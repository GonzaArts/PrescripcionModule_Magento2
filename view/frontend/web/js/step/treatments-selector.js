/**
 * Powerline PrescripcionModule - Treatments Selector Step Component
 *
 * Renders the fourth step: lens treatments selection (AR, Blue Light, etc.)
 * with compatibility checking, dynamic pricing, and descriptions
 *
 * @category  Powerline
 * @package   Powerline_PrescripcionModule
 */

define([
    'jquery',
    'mage/translate'
], function ($, $t) {
    'use strict';

    return {
        /**
         * Initialize treatments selector step
         *
         * @param {Object} container - jQuery container element
         * @param {Object} config - Configuration object
         * @param {Object} lensData - Lens selection from step 3
         * @param {Function} onChange - Callback when selection changes
         */
        init: function (container, config, lensData, onChange) {
            this.container = $(container);
            this.config = config;
            this.lensData = lensData;
            this.onChange = onChange;
            this.selectedTreatments = [];

            // Treatments catalog
            this.treatments = [
                {
                    code: 'AR_BASIC',
                    category: 'antireflective',
                    name: $t('Anti-Reflective (Basic)'),
                    description: $t('Reduces glare and reflections for clearer vision.'),
                    benefits: [$t('Reduces glare'), $t('Easier cleaning'), $t('Better aesthetics')],
                    price: 25.00,
                    recommended: true
                },
                {
                    code: 'AR_PREMIUM',
                    category: 'antireflective',
                    name: $t('Anti-Reflective (Premium)'),
                    description: $t('Superior anti-reflective coating with water and oil repellent properties.'),
                    benefits: [$t('Maximum clarity'), $t('Hydrophobic'), $t('Oleophobic'), $t('Scratch resistant')],
                    price: 45.00,
                    incompatibleWith: ['AR_BASIC'],
                    premium: true
                },
                {
                    code: 'AR_BLUE_LIGHT',
                    category: 'antireflective',
                    name: $t('Anti-Reflective + Blue Light'),
                    description: $t('AR coating with blue light filter for digital device protection.'),
                    benefits: [$t('Reduces glare'), $t('Blue light protection'), $t('Eye strain relief'), $t('Better sleep')],
                    price: 55.00,
                    incompatibleWith: ['AR_BASIC', 'AR_PREMIUM', 'BLUE_LIGHT'],
                    premium: true
                },
                {
                    code: 'BLUE_LIGHT',
                    category: 'protection',
                    name: $t('Blue Light Filter'),
                    description: $t('Filters harmful blue light from screens without AR coating.'),
                    benefits: [$t('Blue light protection'), $t('Reduces eye strain'), $t('Improves sleep quality')],
                    price: 30.00,
                    incompatibleWith: ['AR_BLUE_LIGHT']
                },
                {
                    code: 'PHOTOCHROMIC',
                    category: 'protection',
                    name: $t('Photochromic (Transitions)'),
                    description: $t('Lenses darken automatically in sunlight. Clear indoors, tinted outdoors.'),
                    benefits: [$t('UV protection'), $t('No need for sunglasses'), $t('Automatic adjustment'), $t('All-day comfort')],
                    price: 80.00,
                    incompatibleWith: ['POLARIZED'],
                    compatibleMaterials: ['CR39', 'POLYCARBONATE', 'TRIVEX', 'HIGH_INDEX']
                },
                {
                    code: 'POLARIZED',
                    category: 'protection',
                    name: $t('Polarized'),
                    description: $t('Eliminates glare from reflective surfaces. Ideal for driving and outdoor activities.'),
                    benefits: [$t('Eliminates glare'), $t('Enhanced contrast'), $t('UV protection'), $t('Reduced eye fatigue')],
                    price: 70.00,
                    incompatibleWith: ['PHOTOCHROMIC'],
                    sunglassesOnly: true
                },
                {
                    code: 'HARD_COAT',
                    category: 'durability',
                    name: $t('Scratch-Resistant Coating'),
                    description: $t('Protective coating that increases scratch resistance.'),
                    benefits: [$t('Scratch protection'), $t('Longer lens life'), $t('Maintains clarity')],
                    price: 15.00,
                    compatibleMaterials: ['CR39', 'HIGH_INDEX']
                },
                {
                    code: 'UV_PROTECTION',
                    category: 'protection',
                    name: $t('UV Protection'),
                    description: $t('Blocks 100% of harmful UVA and UVB rays.'),
                    benefits: [$t('100% UV protection'), $t('Eye health'), $t('Prevents cataracts')],
                    price: 20.00
                },
                {
                    code: 'MIRROR_COATING',
                    category: 'aesthetic',
                    name: $t('Mirror Coating'),
                    description: $t('Reflective coating available in multiple colors. Fashion and function.'),
                    benefits: [$t('Stylish look'), $t('Reduces light transmission'), $t('Multiple colors available')],
                    price: 35.00,
                    sunglassesOnly: true
                }
            ];

            this.render();
            this.attachEvents();
        },

        /**
         * Render treatments selector interface
         */
        render: function () {
            // Group treatments by category
            const categories = {
                antireflective: $t('Anti-Reflective Coatings'),
                protection: $t('Protection'),
                durability: $t('Durability'),
                aesthetic: $t('Aesthetic')
            };

            let html = '<div class="treatments-selector-wrapper">';

            Object.keys(categories).forEach(categoryKey => {
                const categoryTreatments = this.treatments.filter(t => t.category === categoryKey);
                
                if (categoryTreatments.length > 0) {
                    html += `
                        <div class="treatment-category">
                            <h4>${categories[categoryKey]}</h4>
                            <div class="treatment-options">
                                ${categoryTreatments.map(t => this.renderTreatmentOption(t)).join('')}
                            </div>
                        </div>
                    `;
                }
            });

            html += '</div>';

            this.container.html(html);
            this.checkCompatibility();
        },

        /**
         * Render treatment option
         *
         * @param {Object} treatment
         * @return {string} HTML
         */
        renderTreatmentOption: function (treatment) {
            const currencySymbol = this.config.currency_symbol || '€';
            
            return `
                <div class="treatment-option" data-treatment="${treatment.code}">
                    <div class="option-header">
                        <label class="checkbox-container">
                            <input type="checkbox" value="${treatment.code}">
                            <span class="checkmark"></span>
                            <span class="treatment-name">
                                ${treatment.name}
                                ${treatment.premium ? `<span class="badge premium">${$t('Premium')}</span>` : ''}
                                ${treatment.recommended ? `<span class="badge recommended">${$t('Recommended')}</span>` : ''}
                            </span>
                        </label>
                        <span class="treatment-price">+${currencySymbol} ${treatment.price.toFixed(2)}</span>
                    </div>
                    <div class="option-body">
                        <p class="treatment-description">${treatment.description}</p>
                        <ul class="treatment-benefits">
                            ${treatment.benefits.map(b => `<li>✓ ${b}</li>`).join('')}
                        </ul>
                    </div>
                    <div class="incompatible-message" style="display: none;">
                        ${$t('Not compatible with current selection')}
                    </div>
                </div>
            `;
        },

        /**
         * Attach event listeners
         */
        attachEvents: function () {
            const self = this;

            // Checkbox change
            this.container.on('change', 'input[type="checkbox"]', function () {
                const treatmentCode = $(this).val();
                const isChecked = $(this).is(':checked');

                if (isChecked) {
                    self.addTreatment(treatmentCode);
                } else {
                    self.removeTreatment(treatmentCode);
                }
            });

            // Option click (expand/collapse)
            this.container.on('click', '.treatment-option', function (e) {
                if (!$(e.target).is('input[type="checkbox"]') && !$(e.target).is('.checkmark')) {
                    $(this).toggleClass('expanded');
                }
            });
        },

        /**
         * Add treatment to selection
         *
         * @param {string} treatmentCode
         */
        addTreatment: function (treatmentCode) {
            // Check if already selected
            if (this.selectedTreatments.indexOf(treatmentCode) !== -1) {
                return;
            }

            this.selectedTreatments.push(treatmentCode);

            // Check for incompatibilities and disable conflicting treatments
            this.checkCompatibility();

            // Trigger onChange
            if (this.onChange) {
                this.onChange(this.selectedTreatments);
            }

            // Track GA4 event
            if (window.dataLayer) {
                window.dataLayer.push({
                    'event': 'prescription_treatment_added',
                    'treatment': treatmentCode
                });
            }
        },

        /**
         * Remove treatment from selection
         *
         * @param {string} treatmentCode
         */
        removeTreatment: function (treatmentCode) {
            const index = this.selectedTreatments.indexOf(treatmentCode);
            if (index !== -1) {
                this.selectedTreatments.splice(index, 1);
            }

            // Re-check compatibility
            this.checkCompatibility();

            // Trigger onChange
            if (this.onChange) {
                this.onChange(this.selectedTreatments);
            }
        },

        /**
         * Check treatment compatibility
         */
        checkCompatibility: function () {
            const self = this;

            this.treatments.forEach(treatment => {
                const $option = this.container.find(`.treatment-option[data-treatment="${treatment.code}"]`);
                const $checkbox = $option.find('input[type="checkbox"]');
                let isCompatible = true;
                let reason = '';

                // Check material compatibility
                if (treatment.compatibleMaterials && this.lensData.lens_material) {
                    if (treatment.compatibleMaterials.indexOf(this.lensData.lens_material) === -1) {
                        isCompatible = false;
                        reason = $t('Not compatible with %1 material').replace('%1', this.lensData.lens_material);
                    }
                }

                // Check incompatibilities with selected treatments
                if (treatment.incompatibleWith) {
                    treatment.incompatibleWith.forEach(incompatibleCode => {
                        if (self.selectedTreatments.indexOf(incompatibleCode) !== -1) {
                            isCompatible = false;
                            const incompatibleTreatment = self.treatments.find(t => t.code === incompatibleCode);
                            reason = $t('Incompatible with %1').replace('%1', incompatibleTreatment ? incompatibleTreatment.name : incompatibleCode);
                        }
                    });
                }

                // Apply compatibility state
                if (!isCompatible) {
                    $option.addClass('incompatible');
                    $checkbox.prop('disabled', true).prop('checked', false);
                    $option.find('.incompatible-message').text(reason).show();
                    
                    // Remove from selection if was selected
                    self.removeTreatment(treatment.code);
                } else {
                    $option.removeClass('incompatible');
                    $checkbox.prop('disabled', false);
                    $option.find('.incompatible-message').hide();
                }
            });
        },

        /**
         * Validate step
         *
         * @return {boolean}
         */
        validate: function () {
            // Treatments are optional, always valid
            return true;
        },

        /**
         * Get step data
         *
         * @return {Object}
         */
        getData: function () {
            return {
                treatments: this.selectedTreatments
            };
        }
    };
});
