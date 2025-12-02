/**
 * Powerline PrescripcionModule - Main Configurator Component
 *
 * Orchestrates the multi-step prescription configurator:
 * - Step navigation and state management
 * - AJAX calls for validation and pricing
 * - Debounced price updates
 * - Custom events for step transitions
 *
 * @category  Powerline
 * @package   Powerline_PrescripcionModule
 */

define([
    'jquery',
    'Powerline_PrescripcionModule/js/step/use-type',
    'mage/translate',
    'jquery/ui'
], function ($, UseTypeStep, $t) {
    'use strict';

    $.widget('powerline.configurator', {
        options: {
            endpoints: {
                validate: '',
                price: ''
            },
            config: {},
            debounceDelay: 300
        },

        /**
         * Widget initialization
         */
        _create: function () {
            this.currentStep = 0;
            this.steps = ['uso', 'prescripcion', 'lentes', 'tratamientos', 'extras', 'resumen'];
            this.stepData = {};
            this.priceDebounceTimer = null;
            this.isValidating = false;
            this.isPricing = false;

            // Check if in edit mode and load existing configuration
            if (this.options.isEditMode && this.options.existingConfiguration) {
                this._loadExistingConfiguration(this.options.existingConfiguration);
            }

            this._initializeSteps();
            this._attachEvents();
            this._loadStep(0);
        },

        /**
         * Initialize all step components
         */
        _initializeSteps: function () {
            const self = this;

            // Initialize Use Type Step
            const useTypeContainer = this.element.find('.step-content[data-step="uso"] .use-type-selector');
            UseTypeStep.init(useTypeContainer, this.options.config, function (useType) {
                self.stepData.use_type = useType;
                self._enableNavigation();
                self._triggerCustomEvent('useTypeSelected', { use_type: useType });
                
                // Calcular precio inmediatamente cuando se selecciona tipo de uso
                self._calculatePrice();
            });

            this.stepComponents = {
                uso: UseTypeStep
            };

            // Populate with existing data if in edit mode
            if (this.options.isEditMode) {
                this._populateStepWithData('uso');
            }
        },
        
        /**
         * Initialize prescription form step (lazy loaded)
         */
        _initializePrescriptionStep: function () {
            const self = this;
            
            if (this.stepComponents.prescripcion) {
                return; // Already initialized
            }
            
            require(['Powerline_PrescripcionModule/js/step/prescription-form'], function (PrescriptionFormStep) {
                const formContainer = self.element.find('.step-content[data-step="prescripcion"] .prescription-form');
                PrescriptionFormStep.init(formContainer, self.options.config, self.stepData.use_type, function (formData) {
                    self.stepData.prescription_data = formData;
                    
                    // Guardar datos del attachment si existen
                    if (formData.attachment_hash) {
                        self.stepData.attachment_hash = formData.attachment_hash;
                        self.stepData.attachment_filename = formData.attachment_filename;
                        self.stepData.attachment_filepath = formData.attachment_filepath;
                    }
                    
                    self._calculatePrice();
                    self._validatePrescription();
                });
                
                self.stepComponents.prescripcion = PrescriptionFormStep;

                // Populate with existing data if in edit mode
                if (self.options.isEditMode) {
                    self._populateStepWithData('prescripcion');
                }
            });
        },

        /**
         * Initialize lens selector step (lazy loaded)
         */
        _initializeLensStep: function () {
            const self = this;
            
            if (this.stepComponents.lentes) {
                return;
            }
            
            require(['Powerline_PrescripcionModule/js/step/lens-selector'], function (LensSelectorStep) {
                const lensContainer = self.element.find('.step-content[data-step="lentes"] .lens-selector');
                LensSelectorStep.init(lensContainer, self.options.config, self.stepData.prescription_data, function (lensData) {
                    Object.assign(self.stepData, lensData);
                    self._calculatePrice();
                });
                
                self.stepComponents.lentes = LensSelectorStep;

                // Populate with existing data if in edit mode
                if (self.options.isEditMode) {
                    self._populateStepWithData('lentes');
                }
            });
        },

        /**
         * Initialize treatments selector step (lazy loaded)
         */
        _initializeTreatmentsStep: function () {
            const self = this;
            
            if (this.stepComponents.tratamientos) {
                return;
            }
            
            require(['Powerline_PrescripcionModule/js/step/treatments-selector'], function (TreatmentsSelectorStep) {
                const treatmentsContainer = self.element.find('.step-content[data-step="tratamientos"] .treatments-selector');
                const lensData = {
                    lens_material: self.stepData.lens_material,
                    lens_design: self.stepData.lens_design,
                    lens_index: self.stepData.lens_index
                };
                TreatmentsSelectorStep.init(treatmentsContainer, self.options.config, lensData, function (treatments) {
                    self.stepData.treatments = treatments;
                    self._calculatePrice();
                });
                
                self.stepComponents.tratamientos = TreatmentsSelectorStep;

                // Populate with existing data if in edit mode
                if (self.options.isEditMode) {
                    self._populateStepWithData('tratamientos');
                }
            });
        },

        /**
         * Initialize extras selector step (lazy loaded)
         */
        _initializeExtrasStep: function () {
            const self = this;
            
            if (this.stepComponents.extras) {
                return;
            }
            
            require(['Powerline_PrescripcionModule/js/step/extras-selector'], function (ExtrasSelectorStep) {
                const extrasContainer = self.element.find('.step-content[data-step="extras"] .extras-selector');
                ExtrasSelectorStep.init(extrasContainer, self.options.config, function (extras) {
                    self.stepData.extras = extras;
                    self._calculatePrice();
                });
                
                self.stepComponents.extras = ExtrasSelectorStep;
            });
        },

        /**
         * Initialize summary step (lazy loaded)
         */
        _initializeSummaryStep: function () {
            if (this.stepComponents.resumen) {
                return;
            }
            
            this._renderSummary();
            this.stepComponents.resumen = { validate: () => true, getData: () => ({}) };
        },

        /**
         * Attach event listeners
         */
        _attachEvents: function () {
            const self = this;

            // Next button
            this.element.on('click', '.btn-next', function () {
                self._nextStep();
            });

            // Previous button
            this.element.on('click', '.btn-prev', function () {
                self._prevStep();
            });

            // Add to cart button
            this.element.on('click', '.btn-add-to-cart', function () {
                self._addToCart();
            });

            // Progress step click
            this.element.find('.progress-steps .step').on('click', function () {
                const stepName = $(this).data('step');
                const stepIndex = self.steps.indexOf(stepName);
                
                if (stepIndex >= 0 && stepIndex < self.currentStep) {
                    self._loadStep(stepIndex);
                }
            });
        },

        /**
         * Load specific step
         *
         * @param {number} stepIndex
         */
        _loadStep: function (stepIndex) {
            if (stepIndex < 0 || stepIndex >= this.steps.length) {
                return;
            }

            // Hide all steps
            this.element.find('.step-content').hide();

            // Show current step
            const stepName = this.steps[stepIndex];
            this.element.find(`.step-content[data-step="${stepName}"]`).show();

            // Lazy load step components
            if (stepName === 'prescripcion' && !this.stepComponents.prescripcion) {
                this._initializePrescriptionStep();
            } else if (stepName === 'lentes' && !this.stepComponents.lentes) {
                this._initializeLensStep();
            } else if (stepName === 'tratamientos' && !this.stepComponents.tratamientos) {
                this._initializeTreatmentsStep();
            } else if (stepName === 'extras' && !this.stepComponents.extras) {
                this._initializeExtrasStep();
            } else if (stepName === 'resumen' && !this.stepComponents.resumen) {
                this._initializeSummaryStep();
            }

            // Update progress
            this._updateProgress(stepIndex);

            // Update navigation buttons
            this._updateNavigationButtons(stepIndex);

            this.currentStep = stepIndex;
            
            // Recalcular precios cuando cambie de paso para mostrar progresivamente
            this._calculatePrice();

            // Trigger custom event
            this._triggerCustomEvent('stepChanged', {
                step: stepName,
                index: stepIndex
            });
        },

        /**
         * Navigate to next step
         */
        _nextStep: function () {
            // Validate current step
            if (!this._validateCurrentStep()) {
                return;
            }

            // Save step data
            this._saveStepData();

            // Move to next step
            if (this.currentStep < this.steps.length - 1) {
                this._loadStep(this.currentStep + 1);
            }
        },

        /**
         * Navigate to previous step
         */
        _prevStep: function () {
            if (this.currentStep > 0) {
                this._loadStep(this.currentStep - 1);
            }
        },

        /**
         * Validate current step
         *
         * @return {boolean}
         */
        _validateCurrentStep: function () {
            const stepName = this.steps[this.currentStep];
            const component = this.stepComponents[stepName];

            if (component && typeof component.validate === 'function') {
                return component.validate();
            }

            return true;
        },

        /**
         * Save current step data
         */
        _saveStepData: function () {
            const stepName = this.steps[this.currentStep];
            const component = this.stepComponents[stepName];

            if (component && typeof component.getData === 'function') {
                const data = component.getData();
                Object.assign(this.stepData, data);
            }
        },

        /**
         * Update progress bar and steps
         *
         * @param {number} stepIndex
         */
        _updateProgress: function (stepIndex) {
            console.log('[Configurator] Actualizando barra de progreso al paso:', stepIndex + 1);
            
            const $steps = this.element.find('.progress-steps .step');
            const $progressLine = this.element.find('.progress-line');
            const totalSteps = this.steps.length; // 6 pasos
            
            // Calcular porcentaje: paso actual / (total - 1) * 100
            // Paso 0 = 0%, Paso 1 = 20%, Paso 2 = 40%, ..., Paso 5 = 100%
            const progressPercent = stepIndex === 0 ? 0 : (stepIndex / (totalSteps - 1)) * 100;

            // Actualizar estados de los pasos
            $steps.each(function (index) {
                const $step = $(this);
                const $stepNumber = $step.find('.step-number');
                
                // Limpiar clases
                $step.removeClass('active completed');

                if (index < stepIndex) {
                    // Paso completado: azul con ✓
                    $step.addClass('completed');
                    $stepNumber.html('✓');
                } else if (index === stepIndex) {
                    // Paso actual: azul con número
                    $step.addClass('active');
                    $stepNumber.html(index + 1);
                } else {
                    // Paso pendiente: gris con número
                    $stepNumber.html(index + 1);
                }
            });

            // Actualizar ancho de la línea azul
            $progressLine.css('width', progressPercent + '%');
            
            console.log('[Configurator] Línea de progreso actualizada a:', progressPercent.toFixed(1) + '%');
        },

        /**
         * Update navigation button visibility
         *
         * @param {number} stepIndex
         */
        _updateNavigationButtons: function (stepIndex) {
            const $prevBtn = this.element.find('.btn-prev');
            const $nextBtn = this.element.find('.btn-next');
            const $addToCartBtn = this.element.find('.btn-add-to-cart');

            // Previous button
            if (stepIndex === 0) {
                $prevBtn.hide();
            } else {
                $prevBtn.show();
            }

            // Next vs Add to Cart button
            if (stepIndex === this.steps.length - 1) {
                $nextBtn.hide();
                $addToCartBtn.show();
            } else {
                $nextBtn.show();
                $addToCartBtn.hide();
            }
        },

        /**
         * Enable navigation (called after step validation)
         */
        _enableNavigation: function () {
            this.element.find('.btn-next').prop('disabled', false);
        },

        /**
         * Disable navigation
         */
        _disableNavigation: function () {
            this.element.find('.btn-next').prop('disabled', true);
        },

        /**
         * Validate prescription via AJAX (debounced)
         */
        _validatePrescription: function () {
            const self = this;

            if (this.isValidating) {
                return;
            }

            this.isValidating = true;

            $.ajax({
                url: this.options.endpoints.validate,
                type: 'POST',
                dataType: 'json',
                contentType: 'application/json',
                data: JSON.stringify(this._buildRequestData()),
                success: function (response) {
                    self._handleValidationResponse(response);
                },
                error: function (xhr) {
                    self._handleAjaxError(xhr, 'validation');
                },
                complete: function () {
                    self.isValidating = false;
                }
            });
        },

        /**
         * Calculate price via AJAX (debounced)
         */
        _calculatePrice: function () {
            const self = this;

            // Clear existing timer
            if (this.priceDebounceTimer) {
                clearTimeout(this.priceDebounceTimer);
            }

            // Set new timer
            this.priceDebounceTimer = setTimeout(function () {
                self._executePriceCalculation();
            }, this.options.debounceDelay);
        },

        /**
         * Execute price calculation
         */
        _executePriceCalculation: function () {
            const self = this;

            if (this.isPricing) {
                return;
            }

            this.isPricing = true;
            this._showPriceLoading();

            $.ajax({
                url: this.options.endpoints.price,
                type: 'POST',
                dataType: 'json',
                contentType: 'application/json',
                data: JSON.stringify(this._buildRequestData()),
                success: function (response) {
                    self._handlePriceResponse(response);
                },
                error: function (xhr) {
                    self._handleAjaxError(xhr, 'pricing');
                },
                complete: function () {
                    self.isPricing = false;
                    self._hidePriceLoading();
                }
            });
        },

        /**
         * Build request data for AJAX calls
         *
         * @return {Object}
         */
        _buildRequestData: function () {
            const requestData = {
                product_id: this.options.config.product_id,
                use_type: this.stepData.use_type || '',
                prescription_data: this.stepData.prescription_data || {},
                lens_material: this.stepData.lens_material || '',
                lens_design: this.stepData.lens_design || '',
                lens_index: this.stepData.lens_index || '',
                treatments: this.stepData.treatments || [],
                extras: this.stepData.extras || [],
                attachment_id: this.stepData.attachment_id || null
            };
            
            console.log('[Configurator] Datos enviados para cálculo de precio:', JSON.stringify(requestData, null, 2));
            
            return requestData;
        },

        /**
         * Handle validation response
         *
         * @param {Object} response
         */
        _handleValidationResponse: function (response) {
            if (response.success && response.validation_result) {
                const result = response.validation_result;

                if (result.is_valid) {
                    this._clearValidationMessages();
                    this._enableNavigation();
                } else {
                    this._showValidationMessages(result.errors, 'error');
                    this._disableNavigation();
                }

                if (result.warnings && result.warnings.length > 0) {
                    this._showValidationMessages(result.warnings, 'warning');
                }
            }
        },

        /**
         * Handle price response
         *
         * @param {Object} response
         */
        _handlePriceResponse: function (response) {
            console.log('[Configurator] Respuesta de precio recibida:', JSON.stringify(response, null, 2));
            
            if (response.success && response.price_breakdown) {
                this._updatePriceDisplay(response.price_breakdown);
            } else {
                console.warn('[Configurator] Respuesta de precio sin breakdown:', response);
            }
        },

        /**
         * Update price display
         *
         * @param {Object} breakdown
         */
        _updatePriceDisplay: function (breakdown) {
            console.log('[Configurator] Actualizando display de precios con breakdown:', breakdown);
            console.log('[Configurator] Paso actual:', this.currentStep, 'de', this.steps.length);
            
            const currencySymbol = '€';
            const stepName = this.steps[this.currentStep];
            
            // Precio de montura (siempre visible)
            const framePrice = breakdown.frame_price || this.options.config.product_price || 0;
            this.element.find('[data-price="frame"]').text(
                framePrice > 0 ? this._formatPrice(framePrice, currencySymbol) : '--'
            );
            
            // Mostrar precios según el paso actual
            // Paso 0: Uso - solo montura
            // Paso 1: Prescripción - montura + recargos
            // Paso 2: Lentes - montura + recargos + lente base
            // Paso 3: Tratamientos - montura + recargos + lente base + tratamientos
            // Paso 4: Extras - montura + recargos + lente base + tratamientos + extras
            // Paso 5: Resumen - todo
            
            // Lente Base (visible desde paso 0: uso - cuando se selecciona tipo de uso)
            if (this.currentStep >= 0) {
                const baseLensPrice = breakdown.base_lens_price || 0;
                this.element.find('[data-price="base"]').text(
                    this._formatPrice(baseLensPrice, currencySymbol)
                );
            } else {
                this.element.find('[data-price="base"]').text('--');
            }
            
            // Recargos de Prescripción (visible desde paso 1: prescripción)
            if (this.currentStep >= 1) {
                const totalSurcharges = (breakdown.sphere_surcharge || 0) +
                                       (breakdown.cylinder_surcharge || 0) +
                                       (breakdown.addition_surcharge || 0) +
                                       (breakdown.prism_surcharge || 0);
                this.element.find('[data-price="surcharges"]').text(
                    this._formatPrice(totalSurcharges, currencySymbol)
                );
            } else {
                this.element.find('[data-price="surcharges"]').text('--');
            }
            
            // Tratamientos (visible desde paso 3: tratamientos)
            if (this.currentStep >= 3) {
                const treatmentsPrice = breakdown.treatments_total || 0;
                this.element.find('[data-price="treatments"]').text(
                    this._formatPrice(treatmentsPrice, currencySymbol)
                );
            } else {
                this.element.find('[data-price="treatments"]').text('--');
            }
            
            // Extras (visible desde paso 4: extras)
            if (this.currentStep >= 4) {
                const extrasPrice = breakdown.extras_total || 0;
                this.element.find('[data-price="extras"]').text(
                    this._formatPrice(extrasPrice, currencySymbol)
                );
            } else {
                this.element.find('[data-price="extras"]').text('--');
            }
            
            // Calcular subtotal y total progresivamente
            let subtotal = framePrice;
            
            // Agregar precio base de lentes desde el paso 0 (cuando se selecciona tipo de uso)
            if (this.currentStep >= 0) {
                subtotal += (breakdown.base_lens_price || 0);
            }
            if (this.currentStep >= 1) {
                subtotal += (breakdown.sphere_surcharge || 0) + (breakdown.cylinder_surcharge || 0) + 
                           (breakdown.addition_surcharge || 0) + (breakdown.prism_surcharge || 0);
            }
            if (this.currentStep >= 3) {
                subtotal += (breakdown.treatments_total || 0);
            }
            if (this.currentStep >= 4) {
                subtotal += (breakdown.extras_total || 0);
            }
            
            // Actualizar subtotal y total
            this.element.find('[data-price="subtotal"]').text(
                this._formatPrice(subtotal, currencySymbol)
            );
            
            this.element.find('[data-price="total"]').text(
                this._formatPrice(subtotal, currencySymbol)
            );
            
            console.log('[Configurator] Precios actualizados - Subtotal:', subtotal, '- Paso:', stepName);
            
            // Si estamos en el paso de resumen, actualizar el resumen también
            if (this.currentStep === this.steps.length - 1) {
                this._renderSummary();
            }
        },

        /**
         * Format price
         *
         * @param {number} amount
         * @param {string} symbol
         * @return {string}
         */
        _formatPrice: function (amount, symbol) {
            if (!amount || amount === 0) {
                return '--';
            }
            return parseFloat(amount).toFixed(2) + ' ' + symbol;
        },

        /**
         * Show price loading indicator
         */
        _showPriceLoading: function () {
            this.element.find('.price-loading').show();
            this.element.find('.price-breakdown').css('opacity', '0.5');
        },

        /**
         * Hide price loading indicator
         */
        _hidePriceLoading: function () {
            this.element.find('.price-loading').hide();
            this.element.find('.price-breakdown').css('opacity', '1');
        },

        /**
         * Show validation messages
         *
         * @param {Array} messages
         * @param {string} type - 'error' or 'warning'
         */
        _showValidationMessages: function (messages, type) {
            const $container = this.element.find('.validation-messages');
            const $list = $container.find('.message-list');

            $list.empty();

            messages.forEach(function (msg) {
                const text = typeof msg === 'object' ? msg.message : msg;
                $list.append($('<li>').text(text));
            });

            $container.removeClass('error warning').addClass(type).show();
        },

        /**
         * Clear validation messages
         */
        _clearValidationMessages: function () {
            this.element.find('.validation-messages').hide();
            this.element.find('.message-list').empty();
        },

        /**
         * Handle AJAX errors
         *
         * @param {Object} xhr
         * @param {string} operation
         */
        _handleAjaxError: function (xhr, operation) {
            console.error(`${operation} error:`, xhr);

            let errorMessage = $t('An error occurred. Please try again.');

            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMessage = xhr.responseJSON.message;
            }

            this._showValidationMessages([errorMessage], 'error');
        },

        /**
         * Trigger custom event
         *
         * @param {string} eventName
         * @param {Object} data
         */
        _triggerCustomEvent: function (eventName, data) {
            this.element.trigger('presc:' + eventName, data);
        },

        /**
         * Add to cart
         */
        _addToCart: function () {
            const self = this;
            const $btn = this.element.find('.btn-add-to-cart');
            
            // Validar que tengamos toda la configuración necesaria
            if (!this._validateCompleteConfiguration()) {
                alert($t('Please complete all required steps before adding to cart.'));
                return;
            }

            // Deshabilitar botón y mostrar loading
            $btn.prop('disabled', true).addClass('loading');
            $btn.text($t('Adding to cart...'));

            // Preparar datos de configuración
            const configuration = {
                prescription: this.stepData.prescription_data,
                use_type: this.stepData.use_type,
                lens: {
                    material: this.stepData.lens_material,
                    design: this.stepData.lens_design,
                    index: this.stepData.lens_index || null
                },
                treatments: this.stepData.treatments || [],
                attachment_id: this.stepData.attachment_id || null,
                attachment_hash: this.stepData.attachment_hash || null,
                attachment_filename: this.stepData.attachment_filename || null,
                attachment_filepath: this.stepData.attachment_filepath || null
            };

            // Request body
            const requestData = {
                product_id: this.options.config.product_id,
                qty: 1,
                configuration: configuration
            };

            // Llamar al endpoint de add to cart
            $.ajax({
                url: this.options.config.endpoints.addtocart || '/presc/ajax/addtocart',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify(requestData),
                dataType: 'json',
                success: function (response) {
                    if (response.success) {
                        // Log para analytics
                        if (window.dataLayer) {
                            window.dataLayer.push({
                                event: 'prescription_add_to_cart',
                                product_id: self.options.config.product_id,
                                total_price: response.price_breakdown.total_price,
                                use_type: configuration.use_type,
                                material: configuration.lens.material,
                                design: configuration.lens.design,
                                treatments_count: configuration.treatments.length
                            });
                        }

                        // Mostrar mensaje de éxito
                        alert($t('Product added to cart successfully!'));
                        
                        // Redirigir al carrito
                        window.location.href = response.cart_url || '/checkout/cart';
                    } else {
                        // Mostrar errores
                        let errorMsg = response.message || $t('Unable to add product to cart.');
                        if (response.errors && response.errors.length > 0) {
                            errorMsg += '\n' + response.errors.join('\n');
                        }
                        alert(errorMsg);
                        
                        // Restaurar botón
                        $btn.prop('disabled', false).removeClass('loading');
                        $btn.text($t('Add to Cart'));
                    }
                },
                error: function (xhr, status, error) {
                    console.error('Add to cart error:', error);
                    alert($t('An error occurred while adding to cart. Please try again.'));
                    
                    // Restaurar botón
                    $btn.prop('disabled', false).removeClass('loading');
                    $btn.text($t('Add to Cart'));
                }
            });
        },

        /**
         * Validar que la configuración esté completa
         */
        _validateCompleteConfiguration: function () {
            return !!(
                this.stepData.prescription_data &&
                this.stepData.use_type &&
                this.stepData.lens_material &&
                this.stepData.lens_design
            );
        },

        /**
         * Render summary step
         */
        _renderSummary: function () {
            // Debug: Ver todos los datos del resumen
            console.log('[Configurator] Renderizando resumen con stepData:', JSON.stringify(this.stepData, null, 2));
            
            const currencySymbol = this.options.config.currency_symbol || '€';
            
            // Obtener precios del lateral
            const framePrice = parseFloat(this.element.find('[data-price="frame"]').text().replace(/[^\d.]/g, '')) || 0;
            const basePrice = parseFloat(this.element.find('[data-price="base"]').text().replace(/[^\d.]/g, '')) || 0;
            const surchargesPrice = parseFloat(this.element.find('[data-price="surcharges"]').text().replace(/[^\d.]/g, '')) || 0;
            const treatmentsPrice = parseFloat(this.element.find('[data-price="treatments"]').text().replace(/[^\d.]/g, '')) || 0;
            const extrasPrice = parseFloat(this.element.find('[data-price="extras"]').text().replace(/[^\d.]/g, '')) || 0;
            const subtotalPrice = parseFloat(this.element.find('[data-price="subtotal"]').text().replace(/[^\d.]/g, '')) || 0;
            const totalPrice = parseFloat(this.element.find('[data-price="total"]').text().replace(/[^\d.]/g, '')) || 0;
            
            console.log('[Configurator] Precios extraídos:', {
                frame: framePrice,
                base: basePrice,
                surcharges: surchargesPrice,
                treatments: treatmentsPrice,
                extras: extrasPrice,
                subtotal: subtotalPrice,
                total: totalPrice
            });
            
            const $summaryContainer = this.element.find('.step-content[data-step="resumen"] .configuration-summary');

            let html = `
                <div class="summary-header" style="text-align: center; margin-bottom: 30px;">
                    <h3 style="font-size: 28px; color: #213b85; margin-bottom: 10px;">Resumen de Configuración</h3>
                    <p style="color: #666; font-size: 16px;">Revisa tu selección antes de añadir al carrito</p>
                </div>
                <div class="summary-sections" style="display: flex; flex-direction: column; gap: 20px;">
            `;

            // 1. Uso (Paso 1)
            html += `
                <div class="summary-section" style="background: #f8f9fa; padding: 20px; border-radius: 8px; border-left: 4px solid #213b85;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                        <h4 style="margin: 0; color: #213b85; font-size: 18px;">
                            <span style="display: inline-block; width: 28px; height: 28px; background: #213b85; color: white; border-radius: 50%; text-align: center; line-height: 28px; margin-right: 10px; font-size: 14px;">1</span>
                            Uso
                        </h4>
                        <span style="font-weight: 600; color: #333; font-size: 16px;">Incluido</span>
                    </div>
                    <p style="margin: 10px 0 0 38px; color: #555; font-size: 15px;">${this._getLabelForValue('use_type', this.stepData.use_type) || 'No especificado'}</p>
                </div>
            `;

            // 2. Prescripción (Paso 2)
            html += `
                <div class="summary-section" style="background: #f8f9fa; padding: 20px; border-radius: 8px; border-left: 4px solid #213b85;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                        <h4 style="margin: 0; color: #213b85; font-size: 18px;">
                            <span style="display: inline-block; width: 28px; height: 28px; background: #213b85; color: white; border-radius: 50%; text-align: center; line-height: 28px; margin-right: 10px; font-size: 14px;">2</span>
                            Prescripción
                        </h4>
                        <span style="font-weight: 600; color: #333; font-size: 16px;">${surchargesPrice > 0 ? `+${surchargesPrice.toFixed(2)} ${currencySymbol}` : 'Incluido'}</span>
                    </div>
                    <div style="margin: 10px 0 0 38px; color: #555; font-size: 14px;">
                        ${this.stepData.prescription_data ? this._renderPrescriptionSummary() : '<p>No especificado</p>'}
                    </div>
                </div>
            `;

            // 3. Cristal (Paso 3 - Material/Tipo)
            html += `
                <div class="summary-section" style="background: #f8f9fa; padding: 20px; border-radius: 8px; border-left: 4px solid #213b85;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                        <h4 style="margin: 0; color: #213b85; font-size: 18px;">
                            <span style="display: inline-block; width: 28px; height: 28px; background: #213b85; color: white; border-radius: 50%; text-align: center; line-height: 28px; margin-right: 10px; font-size: 14px;">3</span>
                            Cristal
                        </h4>
                        <span style="font-weight: 600; color: #333; font-size: 16px;">${basePrice > 0 ? `${basePrice.toFixed(2)} ${currencySymbol}` : 'Incluido'}</span>
                    </div>
                    <div style="margin: 10px 0 0 38px; color: #555;">
                        ${this.stepData.lens_material ? `
                            <p style="margin: 5px 0;"><strong>Material:</strong> ${this.stepData.lens_material}</p>
                            ${this.stepData.lens_design ? `<p style="margin: 5px 0;"><strong>Tipo:</strong> ${this.stepData.lens_design}</p>` : ''}
                        ` : '<p>No especificado</p>'}
                    </div>
                </div>
            `;

            // 4. Marca (Paso 4)
            html += `
                <div class="summary-section" style="background: #f8f9fa; padding: 20px; border-radius: 8px; border-left: 4px solid #213b85;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                        <h4 style="margin: 0; color: #213b85; font-size: 18px;">
                            <span style="display: inline-block; width: 28px; height: 28px; background: #213b85; color: white; border-radius: 50%; text-align: center; line-height: 28px; margin-right: 10px; font-size: 14px;">4</span>
                            Marca
                        </h4>
                        <span style="font-weight: 600; color: #333; font-size: 16px;">Incluido</span>
                    </div>
                    <p style="margin: 10px 0 0 38px; color: #555; font-size: 15px;">${this.stepData.lens_brand || 'No especificado'}</p>
                </div>
            `;

            // 5. Índice (Paso 5)
            html += `
                <div class="summary-section" style="background: #f8f9fa; padding: 20px; border-radius: 8px; border-left: 4px solid #213b85;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                        <h4 style="margin: 0; color: #213b85; font-size: 18px;">
                            <span style="display: inline-block; width: 28px; height: 28px; background: #213b85; color: white; border-radius: 50%; text-align: center; line-height: 28px; margin-right: 10px; font-size: 14px;">5</span>
                            Índice
                        </h4>
                        <span style="font-weight: 600; color: #333; font-size: 16px;">Incluido en Cristal</span>
                    </div>
                    <p style="margin: 10px 0 0 38px; color: #555; font-size: 15px;">${this.stepData.lens_index || 'No especificado'}</p>
                </div>
            `;

            // 6. Tratamientos y Extras combinados
            const hasTreatments = this.stepData.treatments && this.stepData.treatments.length > 0;
            const hasExtras = this.stepData.extras && this.stepData.extras.length > 0;
            const addonsPrice = treatmentsPrice + extrasPrice;
            
            html += `
                <div class="summary-section" style="background: #f8f9fa; padding: 20px; border-radius: 8px; border-left: 4px solid #213b85;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                        <h4 style="margin: 0; color: #213b85; font-size: 18px;">
                            <span style="display: inline-block; width: 28px; height: 28px; background: #213b85; color: white; border-radius: 50%; text-align: center; line-height: 28px; margin-right: 10px; font-size: 14px;">6</span>
                            Tratamientos y Extras
                        </h4>
                        <span style="font-weight: 600; color: #333; font-size: 16px;">${addonsPrice > 0 ? `+${addonsPrice.toFixed(2)} ${currencySymbol}` : 'Sin seleccionar'}</span>
                    </div>
                    <div style="margin: 10px 0 0 38px; color: #555;">
            `;
            
            if (hasTreatments) {
                html += `<p style="margin: 10px 0 5px 0; font-weight: 600; color: #333;">Tratamientos:</p><ul style="margin: 5px 0; padding-left: 20px;">`;
                this.stepData.treatments.forEach(t => {
                    if (typeof t === 'object' && t.name) {
                        html += `<li>${t.name}${t.price ? ` (+${t.price}${currencySymbol})` : ''}</li>`;
                    } else {
                        html += `<li>${t}</li>`;
                    }
                });
                html += `</ul>`;
            }
            
            if (hasExtras) {
                html += `<p style="margin: 10px 0 5px 0; font-weight: 600; color: #333;">Extras:</p><ul style="margin: 5px 0; padding-left: 20px;">`;
                this.stepData.extras.forEach(e => {
                    if (typeof e === 'object') {
                        let text = e.name || e.code || 'Extra';
                        if (e.price) text += ` (+${e.price}${currencySymbol})`;
                        if (e.options) {
                            const opts = Object.entries(e.options).map(([k, v]) => `${k}: ${v}`).join(', ');
                            text += ` (${opts})`;
                        }
                        html += `<li>${text}</li>`;
                    } else {
                        html += `<li>${e}</li>`;
                    }
                });
                html += `</ul>`;
            }
            
            if (!hasTreatments && !hasExtras) {
                html += `<p>No se han añadido tratamientos ni extras</p>`;
            }
            
            html += `
                    </div>
                </div>
            `;

            // Total final
            html += `
                <div class="summary-total" style="background: linear-gradient(135deg, #213b85 0%, #1a2f6b 100%); padding: 25px; border-radius: 8px; margin-top: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="color: white; font-size: 24px; font-weight: 700;">TOTAL</span>
                        <span style="color: white; font-size: 32px; font-weight: 700;">${totalPrice.toFixed(2)} ${currencySymbol}</span>
                    </div>
                    <p style="color: rgba(255,255,255,0.8); margin: 10px 0 0 0; font-size: 14px; text-align: right;">IVA incluido</p>
                </div>
            `;

            html += '</div>';

            $summaryContainer.html(html);
            
            console.log('[Configurator] Resumen completo generado con precios');
        },

        /**
         * Render prescription summary
         *
         * @return {string} HTML
         */
        _renderPrescriptionSummary: function () {
            const pd = this.stepData.prescription_data;
            
            let html = '<table class="prescription-summary-table"><thead><tr>';
            html += `<th>${$t('Eye')}</th><th>${$t('SPH')}</th><th>${$t('CYL')}</th><th>${$t('AXIS')}</th>`;
            
            if (pd.od.add || pd.oi.add) {
                html += `<th>${$t('ADD')}</th>`;
            }
            
            html += '</tr></thead><tbody>';

            // OD
            html += '<tr>';
            html += `<td><strong>${$t('OD')}</strong></td>`;
            html += `<td>${pd.od.sph || '--'}</td>`;
            html += `<td>${pd.od.cyl || '--'}</td>`;
            html += `<td>${pd.od.axis || '--'}</td>`;
            if (pd.od.add || pd.oi.add) {
                html += `<td>${pd.od.add || '--'}</td>`;
            }
            html += '</tr>';

            // OI
            html += '<tr>';
            html += `<td><strong>${$t('OI')}</strong></td>`;
            html += `<td>${pd.oi.sph || '--'}</td>`;
            html += `<td>${pd.oi.cyl || '--'}</td>`;
            html += `<td>${pd.oi.axis || '--'}</td>`;
            if (pd.od.add || pd.oi.add) {
                html += `<td>${pd.oi.add || '--'}</td>`;
            }
            html += '</tr>';

            html += '</tbody></table>';

            // PD
            if (pd.pd) {
                html += `<p><strong>${$t('PD:')}</strong> `;
                if (pd.pd.type === 'binocular') {
                    html += `${pd.pd.value} mm`;
                } else {
                    html += `OD: ${pd.pd.od} mm / OI: ${pd.pd.oi} mm`;
                }
                html += '</p>';
            }

            return html;
        },

        /**
         * Get label for value
         *
         * @param {string} field
         * @param {string} value
         * @return {string}
         */
        _getLabelForValue: function (field, value) {
            if (field === 'use_type' && this.options.config.use_types) {
                const type = this.options.config.use_types.find(t => t.value === value);
                return type ? type.label : value;
            }
            return value;
        },

        /**
         * Load existing configuration for edit mode
         * 
         * @param {Object} config - Existing configuration from cart item
         */
        _loadExistingConfiguration: function (config) {
            console.log('Loading existing configuration:', config);

            // Map configuration to stepData format
            if (config.use_type) {
                this.stepData.use_type = config.use_type;
            }

            if (config.prescription) {
                this.stepData.prescription_data = config.prescription;
            }

            if (config.lens) {
                if (config.lens.material) {
                    this.stepData.lens_material = config.lens.material;
                }
                if (config.lens.design) {
                    this.stepData.lens_design = config.lens.design;
                }
                if (config.lens.index) {
                    this.stepData.lens_index = config.lens.index;
                }
            }

            if (config.treatments && Array.isArray(config.treatments)) {
                this.stepData.treatments = config.treatments;
            }

            if (config.attachment_id) {
                this.stepData.attachment_id = config.attachment_id;
            }

            console.log('Step data loaded:', this.stepData);
        },

        /**
         * Populate step with loaded data
         * Called after step component is initialized
         * 
         * @param {string} stepName
         */
        _populateStepWithData: function (stepName) {
            const component = this.stepComponents[stepName];
            
            if (!component || !component.loadState) {
                return;
            }

            console.log('Populating step with data:', stepName, this.stepData);

            switch (stepName) {
                case 'uso':
                    if (this.stepData.use_type) {
                        component.loadState({ use_type: this.stepData.use_type });
                    }
                    break;

                case 'prescripcion':
                    if (this.stepData.prescription_data) {
                        component.loadState(this.stepData.prescription_data);
                    }
                    break;

                case 'lentes':
                    if (this.stepData.lens_material || this.stepData.lens_design) {
                        component.loadState({
                            material: this.stepData.lens_material,
                            design: this.stepData.lens_design,
                            index: this.stepData.lens_index
                        });
                    }
                    break;

                case 'tratamientos':
                    if (this.stepData.treatments) {
                        component.loadState({ treatments: this.stepData.treatments });
                    }
                    break;
            }
        }
    });

    return $.powerline.configurator;
});
