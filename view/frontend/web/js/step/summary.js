/**
 * Powerline PrescripcionModule - Summary Step
 * 
 * Step 6: Summary with inline editing
 * Displays complete configuration summary with ability to edit any section
 * without navigating back
 */
define([
    'jquery',
    'mage/translate'
], function ($, $t) {
    'use strict';

    return {
        container: null,
        data: {},
        config: {},
        onChange: null,
        editCount: {},
        summaryStartTime: null,
        activeEditor: null,

        /**
         * Initialize summary step
         *
         * @param {jQuery} container
         * @param {Object} data - Current configuration data
         * @param {Object} config - Configuration settings
         * @param {Function} onChange - Callback when data changes
         */
        init: function (container, data, config, onChange) {
            this.container = container;
            this.data = data;
            this.config = config;
            this.onChange = onChange;
            this.editCount = {};
            this.summaryStartTime = Date.now();
            this.activeEditor = null;

            this.render();
            this.attachEvents();
        },

        /**
         * Render summary view
         */
        render: function () {
            const html = `
                <div class="summary-wrapper">
                    <div class="summary-header">
                        <h2>${$t('Configuration Summary')}</h2>
                        <p class="summary-subtitle">${$t('Review your prescription configuration. Click any section to edit.')}</p>
                    </div>

                    <!-- Prescription Section -->
                    <div class="summary-section" data-section="prescription">
                        <div class="section-header">
                            <h3>${$t('Prescription')}</h3>
                            <button type="button" class="btn-edit" data-action="edit-prescription">
                                <span class="icon">✏️</span>
                                <span class="text">${$t('Edit')}</span>
                            </button>
                        </div>
                        <div class="section-content" data-content="prescription">
                            ${this.renderPrescriptionContent()}
                        </div>
                        <div class="section-editor" data-editor="prescription" style="display: none;">
                            <!-- Editor will be populated dynamically -->
                        </div>
                    </div>

                    <!-- Use Type Section -->
                    <div class="summary-section" data-section="use_type">
                        <div class="section-header">
                            <h3>${$t('Use Type')}</h3>
                            <button type="button" class="btn-edit" data-action="edit-use_type">
                                <span class="icon">✏️</span>
                                <span class="text">${$t('Edit')}</span>
                            </button>
                        </div>
                        <div class="section-content" data-content="use_type">
                            ${this.renderUseTypeContent()}
                        </div>
                        <div class="section-editor" data-editor="use_type" style="display: none;">
                            <!-- Editor will be populated dynamically -->
                        </div>
                    </div>

                    <!-- Lens Section -->
                    <div class="summary-section" data-section="lens">
                        <div class="section-header">
                            <h3>${$t('Lens Configuration')}</h3>
                            <button type="button" class="btn-edit" data-action="edit-lens">
                                <span class="icon">✏️</span>
                                <span class="text">${$t('Edit')}</span>
                            </button>
                        </div>
                        <div class="section-content" data-content="lens">
                            ${this.renderLensContent()}
                        </div>
                        <div class="section-editor" data-editor="lens" style="display: none;">
                            <!-- Editor will be populated dynamically -->
                        </div>
                    </div>

                    <!-- Treatments Section -->
                    <div class="summary-section" data-section="treatments">
                        <div class="section-header">
                            <h3>${$t('Treatments')}</h3>
                            <button type="button" class="btn-edit" data-action="edit-treatments">
                                <span class="icon">✏️</span>
                                <span class="text">${$t('Edit')}</span>
                            </button>
                        </div>
                        <div class="section-content" data-content="treatments">
                            ${this.renderTreatmentsContent()}
                        </div>
                        <div class="section-editor" data-editor="treatments" style="display: none;">
                            <!-- Editor will be populated dynamically -->
                        </div>
                    </div>

                    <!-- Price Summary -->
                    <div class="summary-price">
                        <div class="price-breakdown">
                            <div class="price-line">
                                <span>${$t('Base Price')}</span>
                                <span class="price-value" data-price="base">€0.00</span>
                            </div>
                            <div class="price-line">
                                <span>${$t('Treatments')}</span>
                                <span class="price-value" data-price="treatments">€0.00</span>
                            </div>
                            <div class="price-line total">
                                <span>${$t('Total')}</span>
                                <span class="price-value" data-price="total">€0.00</span>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            this.container.html(html);
            this.updatePrice();
        },

        /**
         * Render prescription content
         */
        renderPrescriptionContent: function () {
            const od = this.data.prescription?.od || {};
            const oi = this.data.prescription?.oi || {};

            return `
                <div class="prescription-summary">
                    <div class="eye-data">
                        <strong>${$t('Right Eye (OD)')}</strong>
                        <p>SPH: ${this.formatValue(od.sph, 'sph')}, CYL: ${this.formatValue(od.cyl, 'cyl')}, AXIS: ${od.axis || 0}°</p>
                        ${od.add ? `<p>ADD: ${this.formatValue(od.add, 'add')}</p>` : ''}
                    </div>
                    <div class="eye-data">
                        <strong>${$t('Left Eye (OI)')}</strong>
                        <p>SPH: ${this.formatValue(oi.sph, 'sph')}, CYL: ${this.formatValue(oi.cyl, 'cyl')}, AXIS: ${oi.axis || 0}°</p>
                        ${oi.add ? `<p>ADD: ${this.formatValue(oi.add, 'add')}</p>` : ''}
                    </div>
                    ${this.data.prescription?.pd ? `<p><strong>PD:</strong> ${this.data.prescription.pd}mm</p>` : ''}
                </div>
            `;
        },

        /**
         * Render use type content
         */
        renderUseTypeContent: function () {
            const useType = this.data.use_type || 'monofocal';
            const labels = {
                'monofocal': $t('Distance Vision'),
                'progressive': $t('Progressive (Near & Far)'),
                'bifocal': $t('Bifocal'),
                'reading': $t('Reading'),
                'occupational': $t('Occupational/Office')
            };

            return `<p class="use-type-label">${labels[useType] || useType}</p>`;
        },

        /**
         * Render lens content
         */
        renderLensContent: function () {
            const lens = this.data.lens || {};
            const materialLabels = {
                'CR39': $t('CR-39 Standard'),
                'POLYCARBONATE': $t('Polycarbonate'),
                'TRIVEX': $t('Trivex'),
                'HIGH_INDEX': $t('High Index'),
                'GLASS': $t('Glass')
            };
            const designLabels = {
                'SPHERICAL': $t('Spherical'),
                'ASPHERIC': $t('Aspheric'),
                'DOUBLE_ASPHERIC': $t('Double Aspheric'),
                'LENTICULAR': $t('Lenticular'),
                'ATORIC': $t('Atoric')
            };

            return `
                <div class="lens-summary">
                    <p><strong>${$t('Material:')}</strong> ${materialLabels[lens.material] || lens.material}</p>
                    <p><strong>${$t('Design:')}</strong> ${designLabels[lens.design] || lens.design}</p>
                    ${lens.index ? `<p><strong>${$t('Index:')}</strong> ${lens.index}</p>` : ''}
                </div>
            `;
        },

        /**
         * Render treatments content
         */
        renderTreatmentsContent: function () {
            const treatments = this.data.treatments || [];
            
            if (treatments.length === 0) {
                return `<p class="no-treatments">${$t('No treatments selected')}</p>`;
            }

            const treatmentLabels = {
                'AR_COATING': $t('Anti-Reflective Coating'),
                'BLUE_LIGHT': $t('Blue Light Filter'),
                'PHOTOCHROMIC': $t('Photochromic'),
                'POLARIZED': $t('Polarized'),
                'HARD_COAT': $t('Hard Coating'),
                'UV_PROTECTION': $t('UV Protection'),
                'MIRROR': $t('Mirror Coating'),
                'HYDROPHOBIC': $t('Hydrophobic'),
                'OLEOPHOBIC': $t('Oleophobic')
            };

            return `
                <ul class="treatments-list">
                    ${treatments.map(t => `<li>✓ ${treatmentLabels[t] || t}</li>`).join('')}
                </ul>
            `;
        },

        /**
         * Attach event handlers
         */
        attachEvents: function () {
            // Edit buttons
            this.container.on('click', '.btn-edit', (e) => {
                const section = $(e.currentTarget).data('action').replace('edit-', '');
                this.editSection(section);
            });

            // Save buttons (delegated, created dynamically)
            this.container.on('click', '.btn-save', (e) => {
                e.preventDefault();
                const section = $(e.currentTarget).closest('.section-editor').data('editor');
                this.saveSection(section);
            });

            // Cancel buttons
            this.container.on('click', '.btn-cancel', (e) => {
                e.preventDefault();
                const section = $(e.currentTarget).closest('.section-editor').data('editor');
                this.cancelEdit(section);
            });

            // Real-time validation on input
            this.container.on('input', '.inline-edit-form input, .inline-edit-form select', (e) => {
                this.validateField($(e.target));
            });
        },

        /**
         * Edit section - expand inline editor
         */
        editSection: function (section) {
            // Close any active editor
            if (this.activeEditor && this.activeEditor !== section) {
                this.cancelEdit(this.activeEditor);
            }

            const $section = this.container.find(`[data-section="${section}"]`);
            const $content = $section.find(`[data-content="${section}"]`);
            const $editor = $section.find(`[data-editor="${section}"]`);

            // Populate editor
            this.populateEditor(section, $editor);

            // Animate transition
            $content.slideUp(300);
            $editor.slideDown(300);

            this.activeEditor = section;

            // Track edit action
            this.trackEdit(section, 'opened');
        },

        /**
         * Populate editor with current data
         */
        populateEditor: function (section, $editor) {
            let html = '';

            switch (section) {
                case 'prescription':
                    html = this.renderPrescriptionEditor();
                    break;
                case 'use_type':
                    html = this.renderUseTypeEditor();
                    break;
                case 'lens':
                    html = this.renderLensEditor();
                    break;
                case 'treatments':
                    html = this.renderTreatmentsEditor();
                    break;
            }

            $editor.html(html);
        },

        /**
         * Render prescription editor
         */
        renderPrescriptionEditor: function () {
            const od = this.data.prescription?.od || {};
            const oi = this.data.prescription?.oi || {};
            const pd = this.data.prescription?.pd || '';

            return `
                <form class="inline-edit-form prescription-form">
                    <div class="form-grid">
                        <div class="form-section">
                            <h4>${$t('Right Eye (OD)')}</h4>
                            <div class="form-row">
                                <label>SPH</label>
                                <input type="number" name="od_sph" value="${od.sph || 0}" step="0.25" min="-20" max="20" required>
                                <span class="field-feedback"></span>
                            </div>
                            <div class="form-row">
                                <label>CYL</label>
                                <input type="number" name="od_cyl" value="${od.cyl || 0}" step="0.25" min="-8" max="0" required>
                                <span class="field-feedback"></span>
                            </div>
                            <div class="form-row">
                                <label>AXIS</label>
                                <input type="number" name="od_axis" value="${od.axis || 0}" step="1" min="0" max="180" required>
                                <span class="field-feedback"></span>
                            </div>
                            ${this.requiresAddition() ? `
                            <div class="form-row">
                                <label>ADD</label>
                                <input type="number" name="od_add" value="${od.add || 0}" step="0.25" min="0.75" max="3.50">
                                <span class="field-feedback"></span>
                            </div>` : ''}
                        </div>
                        <div class="form-section">
                            <h4>${$t('Left Eye (OI)')}</h4>
                            <div class="form-row">
                                <label>SPH</label>
                                <input type="number" name="oi_sph" value="${oi.sph || 0}" step="0.25" min="-20" max="20" required>
                                <span class="field-feedback"></span>
                            </div>
                            <div class="form-row">
                                <label>CYL</label>
                                <input type="number" name="oi_cyl" value="${oi.cyl || 0}" step="0.25" min="-8" max="0" required>
                                <span class="field-feedback"></span>
                            </div>
                            <div class="form-row">
                                <label>AXIS</label>
                                <input type="number" name="oi_axis" value="${oi.axis || 0}" step="1" min="0" max="180" required>
                                <span class="field-feedback"></span>
                            </div>
                            ${this.requiresAddition() ? `
                            <div class="form-row">
                                <label>ADD</label>
                                <input type="number" name="oi_add" value="${oi.add || 0}" step="0.25" min="0.75" max="3.50">
                                <span class="field-feedback"></span>
                            </div>` : ''}
                        </div>
                        <div class="form-section full-width">
                            <div class="form-row">
                                <label>PD (mm)</label>
                                <input type="number" name="pd" value="${pd}" step="0.5" min="50" max="80">
                                <span class="field-feedback"></span>
                            </div>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn-save primary">${$t('Save Changes')}</button>
                        <button type="button" class="btn-cancel">${$t('Cancel')}</button>
                    </div>
                </form>
            `;
        },

        /**
         * Render use type editor
         */
        renderUseTypeEditor: function () {
            const currentUseType = this.data.use_type || 'monofocal';

            return `
                <form class="inline-edit-form use-type-form">
                    <div class="form-row">
                        <label>${$t('Select Use Type')}</label>
                        <select name="use_type" required>
                            <option value="monofocal" ${currentUseType === 'monofocal' ? 'selected' : ''}>${$t('Distance Vision')}</option>
                            <option value="progressive" ${currentUseType === 'progressive' ? 'selected' : ''}>${$t('Progressive (Near & Far)')}</option>
                            <option value="bifocal" ${currentUseType === 'bifocal' ? 'selected' : ''}>${$t('Bifocal')}</option>
                            <option value="reading" ${currentUseType === 'reading' ? 'selected' : ''}>${$t('Reading')}</option>
                            <option value="occupational" ${currentUseType === 'occupational' ? 'selected' : ''}>${$t('Occupational/Office')}</option>
                        </select>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn-save primary">${$t('Save Changes')}</button>
                        <button type="button" class="btn-cancel">${$t('Cancel')}</button>
                    </div>
                </form>
            `;
        },

        /**
         * Render lens editor
         */
        renderLensEditor: function () {
            const lens = this.data.lens || {};

            return `
                <form class="inline-edit-form lens-form">
                    <div class="form-row">
                        <label>${$t('Material')}</label>
                        <select name="material" required>
                            <option value="CR39" ${lens.material === 'CR39' ? 'selected' : ''}>${$t('CR-39 Standard')}</option>
                            <option value="POLYCARBONATE" ${lens.material === 'POLYCARBONATE' ? 'selected' : ''}>${$t('Polycarbonate')}</option>
                            <option value="TRIVEX" ${lens.material === 'TRIVEX' ? 'selected' : ''}>${$t('Trivex')}</option>
                            <option value="HIGH_INDEX" ${lens.material === 'HIGH_INDEX' ? 'selected' : ''}>${$t('High Index')}</option>
                            <option value="GLASS" ${lens.material === 'GLASS' ? 'selected' : ''}>${$t('Glass')}</option>
                        </select>
                    </div>
                    <div class="form-row">
                        <label>${$t('Design')}</label>
                        <select name="design" required>
                            <option value="SPHERICAL" ${lens.design === 'SPHERICAL' ? 'selected' : ''}>${$t('Spherical')}</option>
                            <option value="ASPHERIC" ${lens.design === 'ASPHERIC' ? 'selected' : ''}>${$t('Aspheric')}</option>
                            <option value="DOUBLE_ASPHERIC" ${lens.design === 'DOUBLE_ASPHERIC' ? 'selected' : ''}>${$t('Double Aspheric')}</option>
                            <option value="LENTICULAR" ${lens.design === 'LENTICULAR' ? 'selected' : ''}>${$t('Lenticular')}</option>
                            <option value="ATORIC" ${lens.design === 'ATORIC' ? 'selected' : ''}>${$t('Atoric')}</option>
                        </select>
                    </div>
                    ${lens.material === 'HIGH_INDEX' ? `
                    <div class="form-row">
                        <label>${$t('Index')}</label>
                        <select name="index">
                            <option value="1.60" ${lens.index === '1.60' ? 'selected' : ''}>1.60</option>
                            <option value="1.67" ${lens.index === '1.67' ? 'selected' : ''}>1.67</option>
                            <option value="1.74" ${lens.index === '1.74' ? 'selected' : ''}>1.74</option>
                        </select>
                    </div>` : ''}
                    <div class="form-actions">
                        <button type="submit" class="btn-save primary">${$t('Save Changes')}</button>
                        <button type="button" class="btn-cancel">${$t('Cancel')}</button>
                    </div>
                </form>
            `;
        },

        /**
         * Render treatments editor
         */
        renderTreatmentsEditor: function () {
            const currentTreatments = this.data.treatments || [];

            const treatments = [
                { code: 'AR_COATING', label: $t('Anti-Reflective Coating') },
                { code: 'BLUE_LIGHT', label: $t('Blue Light Filter') },
                { code: 'PHOTOCHROMIC', label: $t('Photochromic') },
                { code: 'POLARIZED', label: $t('Polarized') },
                { code: 'HARD_COAT', label: $t('Hard Coating') },
                { code: 'UV_PROTECTION', label: $t('UV Protection') },
                { code: 'MIRROR', label: $t('Mirror Coating') },
                { code: 'HYDROPHOBIC', label: $t('Hydrophobic') },
                { code: 'OLEOPHOBIC', label: $t('Oleophobic') }
            ];

            return `
                <form class="inline-edit-form treatments-form">
                    <div class="treatments-checkboxes">
                        ${treatments.map(t => `
                            <div class="checkbox-row">
                                <label>
                                    <input type="checkbox" name="treatments" value="${t.code}" ${currentTreatments.includes(t.code) ? 'checked' : ''}>
                                    <span>${t.label}</span>
                                </label>
                            </div>
                        `).join('')}
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn-save primary">${$t('Save Changes')}</button>
                        <button type="button" class="btn-cancel">${$t('Cancel')}</button>
                    </div>
                </form>
            `;
        },

        /**
         * Save section changes
         */
        saveSection: function (section) {
            const $editor = this.container.find(`[data-editor="${section}"]`);
            const $form = $editor.find('.inline-edit-form');

            // Validate all fields
            if (!this.validateForm($form)) {
                this.showError($t('Please correct the errors before saving.'));
                return;
            }

            // Collect form data
            const formData = this.collectFormData($form, section);

            // Track changes
            const changes = this.detectChanges(section, formData);

            // Update data
            this.updateData(section, formData);

            // Recalculate price
            this.updatePrice();

            // Update content view
            this.updateSectionContent(section);

            // Collapse editor
            this.closeEditor(section);

            // Track edit
            this.trackEdit(section, 'saved', changes);

            // Trigger onChange callback
            if (this.onChange) {
                this.onChange(this.data);
            }
        },

        /**
         * Cancel edit
         */
        cancelEdit: function (section) {
            this.closeEditor(section);
            this.trackEdit(section, 'cancelled');
        },

        /**
         * Close editor and show content
         */
        closeEditor: function (section) {
            const $section = this.container.find(`[data-section="${section}"]`);
            const $content = $section.find(`[data-content="${section}"]`);
            const $editor = $section.find(`[data-editor="${section}"]`);

            $editor.slideUp(300);
            $content.slideDown(300);

            this.activeEditor = null;
        },

        /**
         * Validate field in real-time
         */
        validateField: function ($field) {
            const name = $field.attr('name');
            const value = $field.val();
            const min = parseFloat($field.attr('min'));
            const max = parseFloat($field.attr('max'));
            const required = $field.attr('required') !== undefined;

            let isValid = true;
            let message = '';

            // Required check
            if (required && !value) {
                isValid = false;
                message = $t('This field is required');
            }
            // Range check for numbers
            else if ($field.attr('type') === 'number') {
                const numValue = parseFloat(value);
                if (!isNaN(min) && numValue < min) {
                    isValid = false;
                    message = $t('Value must be at least %1').replace('%1', min);
                } else if (!isNaN(max) && numValue > max) {
                    isValid = false;
                    message = $t('Value must be at most %1').replace('%1', max);
                }
            }

            // Update field state
            const $feedback = $field.siblings('.field-feedback');
            if (isValid) {
                $field.removeClass('error').addClass('valid');
                $feedback.removeClass('error').addClass('success').text('✓');
            } else {
                $field.removeClass('valid').addClass('error');
                $feedback.removeClass('success').addClass('error').text(message);
            }

            return isValid;
        },

        /**
         * Validate entire form
         */
        validateForm: function ($form) {
            let isValid = true;
            
            $form.find('input[required], select[required]').each((i, field) => {
                if (!this.validateField($(field))) {
                    isValid = false;
                }
            });

            return isValid;
        },

        /**
         * Collect form data
         */
        collectFormData: function ($form, section) {
            const formData = {};

            if (section === 'prescription') {
                formData.od = {
                    sph: parseFloat($form.find('[name="od_sph"]').val()),
                    cyl: parseFloat($form.find('[name="od_cyl"]').val()),
                    axis: parseInt($form.find('[name="od_axis"]').val())
                };
                formData.oi = {
                    sph: parseFloat($form.find('[name="oi_sph"]').val()),
                    cyl: parseFloat($form.find('[name="oi_cyl"]').val()),
                    axis: parseInt($form.find('[name="oi_axis"]').val())
                };
                if ($form.find('[name="od_add"]').length) {
                    formData.od.add = parseFloat($form.find('[name="od_add"]').val());
                    formData.oi.add = parseFloat($form.find('[name="oi_add"]').val());
                }
                const pd = $form.find('[name="pd"]').val();
                if (pd) {
                    formData.pd = parseFloat(pd);
                }
            } else if (section === 'use_type') {
                formData.use_type = $form.find('[name="use_type"]').val();
            } else if (section === 'lens') {
                formData.material = $form.find('[name="material"]').val();
                formData.design = $form.find('[name="design"]').val();
                const index = $form.find('[name="index"]').val();
                if (index) {
                    formData.index = index;
                }
            } else if (section === 'treatments') {
                formData.treatments = [];
                $form.find('[name="treatments"]:checked').each((i, checkbox) => {
                    formData.treatments.push($(checkbox).val());
                });
            }

            return formData;
        },

        /**
         * Detect changes between old and new data
         */
        detectChanges: function (section, newData) {
            const changes = [];
            
            if (section === 'prescription') {
                const oldPrescription = this.data.prescription || {};
                if (JSON.stringify(oldPrescription) !== JSON.stringify(newData)) {
                    changes.push('prescription');
                }
            } else if (section === 'use_type') {
                if (this.data.use_type !== newData.use_type) {
                    changes.push('use_type');
                }
            } else if (section === 'lens') {
                const oldLens = this.data.lens || {};
                Object.keys(newData).forEach(key => {
                    if (oldLens[key] !== newData[key]) {
                        changes.push(key);
                    }
                });
            } else if (section === 'treatments') {
                const oldTreatments = this.data.treatments || [];
                if (JSON.stringify(oldTreatments.sort()) !== JSON.stringify(newData.treatments.sort())) {
                    changes.push('treatments');
                }
            }

            return changes;
        },

        /**
         * Update internal data
         */
        updateData: function (section, formData) {
            if (section === 'prescription') {
                this.data.prescription = formData;
            } else if (section === 'use_type') {
                this.data.use_type = formData.use_type;
            } else if (section === 'lens') {
                this.data.lens = this.data.lens || {};
                Object.assign(this.data.lens, formData);
            } else if (section === 'treatments') {
                this.data.treatments = formData.treatments;
            }
        },

        /**
         * Update section content display
         */
        updateSectionContent: function (section) {
            const $content = this.container.find(`[data-content="${section}"]`);
            let html = '';

            switch (section) {
                case 'prescription':
                    html = this.renderPrescriptionContent();
                    break;
                case 'use_type':
                    html = this.renderUseTypeContent();
                    break;
                case 'lens':
                    html = this.renderLensContent();
                    break;
                case 'treatments':
                    html = this.renderTreatmentsContent();
                    break;
            }

            $content.html(html);
        },

        /**
         * Update price display
         */
        updatePrice: function () {
            // Call price calculation endpoint
            const priceEndpoint = this.config.endpoints?.price;
            if (!priceEndpoint) {
                return;
            }

            $.ajax({
                url: priceEndpoint,
                method: 'POST',
                data: JSON.stringify(this.data),
                contentType: 'application/json',
                dataType: 'json',
                success: (response) => {
                    if (response.success) {
                        this.container.find('[data-price="base"]').text(this.formatPrice(response.base_price));
                        this.container.find('[data-price="treatments"]').text(this.formatPrice(response.treatments_price));
                        this.container.find('[data-price="total"]').text(this.formatPrice(response.total_price));
                    }
                },
                error: (xhr, status, error) => {
                    console.error('Price calculation failed:', error);
                }
            });
        },

        /**
         * Track edit action in GA4
         */
        trackEdit: function (section, action, changes) {
            if (!this.editCount[section]) {
                this.editCount[section] = 0;
            }

            if (action === 'saved') {
                this.editCount[section]++;
            }

            if (window.dataLayer) {
                window.dataLayer.push({
                    'event': 'prescription_summary_edit',
                    'section': section,
                    'action': action,
                    'edit_count': this.editCount[section],
                    'fields_changed': changes || [],
                    'time_in_summary': Math.round((Date.now() - this.summaryStartTime) / 1000)
                });
            }
        },

        /**
         * Check if use type requires addition
         */
        requiresAddition: function () {
            const useType = this.data.use_type;
            return ['progressive', 'bifocal', 'occupational'].includes(useType);
        },

        /**
         * Format prescription value
         */
        formatValue: function (value, type) {
            if (value === undefined || value === null) {
                return '0.00';
            }

            const num = parseFloat(value);
            if (type === 'sph' || type === 'cyl' || type === 'add') {
                return (num >= 0 ? '+' : '') + num.toFixed(2);
            }

            return num.toFixed(2);
        },

        /**
         * Format price
         */
        formatPrice: function (price) {
            return '€' + parseFloat(price).toFixed(2);
        },

        /**
         * Show error message
         */
        showError: function (message) {
            // TODO: Implement toast notification
            alert(message);
        },

        /**
         * Validate step
         */
        validate: function () {
            // Summary step is always valid if we reached here
            return true;
        },

        /**
         * Get current data
         */
        getData: function () {
            return this.data;
        }
    };
});
