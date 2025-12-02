/**
 * Powerline PrescripcionModule - Extras Selector Step Component
 *
 * Renders the fifth step: additional options and services
 * (premium coating, edge polish, tints, etc.)
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
         * Initialize extras selector step
         *
         * @param {Object} container - jQuery container element
         * @param {Object} config - Configuration object
         * @param {Function} onChange - Callback when selection changes
         */
        init: function (container, config, onChange) {
            this.container = $(container);
            this.config = config;
            this.onChange = onChange;
            this.selectedExtras = [];

            // Extras catalog
            this.extras = [
                {
                    code: 'premium_coating',
                    name: $t('Premium Super-Hydrophobic Coating'),
                    description: $t('Advanced coating that repels water, oil, and dust. Easier to clean and maintains clarity longer.'),
                    benefits: [
                        $t('Repels water and oil'),
                        $t('Dust resistant'),
                        $t('Easier cleaning'),
                        $t('Extended clarity')
                    ],
                    price: 15.00
                },
                {
                    code: 'edge_polish',
                    name: $t('Edge Polishing'),
                    description: $t('Polished lens edges for rimless and semi-rimless frames. Better aesthetics and durability.'),
                    benefits: [
                        $t('Better appearance'),
                        $t('Increased durability'),
                        $t('Professional finish')
                    ],
                    price: 10.00,
                    frameDependant: true
                },
                {
                    code: 'custom_tint',
                    name: $t('Custom Tint'),
                    description: $t('Solid tint in your choice of color and density. Fashion or functional.'),
                    benefits: [
                        $t('Multiple colors available'),
                        $t('Custom density'),
                        $t('UV protection'),
                        $t('Reduces brightness')
                    ],
                    price: 20.00,
                    hasOptions: true,
                    options: {
                        colors: ['gray', 'brown', 'green', 'rose', 'yellow'],
                        densities: ['15%', '25%', '50%', '75%', '85%']
                    }
                },
                {
                    code: 'gradient_tint',
                    name: $t('Gradient Tint'),
                    description: $t('Gradual tint from dark at top to lighter at bottom. Stylish and practical for driving.'),
                    benefits: [
                        $t('Fashionable look'),
                        $t('Top sun protection'),
                        $t('Clear bottom for reading'),
                        $t('Multiple colors')
                    ],
                    price: 18.00,
                    hasOptions: true,
                    options: {
                        colors: ['gray', 'brown', 'green', 'rose']
                    }
                },
                {
                    code: 'mirror_coating',
                    name: $t('Mirror Coating'),
                    description: $t('Reflective mirror coating in various colors. High fashion and additional glare reduction.'),
                    benefits: [
                        $t('Reflective look'),
                        $t('Reduces glare'),
                        $t('Multiple colors'),
                        $t('Fashion statement')
                    ],
                    price: 25.00,
                    hasOptions: true,
                    options: {
                        colors: ['silver', 'gold', 'blue', 'green', 'red', 'purple']
                    }
                },
                {
                    code: 'anti_fog',
                    name: $t('Anti-Fog Treatment'),
                    description: $t('Prevents fogging in humid conditions or with face masks. Essential for healthcare workers.'),
                    benefits: [
                        $t('Prevents fogging'),
                        $t('Mask compatible'),
                        $t('All-day clarity'),
                        $t('Reusable treatment')
                    ],
                    price: 12.00
                },
                {
                    code: 'warranty_plus',
                    name: $t('Extended Warranty (2 Years)'),
                    description: $t('Extended warranty covering manufacturing defects, accidental damage, and scratch protection.'),
                    benefits: [
                        $t('2-year coverage'),
                        $t('Accidental damage'),
                        $t('Scratch protection'),
                        $t('Peace of mind')
                    ],
                    price: 30.00
                }
            ];

            this.render();
            this.attachEvents();
        },

        /**
         * Render extras selector interface
         */
        render: function () {
            const html = `
                <div class="extras-selector-wrapper">
                    <div class="extras-intro">
                        <p>${$t('Enhance your lenses with additional features and services. All extras are optional.')}</p>
                    </div>
                    <div class="extras-list">
                        ${this.extras.map(e => this.renderExtraOption(e)).join('')}
                    </div>
                </div>
            `;

            this.container.html(html);
        },

        /**
         * Render extra option
         *
         * @param {Object} extra
         * @return {string} HTML
         */
        renderExtraOption: function (extra) {
            const currencySymbol = this.config.currency_symbol || '€';
            
            return `
                <div class="extra-option" data-extra="${extra.code}">
                    <div class="option-header">
                        <label class="checkbox-container">
                            <input type="checkbox" value="${extra.code}">
                            <span class="checkmark"></span>
                            <span class="extra-name">${extra.name}</span>
                        </label>
                        <span class="extra-price">+${currencySymbol} ${extra.price.toFixed(2)}</span>
                    </div>
                    <div class="option-body">
                        <p class="extra-description">${extra.description}</p>
                        <ul class="extra-benefits">
                            ${extra.benefits.map(b => `<li>✓ ${b}</li>`).join('')}
                        </ul>
                        ${extra.hasOptions ? this.renderExtraOptions(extra) : ''}
                    </div>
                </div>
            `;
        },

        /**
         * Render extra customization options
         *
         * @param {Object} extra
         * @return {string} HTML
         */
        renderExtraOptions: function (extra) {
            let html = '<div class="extra-customization" style="display: none;">';

            if (extra.options.colors) {
                html += `
                    <div class="customization-field">
                        <label>${$t('Select Color:')}</label>
                        <select class="extra-option-select" data-option="color">
                            <option value="">${$t('Choose...')}</option>
                            ${extra.options.colors.map(c => 
                                `<option value="${c}">${this.getColorName(c)}</option>`
                            ).join('')}
                        </select>
                    </div>
                `;
            }

            if (extra.options.densities) {
                html += `
                    <div class="customization-field">
                        <label>${$t('Select Density:')}</label>
                        <select class="extra-option-select" data-option="density">
                            <option value="">${$t('Choose...')}</option>
                            ${extra.options.densities.map(d => 
                                `<option value="${d}">${d}</option>`
                            ).join('')}
                        </select>
                    </div>
                `;
            }

            html += '</div>';
            return html;
        },

        /**
         * Get translated color name
         *
         * @param {string} colorCode
         * @return {string}
         */
        getColorName: function (colorCode) {
            const colorNames = {
                'gray': $t('Gray'),
                'brown': $t('Brown'),
                'green': $t('Green'),
                'rose': $t('Rose'),
                'yellow': $t('Yellow'),
                'silver': $t('Silver'),
                'gold': $t('Gold'),
                'blue': $t('Blue'),
                'red': $t('Red'),
                'purple': $t('Purple')
            };

            return colorNames[colorCode] || colorCode;
        },

        /**
         * Attach event listeners
         */
        attachEvents: function () {
            const self = this;

            // Checkbox change
            this.container.on('change', 'input[type="checkbox"]', function () {
                const extraCode = $(this).val();
                const isChecked = $(this).is(':checked');
                const $option = $(this).closest('.extra-option');

                if (isChecked) {
                    // Show customization options if available
                    $option.find('.extra-customization').slideDown(300);
                    self.addExtra(extraCode);
                } else {
                    // Hide customization options
                    $option.find('.extra-customization').slideUp(300);
                    self.removeExtra(extraCode);
                }
            });

            // Option select change
            this.container.on('change', '.extra-option-select', function () {
                const $option = $(this).closest('.extra-option');
                const extraCode = $option.data('extra');
                self.updateExtraOptions(extraCode);
            });

            // Option click (expand/collapse)
            this.container.on('click', '.extra-option', function (e) {
                if (!$(e.target).is('input, select') && !$(e.target).is('.checkmark')) {
                    $(this).toggleClass('expanded');
                }
            });
        },

        /**
         * Add extra to selection
         *
         * @param {string} extraCode
         */
        addExtra: function (extraCode) {
            const extra = this.extras.find(e => e.code === extraCode);
            if (!extra) return;

            const extraData = {
                code: extraCode,
                price: extra.price
            };

            // Add options if available
            if (extra.hasOptions) {
                extraData.options = {};
            }

            this.selectedExtras.push(extraData);

            // Trigger onChange
            if (this.onChange) {
                this.onChange(this.selectedExtras);
            }

            // Track GA4 event
            if (window.dataLayer) {
                window.dataLayer.push({
                    'event': 'prescription_extra_added',
                    'extra': extraCode
                });
            }
        },

        /**
         * Remove extra from selection
         *
         * @param {string} extraCode
         */
        removeExtra: function (extraCode) {
            this.selectedExtras = this.selectedExtras.filter(e => e.code !== extraCode);

            // Trigger onChange
            if (this.onChange) {
                this.onChange(this.selectedExtras);
            }
        },

        /**
         * Update extra options
         *
         * @param {string} extraCode
         */
        updateExtraOptions: function (extraCode) {
            const extraData = this.selectedExtras.find(e => e.code === extraCode);
            if (!extraData) return;

            const $option = this.container.find(`.extra-option[data-extra="${extraCode}"]`);
            
            // Collect all option values
            $option.find('.extra-option-select').each(function () {
                const optionName = $(this).data('option');
                const optionValue = $(this).val();
                
                if (optionValue) {
                    extraData.options[optionName] = optionValue;
                }
            });

            // Trigger onChange
            if (this.onChange) {
                this.onChange(this.selectedExtras);
            }
        },

        /**
         * Validate step
         *
         * @return {boolean}
         */
        validate: function () {
            // Check if extras with options have all required options selected
            let isValid = true;

            this.selectedExtras.forEach(extraData => {
                const extra = this.extras.find(e => e.code === extraData.code);
                
                if (extra && extra.hasOptions) {
                    const $option = this.container.find(`.extra-option[data-extra="${extraData.code}"]`);
                    
                    $option.find('.extra-option-select').each(function () {
                        if ($(this).val() === '') {
                            isValid = false;
                            $(this).addClass('error');
                        } else {
                            $(this).removeClass('error');
                        }
                    });
                }
            });

            if (!isValid) {
                this.showError($t('Please select all required options for your chosen extras.'));
            }

            return isValid;
        },

        /**
         * Show error message
         *
         * @param {string} message
         */
        showError: function (message) {
            const $error = $('<div class="message error"></div>').text(message);
            this.container.find('.extras-intro').after($error);

            setTimeout(function () {
                $error.fadeOut(function () {
                    $(this).remove();
                });
            }, 3000);
        },

        /**
         * Get step data
         *
         * @return {Object}
         */
        getData: function () {
            return {
                extras: this.selectedExtras
            };
        }
    };
});
