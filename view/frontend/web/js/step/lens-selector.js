/**
 * Powerline PrescripcionModule - Lens Selector Step Component
 *
 * Renders the third step: lens material, design, and index selection
 * with dynamic filtering based on prescription data and compatibility checking
 *
 * @category  Powerline
 * @package   Powerline_PrescripcionModule
 */

define([
    'jquery',
    'mage/translate',
    'Powerline_PrescripcionModule/js/lens-visualizer'
], function ($, $t, lensVisualizer) {
    'use strict';

    return {
        /**
         * Initialize lens selector step
         *
         * @param {Object} container - jQuery container element
         * @param {Object} config - Configuration object
         * @param {Object} prescriptionData - Prescription data from step 2
         * @param {Function} onChange - Callback when selection changes
         */
        init: function (container, config, prescriptionData, onChange) {
            this.container = $(container);
            this.config = config;
            this.prescriptionData = prescriptionData;
            this.onChange = onChange;
            
            this.selection = {
                material: null,
                design: null,
                index: null
            };

            // Lens materials catalog
            this.materials = [
                {
                    code: 'CR39',
                    name: $t('CR-39 (Plastic)'),
                    description: $t('Standard plastic lens material. Affordable, lightweight, and good optical quality.'),
                    benefits: [$t('Affordable'), $t('Good scratch resistance'), $t('Lightweight')],
                    weight: 1.0,
                    minIndex: 1.50,
                    maxIndex: 1.50
                },
                {
                    code: 'POLYCARBONATE',
                    name: $t('Polycarbonate'),
                    description: $t('Impact-resistant material ideal for sports and children. Thinner than CR-39.'),
                    benefits: [$t('Impact resistant'), $t('UV protection'), $t('Lightweight'), $t('Thinner')],
                    weight: 0.85,
                    minIndex: 1.59,
                    maxIndex: 1.59
                },
                {
                    code: 'TRIVEX',
                    name: $t('Trivex'),
                    description: $t('Premium material combining lightness, impact resistance, and optical clarity.'),
                    benefits: [$t('Superior optics'), $t('Impact resistant'), $t('Very lightweight'), $t('UV protection')],
                    weight: 0.80,
                    minIndex: 1.53,
                    maxIndex: 1.53
                },
                {
                    code: 'HIGH_INDEX',
                    name: $t('High Index'),
                    description: $t('Extra-thin lenses for high prescriptions. Available in multiple index options.'),
                    benefits: [$t('Very thin'), $t('Aesthetic'), $t('UV protection'), $t('Lightweight')],
                    weight: 0.75,
                    minIndex: 1.60,
                    maxIndex: 1.74
                },
                {
                    code: 'GLASS',
                    name: $t('Mineral Glass'),
                    description: $t('Traditional glass lenses with excellent optical quality and scratch resistance.'),
                    benefits: [$t('Best optics'), $t('Scratch resistant'), $t('Durable')],
                    weight: 1.4,
                    minIndex: 1.50,
                    maxIndex: 1.90
                }
            ];

            // Lens designs catalog
            this.designs = [
                {
                    code: 'SPHERICAL',
                    name: $t('Spherical'),
                    description: $t('Traditional curved lens design. Standard option for most prescriptions.'),
                    compatibleTypes: ['monofocal', 'bifocal', 'progressive', 'occupational', 'reading'],
                    maxSph: 8.0
                },
                {
                    code: 'ASPHERIC',
                    name: $t('Aspheric'),
                    description: $t('Flatter profile for better aesthetics and reduced distortion in peripheral vision.'),
                    compatibleTypes: ['monofocal', 'bifocal', 'progressive', 'occupational', 'reading'],
                    maxSph: 12.0
                },
                {
                    code: 'DOUBLE_ASPHERIC',
                    name: $t('Double Aspheric'),
                    description: $t('Both surfaces optimized for thinnest profile and best optics. Premium option.'),
                    compatibleTypes: ['monofocal', 'progressive', 'occupational'],
                    maxSph: 20.0
                },
                {
                    code: 'FREEFORM',
                    name: $t('Freeform Progressive'),
                    description: $t('Digitally surfaced progressive lenses with wider fields of vision and minimal distortion.'),
                    compatibleTypes: ['progressive', 'occupational'],
                    maxSph: 20.0,
                    premium: true
                },
                {
                    code: 'PERSONALIZED',
                    name: $t('Personalized Freeform'),
                    description: $t('Custom-made lenses considering frame shape, wearing position, and visual habits.'),
                    compatibleTypes: ['progressive', 'occupational'],
                    maxSph: 20.0,
                    premium: true
                }
            ];

            // Index options for HIGH_INDEX material
            this.indexOptions = [
                { value: 1.60, label: '1.60', description: $t('Up to 20% thinner than standard') },
                { value: 1.67, label: '1.67', description: $t('Up to 40% thinner than standard') },
                { value: 1.74, label: '1.74', description: $t('Up to 50% thinner than standard - thinnest available') }
            ];

            this.render();
            this.attachEvents();
        },

        /**
         * Render lens selector interface
         */
        render: function () {
            const html = `
                <div class="lens-selector-wrapper">
                    <div class="lens-visualizer-container">
                        <div id="lens-visualizer"></div>
                    </div>

                    <div class="lens-section material-section">
                        <h3>${$t('1. Select Lens Material')}</h3>
                        <div class="material-cards">
                            ${this.materials.map(m => this.renderMaterialCard(m)).join('')}
                        </div>
                    </div>

                    <div class="lens-section design-section" style="display: none;">
                        <h3>${$t('2. Select Lens Design')}</h3>
                        <div class="design-cards">
                            ${this.designs.map(d => this.renderDesignCard(d)).join('')}
                        </div>
                    </div>

                    <div class="lens-section index-section" style="display: none;">
                        <h3>${$t('3. Select Lens Index')}</h3>
                        <p class="section-description">${$t('Higher index means thinner lenses. Recommended for prescriptions over ±6.00.')}</p>
                        <div class="index-options">
                            ${this.indexOptions.map(i => this.renderIndexOption(i)).join('')}
                        </div>
                    </div>

                    <div class="lens-recommendation" style="display: none;">
                        <div class="recommendation-content"></div>
                    </div>
                </div>
            `;

            this.container.html(html);
            this._initializeVisualizer();
        },

        /**
         * Initialize lens visualizer
         */
        _initializeVisualizer: function () {
            const visualizerContainer = this.container.find('#lens-visualizer');
            if (visualizerContainer.length) {
                lensVisualizer.init(visualizerContainer, {});
                this._updateVisualizer();
            }
        },

        /**
         * Update visualizer with current selections
         */
        _updateVisualizer: function () {
            if (typeof lensVisualizer.update === 'function') {
                lensVisualizer.update({
                    material: this.selectedMaterial,
                    design: this.selectedDesign,
                    prescription: this.prescriptionData,
                    treatments: [], // Will be updated from treatments step
                    index: this.selectedIndex
                });
            }
        },

        /**
         * Render material card
         *
         * @param {Object} material
         * @return {string} HTML
         */
        renderMaterialCard: function (material) {
            const weightClass = material.weight < 1 ? 'lightweight' : 'standard';
            
            return `
                <div class="lens-card material-card" data-material="${material.code}">
                    <div class="card-header">
                        <h4>${material.name}</h4>
                        <span class="badge ${weightClass}">
                            ${material.weight < 1 ? $t('Lightweight') : $t('Standard')}
                        </span>
                    </div>
                    <div class="card-description">
                        <p>${material.description}</p>
                    </div>
                    <div class="card-benefits">
                        <ul>
                            ${material.benefits.map(b => `<li>✓ ${b}</li>`).join('')}
                        </ul>
                    </div>
                    <div class="card-footer">
                        <span class="index-range">${$t('Index')}: ${material.minIndex}${material.maxIndex !== material.minIndex ? ' - ' + material.maxIndex : ''}</span>
                    </div>
                </div>
            `;
        },

        /**
         * Render design card
         *
         * @param {Object} design
         * @return {string} HTML
         */
        renderDesignCard: function (design) {
            return `
                <div class="lens-card design-card" data-design="${design.code}">
                    <div class="card-header">
                        <h4>${design.name}</h4>
                        ${design.premium ? `<span class="badge premium">${$t('Premium')}</span>` : ''}
                    </div>
                    <div class="card-description">
                        <p>${design.description}</p>
                    </div>
                    <div class="card-footer">
                        <span class="compatibility">${$t('Compatible with your prescription')}</span>
                    </div>
                </div>
            `;
        },

        /**
         * Render index option
         *
         * @param {Object} index
         * @return {string} HTML
         */
        renderIndexOption: function (index) {
            return `
                <div class="index-option" data-index="${index.value}">
                    <div class="option-header">
                        <input type="radio" name="lens_index" value="${index.value}" id="index_${index.value}">
                        <label for="index_${index.value}">
                            <span class="index-value">${$t('Index')} ${index.label}</span>
                        </label>
                    </div>
                    <div class="option-description">${index.description}</div>
                </div>
            `;
        },

        /**
         * Attach event listeners
         */
        attachEvents: function () {
            const self = this;

            // Material selection
            this.container.on('click', '.material-card', function () {
                const material = $(this).data('material');
                self.selectMaterial(material);
            });

            // Design selection
            this.container.on('click', '.design-card:not(.disabled)', function () {
                const design = $(this).data('design');
                self.selectDesign(design);
            });

            // Index selection
            this.container.on('change', 'input[name="lens_index"]', function () {
                const index = parseFloat($(this).val());
                self.selectIndex(index);
            });
        },

        /**
         * Select material
         *
         * @param {string} materialCode
         */
        selectMaterial: function (materialCode) {
            // Update selection
            this.container.find('.material-card').removeClass('selected');
            this.container.find(`.material-card[data-material="${materialCode}"]`).addClass('selected');

            this.selection.material = materialCode;

            // Filter and show design section
            this.filterDesigns();
            this.container.find('.design-section').show();

            // Show/hide index section based on material
            const material = this.materials.find(m => m.code === materialCode);
            if (material && material.maxIndex > material.minIndex) {
                this.container.find('.index-section').show();
                // Auto-select middle index if only one option
                if (materialCode !== 'HIGH_INDEX') {
                    this.selection.index = material.minIndex;
                }
            } else {
                this.container.find('.index-section').hide();
                this.selection.index = material ? material.minIndex : null;
            }

            // Show recommendation
            this.showRecommendation();

            // Update visualizer
            this.selectedMaterial = materialCode;
            this._updateVisualizer();

            // Trigger onChange
            if (this.onChange) {
                this.onChange(this.selection);
            }

            // Track GA4 event
            if (window.dataLayer) {
                window.dataLayer.push({
                    'event': 'prescription_material_selected',
                    'material': materialCode
                });
            }
        },

        /**
         * Select design
         *
         * @param {string} designCode
         */
        selectDesign: function (designCode) {
            // Update selection
            this.container.find('.design-card').removeClass('selected');
            this.container.find(`.design-card[data-design="${designCode}"]`).addClass('selected');

            this.selection.design = designCode;

            // Update visualizer
            this.selectedDesign = designCode;
            this._updateVisualizer();

            // Trigger onChange
            if (this.onChange) {
                this.onChange(this.selection);
            }

            // Track GA4 event
            if (window.dataLayer) {
                window.dataLayer.push({
                    'event': 'prescription_design_selected',
                    'design': designCode
                });
            }
        },

        /**
         * Select index
         *
         * @param {number} indexValue
         */
        selectIndex: function (indexValue) {
            this.selection.index = indexValue;

            // Update visualizer
            this._updateVisualizer();

            // Trigger onChange
            if (this.onChange) {
                this.onChange(this.selection);
            }
        },

        /**
         * Filter designs based on use type and prescription
         */
        filterDesigns: function () {
            const useType = this.prescriptionData.use_type || 'monofocal';
            const maxSph = this.getMaxSphere();

            this.designs.forEach(design => {
                const $card = this.container.find(`.design-card[data-design="${design.code}"]`);
                
                // Check use type compatibility
                const isCompatibleType = design.compatibleTypes.indexOf(useType) !== -1;
                
                // Check prescription range compatibility
                const isCompatibleRange = Math.abs(maxSph) <= design.maxSph;

                if (isCompatibleType && isCompatibleRange) {
                    $card.removeClass('disabled');
                } else {
                    $card.addClass('disabled');
                    
                    if (!isCompatibleType) {
                        $card.find('.card-footer .compatibility').text(
                            $t('Not compatible with %1 lenses').replace('%1', useType)
                        );
                    } else if (!isCompatibleRange) {
                        $card.find('.card-footer .compatibility').text(
                            $t('Not suitable for your prescription range')
                        );
                    }
                }
            });
        },

        /**
         * Get maximum sphere value from prescription
         *
         * @return {number}
         */
        getMaxSphere: function () {
            let maxSph = 0;

            if (this.prescriptionData.od && this.prescriptionData.od.sph) {
                maxSph = Math.max(maxSph, Math.abs(this.prescriptionData.od.sph));
            }

            if (this.prescriptionData.oi && this.prescriptionData.oi.sph) {
                maxSph = Math.max(maxSph, Math.abs(this.prescriptionData.oi.sph));
            }

            return maxSph;
        },

        /**
         * Show recommendation based on prescription
         */
        showRecommendation: function () {
            const maxSph = this.getMaxSphere();
            let recommendation = '';

            if (maxSph >= 6.0) {
                recommendation = $t('Recommended: High index lenses for thinner, more aesthetic lenses with your prescription.');
            } else if (maxSph >= 4.0) {
                recommendation = $t('Consider: Aspheric design for improved aesthetics and comfort.');
            } else {
                recommendation = $t('Your prescription allows for standard lens options with excellent results.');
            }

            this.container.find('.lens-recommendation .recommendation-content').html(
                `<strong>${$t('Recommendation:')}</strong> ${recommendation}`
            );
            this.container.find('.lens-recommendation').show();
        },

        /**
         * Initialize lens visualizer
         */
        _initializeVisualizer: function () {
            const $visualizerContainer = this.container.find('.lens-visualization-container');
            if ($visualizerContainer.length && lensVisualizer) {
                this.visualizer = Object.create(lensVisualizer);
                this.visualizer.init($visualizerContainer, this.config || {});
                this._updateVisualizer();
            }
        },

        /**
         * Update lens visualizer with current selection
         */
        _updateVisualizer: function () {
            if (!this.visualizer) {
                return;
            }

            const lensData = {
                prescription: this.prescriptionData || {},
                material: this.selectedMaterial || this.selection.material || null,
                design: this.selectedDesign || this.selection.design || null,
                index: this.selection.index || null,
                treatments: []
            };

            this.visualizer.updateVisualization(lensData);
        },

        /**
         * Validate step
         *
         * @return {boolean}
         */
        validate: function () {
            if (!this.selection.material) {
                this.showError($t('Please select a lens material.'));
                return false;
            }

            if (!this.selection.design) {
                this.showError($t('Please select a lens design.'));
                return false;
            }

            const material = this.materials.find(m => m.code === this.selection.material);
            if (material && material.maxIndex > material.minIndex && !this.selection.index) {
                this.showError($t('Please select a lens index.'));
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
            this.container.prepend($error);

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
                lens_material: this.selection.material,
                lens_design: this.selection.design,
                lens_index: this.selection.index
            };
        }
    };
});
