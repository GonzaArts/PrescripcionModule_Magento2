/**
 * Powerline PrescripcionModule - Prescription CTA Component
 *
 * @category  Powerline
 * @package   Powerline_PrescripcionModule
 * @author    Powerline Development Team
 * @copyright Copyright (c) 2025 Powerline
 */

define([
    'jquery',
    'mage/translate',
    'Magento_Ui/js/modal/modal'
], function ($, $t, modal) {
    'use strict';

    /**
     * Prescription CTA Widget
     */
    return function (config, element) {
        var $button = $(element);
        var modalElement = null;

        /**
         * Create validation modal
         */
        function createValidationModal() {
            if (!modalElement) {
                modalElement = $('<div class="prescription-validation-modal"/>').html(
                    '<div style="text-align: center; padding: 20px;">' +
                    '<p style="font-size: 18px; margin-bottom: 30px; font-weight: 500;">' + 
                    $t('Seleccione los campos obligatorios') + 
                    '</p>' +
                    '<button type="button" class="action primary modal-close" style="padding: 12px 40px; font-size: 16px;">' +
                    $t('Cerrar') +
                    '</button>' +
                    '</div>'
                );
                
                $('body').append(modalElement);
                
                modal({
                    type: 'popup',
                    responsive: true,
                    innerScroll: true,
                    modalClass: 'prescription-validation-modal-popup',
                    buttons: []
                }, modalElement);
                
                modalElement.on('click', '.modal-close', function() {
                    modalElement.modal('closeModal');
                });
            }
            return modalElement;
        }

        /**
         * Track event
         *
         * @param {String} eventName
         * @param {Object} data
         */
        function trackEvent(eventName, data) {
            $(document).trigger('presc:' + eventName, data);

            // Google Analytics 4 tracking
            if (window.dataLayer) {
                window.dataLayer.push({
                    event: 'presc_' + eventName,
                    presc_product_id: config.productId,
                    ...data
                });
            }
        }

        /**
         * Check if size/talla is selected
         */
        function isSizeSelected() {
            // Buscar cualquier input/select de talla/size
            var $sizeInputs = $('input[type="radio"][name*="super_attribute"]:checked, select[name*="super_attribute"]');
            
            // Si hay radios seleccionados, está ok
            if ($sizeInputs.filter('input[type="radio"]').length > 0) {
                return true;
            }
            
            // Si hay select con valor, está ok
            var $sizeSelect = $sizeInputs.filter('select');
            if ($sizeSelect.length > 0 && $sizeSelect.val() && $sizeSelect.val() !== '') {
                return true;
            }
            
            // Si no hay opciones configurables, permitir continuar
            if ($('input[name*="super_attribute"], select[name*="super_attribute"]').length === 0) {
                return true;
            }
            
            return false;
        }

        /**
         * Highlight size selector
         */
        function highlightSizeSelector() {
            var $sizeContainer = $('.swatch-attribute, .product-options-wrapper, .fieldset').first();

            if ($sizeContainer.length) {
                // Scroll to selector
                $('html, body').animate({
                    scrollTop: $sizeContainer.offset().top - 100
                }, 500);

                // Highlight with red border
                $sizeContainer.css({
                    'border': '2px solid #e02b27',
                    'box-shadow': '0 0 5px rgba(224, 43, 39, 0.5)',
                    'padding': '10px',
                    'border-radius': '4px'
                });

                // Remove highlight after 3 seconds
                setTimeout(function () {
                    $sizeContainer.css({
                        'border': '',
                        'box-shadow': '',
                        'padding': ''
                    });
                }, 3000);
            }
        }

        /**
         * Get selected size attribute and value, and find simple product ID
         */
        function getSelectedSize() {
            var result = {
                attributeId: null,
                value: null,
                simpleProductId: null
            };
            
            console.log('🔎 Starting getSelectedSize()...');
            
            // Try to find selected super_attribute value
            var $radioChecked = $('input[type="radio"][name*="super_attribute"]:checked');
            
            if ($radioChecked.length > 0) {
                var name = $radioChecked.attr('name');
                var match = name.match(/super_attribute\[(\d+)\]/);
                if (match) {
                    result.attributeId = match[1];
                    result.value = $radioChecked.val();
                    console.log('Found from radio:', result);
                }
            }
            
            // Try select dropdown
            if (!result.value) {
                var $select = $('select[name*="super_attribute"]');
                if ($select.length > 0 && $select.val()) {
                    var selectName = $select.attr('name');
                    var selectMatch = selectName.match(/super_attribute\[(\d+)\]/);
                    if (selectMatch) {
                        result.attributeId = selectMatch[1];
                        result.value = $select.val();
                        console.log('Found from select:', result);
                    }
                }
            }
            
            // Now find the simple product ID based on the selected option
            if (result.attributeId && result.value) {
                console.log('Looking for simple product with attribute', result.attributeId, '=', result.value);
                
                // Method 1: Use Magento's spConfig.index
                // spConfig.index maps [attributeId][optionId] => [simpleProductIds]
                if (typeof $ !== 'undefined' && $('[data-role=swatch-options]').length > 0) {
                    var swatchWidget = $('[data-role=swatch-options]').data('mage-SwatchRenderer');
                    if (swatchWidget && swatchWidget.options && swatchWidget.options.jsonConfig) {
                        var jsonConfig = swatchWidget.options.jsonConfig;
                        if (jsonConfig.index && jsonConfig.index[result.attributeId]) {
                            var simpleProducts = jsonConfig.index[result.attributeId][result.value];
                            if (simpleProducts && simpleProducts.length > 0) {
                                result.simpleProductId = simpleProducts[0];
                                console.log('✅ Found simple product from SwatchRenderer:', result.simpleProductId);
                            }
                        }
                    }
                }
                
                // Method 2: Check $.configurable widget (for non-swatch configurables)
                if (!result.simpleProductId && typeof $ !== 'undefined') {
                    var $configurableForm = $('[data-role=swatch-option-' + result.value + ']').closest('form');
                    if ($configurableForm.length === 0) {
                        $configurableForm = $('form#product_addtocart_form');
                    }
                    
                    if ($configurableForm.length > 0) {
                        var configurableWidget = $configurableForm.data('mageConfigurable');
                        if (configurableWidget && configurableWidget.options && configurableWidget.options.spConfig) {
                            var spConfig = configurableWidget.options.spConfig;
                            if (spConfig.index && spConfig.index[result.attributeId]) {
                                var products = spConfig.index[result.attributeId][result.value];
                                if (products && products.length > 0) {
                                    result.simpleProductId = products[0];
                                    console.log('✅ Found simple product from Configurable widget:', result.simpleProductId);
                                }
                            }
                        }
                    }
                }
                
                // Method 3: Look in global spConfig variable
                if (!result.simpleProductId && typeof window.spConfig !== 'undefined') {
                    console.log('Checking window.spConfig...');
                    if (window.spConfig.index && window.spConfig.index[result.attributeId]) {
                        var products = window.spConfig.index[result.attributeId][result.value];
                        if (products && products.length > 0) {
                            result.simpleProductId = products[0];
                            console.log('✅ Found simple product from window.spConfig:', result.simpleProductId);
                        }
                    }
                }
            }
            
            console.log('Final result:', result);
            return result.attributeId && result.value ? result : null;
        }

        /**
         * Handle configurator button click
         */
        $button.on('click', function (e) {
            e.preventDefault();
            e.stopPropagation();

            // Check if size is selected
            if (!isSizeSelected()) {
                // Show modal instead of alert
                var $modal = createValidationModal();
                $modal.modal('openModal');
                
                highlightSizeSelector();
                
                trackEvent('cta_error', {
                    source: 'pdp',
                    error: 'size_not_selected'
                });
                
                return false;
            }

            // Track click event
            trackEvent('cta_clicked', {
                source: 'pdp',
                action: 'open_configurator'
            });

            // Add loading state
            $button.addClass('loading').prop('disabled', true);
            
            // Get selected size attribute value
            var selectedSize = getSelectedSize();
            
            console.log('🚀 NAVIGATING TO CONFIGURATOR:', {
                configuratorUrl: config.configuratorUrl,
                selectedSize: selectedSize,
                productId: config.productId
            });
            
            // Navigate to configurator
            if (config.configuratorUrl) {
                var url = config.configuratorUrl;
                
                console.log('🔍 Base URL from config:', url);
                
                // ALWAYS add super_attribute to URL (backend will resolve the simple product)
                if (selectedSize && selectedSize.attributeId && selectedSize.value) {
                    // Use super_attribute[id]=value format (array notation)
                    url += (url.indexOf('?') > -1 ? '&' : '?');
                    url += 'super_attribute[' + selectedSize.attributeId + ']=' + encodeURIComponent(selectedSize.value);
                    console.log('✅ Added super_attribute[' + selectedSize.attributeId + ']=' + selectedSize.value);
                    
                    // Optional: Also try to replace product_id if we found simple product
                    if (selectedSize.simpleProductId) {
                        url = url.replace(/product_id\/\d+/, 'product_id/' + selectedSize.simpleProductId);
                        console.log('✅ Also replaced with simple product ID:', selectedSize.simpleProductId);
                    } else {
                        console.log('⚠️ Simple product ID not found in JS, backend will resolve it');
                    }
                } else {
                    console.log('⚠️ No size selection detected, using base URL');
                }
                
                console.log('📍 Final URL:', url);
                
                // Navigate (removed alert for cleaner UX)
                window.location.href = url;
            } else {
                console.error('❌ No configuratorUrl found in config!');
                alert('Error: No se encontró la URL del configurador');
            }
        });

        /**
         * Track impression
         */
        trackEvent('cta_impression', {
            source: 'pdp'
        });

        // Initialize
        console.log('Prescription CTA initialized for product:', config.productId);
    };
});
