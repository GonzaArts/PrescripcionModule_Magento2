/**
 * Powerline PrescripcionModule - Use Type Step Component
 *
 * Renders the first step of the prescription configurator:
 * selection of lens use type (monofocal, progressive, etc.)
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
         * Initialize use type step
         *
         * @param {Object} container - jQuery container element
         * @param {Object} config - Configuration object
         * @param {Function} onSelect - Callback when use type is selected
         */
        init: function (container, config, onSelect) {
            this.container = $(container);
            this.config = config;
            this.onSelect = onSelect;
            this.selectedUseType = null;

            this.render();
            this.attachEvents();
        },

        /**
         * Render use type options
         */
        render: function () {
            const useTypes = this.config.use_types || [];
            
            const html = `
                <div class="use-type-grid">
                    ${useTypes.map(type => this.renderUseTypeCard(type)).join('')}
                </div>
                <div class="use-type-description">
                    <div class="description-content"></div>
                </div>
            `;

            this.container.html(html);
        },

        /**
         * Render individual use type card
         *
         * @param {Object} type - Use type object {value, label}
         * @return {string} HTML
         */
        renderUseTypeCard: function (type) {
            const descriptions = {
                'monofocal': $t('For distance or near vision only. Single prescription for all distances.'),
                'bifocal': $t('Two vision zones: distance (top) and near (bottom). Visible separation line.'),
                'progressive': $t('Gradual transition from distance to near vision. No visible lines.'),
                'occupational': $t('Optimized for intermediate and near distances. Ideal for office work.'),
                'reading': $t('Designed exclusively for near vision tasks like reading and close work.')
            };

            const icons = {
                'monofocal': '👁️',
                'bifocal': '👓',
                'progressive': '🔄',
                'occupational': '💼',
                'reading': '📖'
            };

            return `
                <div class="use-type-card" data-use-type="${type.value}">
                    <div class="card-icon">${icons[type.value] || '👓'}</div>
                    <div class="card-label">${type.label}</div>
                    <div class="card-description">${descriptions[type.value] || ''}</div>
                    <div class="card-check">✓</div>
                </div>
            `;
        },

        /**
         * Attach event listeners
         */
        attachEvents: function () {
            const self = this;

            // Card click event
            this.container.on('click', '.use-type-card', function () {
                const $card = $(this);
                const useType = $card.data('use-type');

                // Update selection
                self.selectUseType(useType);
            });

            // Card hover event - show extended description
            this.container.on('mouseenter', '.use-type-card', function () {
                const $card = $(this);
                const useType = $card.data('use-type');
                self.showDescription(useType);
            });

            this.container.on('mouseleave', '.use-type-card', function () {
                if (!self.selectedUseType) {
                    self.hideDescription();
                }
            });
        },

        /**
         * Select use type
         *
         * @param {string} useType
         */
        selectUseType: function (useType) {
            // Remove previous selection
            this.container.find('.use-type-card').removeClass('selected');

            // Add new selection
            const $card = this.container.find(`.use-type-card[data-use-type="${useType}"]`);
            $card.addClass('selected');

            this.selectedUseType = useType;
            this.showDescription(useType);

            // Trigger callback
            if (this.onSelect) {
                this.onSelect(useType);
            }

            // Track GA4 event
            if (window.dataLayer) {
                window.dataLayer.push({
                    'event': 'prescription_use_type_selected',
                    'use_type': useType
                });
            }
        },

        /**
         * Show extended description for use type
         *
         * @param {string} useType
         */
        showDescription: function (useType) {
            const extendedDescriptions = {
                'monofocal': $t('Monofocal lenses correct vision at a single distance. They are the most common type for distance vision (myopia or hyperopia) or for reading. Simple, affordable, and provide clear vision at the prescribed distance.'),
                'bifocal': $t('Bifocal lenses have two distinct viewing zones separated by a visible line. The upper part corrects distance vision, while the lower segment is for near vision. Traditional solution that requires adaptation to the visible line.'),
                'progressive': $t('Progressive lenses offer a seamless transition from distance to near vision without visible lines. The most advanced and comfortable option for presbyopia, providing clear vision at all distances with a modern aesthetic.'),
                'occupational': $t('Occupational or office lenses are optimized for intermediate (60-80cm) and near (40cm) distances. Ideal for computer work and office tasks, providing a wider field of view than standard progressives at these distances.'),
                'reading': $t('Reading lenses are specifically designed for close-up tasks at 40cm distance. Perfect for reading, crafts, or any detailed work. Provide maximum clarity for near vision with a wide field of view.')
            };

            const $description = this.container.find('.description-content');
            $description.html(`
                <h4>${this.getLabelForUseType(useType)}</h4>
                <p>${extendedDescriptions[useType] || ''}</p>
            `);
            
            this.container.find('.use-type-description').addClass('visible');
        },

        /**
         * Hide description
         */
        hideDescription: function () {
            this.container.find('.use-type-description').removeClass('visible');
        },

        /**
         * Get label for use type
         *
         * @param {string} useType
         * @return {string}
         */
        getLabelForUseType: function (useType) {
            const type = this.config.use_types.find(t => t.value === useType);
            return type ? type.label : useType;
        },

        /**
         * Get current selection
         *
         * @return {string|null}
         */
        getSelection: function () {
            return this.selectedUseType;
        },

        /**
         * Validate step
         *
         * @return {boolean}
         */
        validate: function () {
            if (!this.selectedUseType) {
                this.showError($t('Please select a lens use type to continue.'));
                return false;
            }
            return true;
        },

        /**
         * Show error message
         *
         * @param {string} message
         */
        showError: function (message) {
            const $error = $('<div class="message error"></div>').text(message);
            this.container.find('.use-type-grid').before($error);

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
                use_type: this.selectedUseType
            };
        }
    };
});
