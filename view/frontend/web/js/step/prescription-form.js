/**
 * Powerline PrescripcionModule - Prescription Form Step Component
 *
 * Renders the second step: prescription data entry form for both eyes
 * with real-time validation, tooltips, and field dependencies
 *
 * Fields: SPH, CYL, AXIS, ADD, PRISM, PRISM_BASE, PD (mono/binocular), HEIGHT
 *
 * @category  Powerline
 * @package   Powerline_PrescripcionModule
 */

define([
    'jquery',
    'mage/translate',
    'Powerline_PrescripcionModule/js/upload-handler',
    'jquery/ui'
], function ($, $t, uploadHandler) {
    'use strict';

    return {
        /**
         * Initialize prescription form step
         *
         * @param {Object} container - jQuery container element
         * @param {Object} config - Configuration object
         * @param {string} useType - Selected use type
         * @param {Function} onChange - Callback when form changes
         */
        init: function (container, config, useType, onChange) {
            this.container = $(container);
            this.config = config;
            this.useType = useType;
            this.onChange = onChange;
            this.formData = {
                od: {},
                oi: {},
                pd: { type: 'binocular', value: null, od: null, oi: null },
                attachment_id: null,
                attachment_hash: null,
                attachment_filename: null,
                attachment_filepath: null
            };
            this.validationDebounceTimer = null;

            this.render();
            this.attachEvents();
            this._initializeUploadHandler();
        },

        /**
         * Render prescription form
         */
        render: function () {
            const html = `
                <div class="prescription-form-wrapper">
                    <div class="form-instructions">
                        <p>${$t('Enter the prescription data for both eyes. All fields marked with * are required.')}</p>
                    </div>

                    <div class="prescription-table">
                        <table>
                            <thead>
                                <tr>
                                    <th class="field-label">${$t('Field')}</th>
                                    <th class="eye-column">
                                        ${$t('Right Eye (OD)')}
                                        <span class="tooltip-icon" data-tooltip="${$t('Right eye prescription values')}">ⓘ</span>
                                    </th>
                                    <th class="eye-column">
                                        ${$t('Left Eye (OI)')}
                                        <span class="tooltip-icon" data-tooltip="${$t('Left eye prescription values')}">ⓘ</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                ${this.renderFieldRow('sph', 'Sphere (SPH) *', 
                                    $t('Corrects near or farsightedness. Range: -20.00 to +20.00 in 0.25 steps'))}
                                ${this.renderFieldRow('cyl', 'Cylinder (CYL)', 
                                    $t('Corrects astigmatism. Range: -8.00 to +8.00 in 0.25 steps'))}
                                ${this.renderFieldRow('axis', 'Axis (AXIS)', 
                                    $t('Direction of astigmatism correction. Required if CYL is present. Range: 0-180°'), 'number', 1)}
                                ${this.renderAddFieldRow()}
                                ${this.renderFieldRow('prism', 'Prism', 
                                    $t('Corrects eye alignment. Range: 0-10 in 0.25 steps'))}
                                ${this.renderPrismBaseRow()}
                            </tbody>
                        </table>
                    </div>

                    ${this.renderPdSection()}
                    ${this.renderHeightSection()}
                    ${this.renderUploadSection()}
                    
                    <div class="validation-summary"></div>
                </div>
            `;

            this.container.html(html);
            this._initializeTooltips();
        },

        /**
         * Render standard field row
         *
         * @param {string} field - Field name
         * @param {string} label - Field label
         * @param {string} tooltip - Tooltip text
         * @param {string} inputType - Input type (number or text)
         * @param {number} step - Input step value
         * @return {string} HTML
         */
        renderFieldRow: function (field, label, tooltip, inputType = 'number', step = 0.25) {
            const range = this.config[field + '_range'] || {};
            const min = range.min !== undefined ? range.min : '';
            const max = range.max !== undefined ? range.max : '';

            return `
                <tr class="field-row" data-field="${field}">
                    <td class="field-label">
                        ${label}
                        <span class="tooltip-icon" data-tooltip="${tooltip}">ⓘ</span>
                    </td>
                    <td class="eye-column">
                        <input type="${inputType}" 
                               class="prescription-input" 
                               data-eye="od" 
                               data-field="${field}"
                               min="${min}" 
                               max="${max}" 
                               step="${step}"
                               placeholder="--">
                        <span class="field-error"></span>
                    </td>
                    <td class="eye-column">
                        <input type="${inputType}" 
                               class="prescription-input" 
                               data-eye="oi" 
                               data-field="${field}"
                               min="${min}" 
                               max="${max}" 
                               step="${step}"
                               placeholder="--">
                        <span class="field-error"></span>
                    </td>
                </tr>
            `;
        },

        /**
         * Render ADD field row (conditional based on use type)
         *
         * @return {string} HTML
         */
        renderAddFieldRow: function () {
            const requiresAdd = ['progressive', 'bifocal', 'occupational'].indexOf(this.useType) !== -1;
            const label = requiresAdd ? 'Addition (ADD) *' : 'Addition (ADD)';
            const tooltip = $t('Near vision addition. Required for progressive/bifocal lenses. Range: 0.25-4.00');

            return this.renderFieldRow('add', label, tooltip);
        },

        /**
         * Render prism base row
         *
         * @return {string} HTML
         */
        renderPrismBaseRow: function () {
            return `
                <tr class="field-row" data-field="prism_base">
                    <td class="field-label">
                        Prism Base
                        <span class="tooltip-icon" data-tooltip="${$t('Direction of prism correction. Required if PRISM is present.')}">ⓘ</span>
                    </td>
                    <td class="eye-column">
                        <select class="prescription-select" data-eye="od" data-field="prism_base">
                            <option value="">--</option>
                            <option value="UP">${$t('UP')}</option>
                            <option value="DOWN">${$t('DOWN')}</option>
                            <option value="IN">${$t('IN')}</option>
                            <option value="OUT">${$t('OUT')}</option>
                        </select>
                        <span class="field-error"></span>
                    </td>
                    <td class="eye-column">
                        <select class="prescription-select" data-eye="oi" data-field="prism_base">
                            <option value="">--</option>
                            <option value="UP">${$t('UP')}</option>
                            <option value="DOWN">${$t('DOWN')}</option>
                            <option value="IN">${$t('IN')}</option>
                            <option value="OUT">${$t('OUT')}</option>
                        </select>
                        <span class="field-error"></span>
                    </td>
                </tr>
            `;
        },

        /**
         * Render PD (Pupillary Distance) section
         *
         * @return {string} HTML
         */
        renderPdSection: function () {
            return `
                <div class="pd-section">
                    <h3>${$t('Pupillary Distance (PD) *')}</h3>
                    <div class="pd-type-selector">
                        <label class="pd-type-option">
                            <input type="radio" name="pd_type" value="binocular" checked>
                            <span>${$t('Binocular (single value)')}</span>
                        </label>
                        <label class="pd-type-option">
                            <input type="radio" name="pd_type" value="monocular">
                            <span>${$t('Monocular (separate values)')}</span>
                        </label>
                    </div>
                    
                    <div class="pd-input-binocular">
                        <label>
                            ${$t('PD (mm)')}
                            <span class="tooltip-icon" data-tooltip="${$t('Distance between pupils. Range: 20-80mm')}">ⓘ</span>
                        </label>
                        <input type="number" class="pd-input" data-pd-type="binocular" 
                               min="20" max="80" step="1" placeholder="62">
                        <span class="field-error"></span>
                    </div>
                    
                    <div class="pd-input-monocular" style="display: none;">
                        <div class="pd-monocular-field">
                            <label>${$t('PD OD (mm)')}</label>
                            <input type="number" class="pd-input" data-pd-type="od" 
                                   min="20" max="80" step="1" placeholder="31">
                            <span class="field-error"></span>
                        </div>
                        <div class="pd-monocular-field">
                            <label>${$t('PD OI (mm)')}</label>
                            <input type="number" class="pd-input" data-pd-type="oi" 
                                   min="20" max="80" step="1" placeholder="31">
                            <span class="field-error"></span>
                        </div>
                    </div>
                </div>
            `;
        },

        /**
         * Render height section
         *
         * @return {string} HTML
         */
        renderHeightSection: function () {
            return `
                <div class="height-section">
                    <h3>${$t('Fitting Height (Optional)')}</h3>
                    <p class="field-description">${$t('Vertical distance from the bottom of the lens to the pupil center.')}</p>
                    <div class="height-inputs">
                        <div class="height-field">
                            <label>${$t('Height OD (mm)')}</label>
                            <input type="number" class="height-input" data-eye="od" 
                                   min="10" max="40" step="0.5" placeholder="--">
                        </div>
                        <div class="height-field">
                            <label>${$t('Height OI (mm)')}</label>
                            <input type="number" class="height-input" data-eye="oi" 
                                   min="10" max="40" step="0.5" placeholder="--">
                        </div>
                    </div>
                </div>
            `;
        },

        /**
         * Render upload section
         *
         * @return {string} HTML
         */
        renderUploadSection: function () {
            return `
                <div class="prescription-upload-section">
                    <h3>${$t('Upload Prescription (Optional)')}</h3>
                    <p class="upload-help-text">${$t('You can upload a photo or scan of your prescription')}</p>
                    <div class="upload-drop-zone">
                        <input type="file" id="prescription_file" accept="image/*,.pdf" style="display: none;">
                        <div class="upload-placeholder">
                            <i class="fa fa-cloud-upload"></i>
                            <span>${$t('Click to upload or drag & drop')}</span>
                            <small>${$t('Supported formats: JPG, PNG, PDF (max 5MB)')}</small>
                        </div>
                        <div class="upload-preview" style="display: none;"></div>
                        <div class="upload-progress-bar" style="display: none;">
                            <div class="upload-progress-fill"></div>
                            <span class="upload-progress-text">0%</span>
                        </div>
                        <button type="button" class="upload-remove-btn" style="display: none;">
                            <i class="fa fa-times"></i> ${$t('Remove')}
                        </button>
                        <div class="upload-error" style="display: none;"></div>
                    </div>
                </div>
            `;
        },

        /**
         * Attach event listeners
         */
        attachEvents: function () {
            const self = this;

            // Input change events - debounced validation
            this.container.on('input change', '.prescription-input, .prescription-select', function () {
                self._handleFieldChange($(this));
            });

            // PD type change
            this.container.on('change', 'input[name="pd_type"]', function () {
                self._handlePdTypeChange($(this).val());
            });

            // PD input change
            this.container.on('input', '.pd-input', function () {
                self._handlePdChange($(this));
            });

            // Height input change
            this.container.on('input', '.height-input', function () {
                self._handleHeightChange($(this));
            });

            // Tooltip hover
            this.container.on('mouseenter', '.tooltip-icon', function () {
                self._showTooltip($(this));
            });

            this.container.on('mouseleave', '.tooltip-icon', function () {
                self._hideTooltip();
            });
        },

        /**
         * Initialize upload handler
         */
        _initializeUploadHandler: function () {
            const self = this;
            const uploadContainer = this.container.find('.prescription-upload-section');

            if (uploadContainer.length && this.config.endpoints && this.config.endpoints.upload) {
                uploadHandler.init(
                    uploadContainer,
                    {
                        uploadUrl: this.config.endpoints.upload
                    },
                    function (result) {
                        // Success callback
                        if (result) {
                            self.formData.attachment_id = result.attachmentId;
                            self.formData.attachment_hash = result.hash;
                            self.formData.attachment_filename = result.filename;
                            self.formData.attachment_filepath = result.filePath;
                            self.onChange();
                        } else {
                            self.formData.attachment_id = null;
                            self.formData.attachment_hash = null;
                            self.formData.attachment_filename = null;
                            self.formData.attachment_filepath = null;
                            self.onChange();
                        }
                    },
                    function (error) {
                        // Error callback
                        console.error('Upload error:', error);
                    }
                );
            }
        },

        /**
         * Handle field change
         *
         * @param {jQuery} $input
         */
        _handleFieldChange: function ($input) {
            const eye = $input.data('eye');
            const field = $input.data('field');
            const value = $input.val();

            // Update form data
            if (!this.formData[eye]) {
                this.formData[eye] = {};
            }
            this.formData[eye][field] = value !== '' ? parseFloat(value) || value : null;

            // Clear error
            $input.siblings('.field-error').text('');

            // Debounced validation
            this._debounceValidation();

            // Trigger onChange callback
            if (this.onChange) {
                this.onChange(this.formData);
            }
        },

        /**
         * Handle PD type change
         *
         * @param {string} type - 'binocular' or 'monocular'
         */
        _handlePdTypeChange: function (type) {
            this.formData.pd.type = type;

            if (type === 'binocular') {
                this.container.find('.pd-input-binocular').show();
                this.container.find('.pd-input-monocular').hide();
            } else {
                this.container.find('.pd-input-binocular').hide();
                this.container.find('.pd-input-monocular').show();
            }

            this._debounceValidation();
        },

        /**
         * Handle PD input change
         *
         * @param {jQuery} $input
         */
        _handlePdChange: function ($input) {
            const pdType = $input.data('pd-type');
            const value = $input.val();

            if (pdType === 'binocular') {
                this.formData.pd.value = value !== '' ? parseInt(value) : null;
            } else {
                this.formData.pd[pdType] = value !== '' ? parseInt(value) : null;
            }

            $input.siblings('.field-error').text('');
            this._debounceValidation();

            if (this.onChange) {
                this.onChange(this.formData);
            }
        },

        /**
         * Handle height input change
         *
         * @param {jQuery} $input
         */
        _handleHeightChange: function ($input) {
            const eye = $input.data('eye');
            const value = $input.val();

            if (!this.formData[eye]) {
                this.formData[eye] = {};
            }
            this.formData[eye].height = value !== '' ? parseFloat(value) : null;

            if (this.onChange) {
                this.onChange(this.formData);
            }
        },

        /**
         * Handle file upload
         *
         * @param {File} file
         */
        _handleFileUpload: function (file) {
            if (!file) return;

            // TODO: Upload to server in Sprint 3
            this.container.find('.upload-filename').text(file.name);
            console.log('File selected:', file.name);

            // For now, just store filename
            this.formData.attachment_filename = file.name;
        },

        /**
         * Debounced validation
         */
        _debounceValidation: function () {
            const self = this;

            if (this.validationDebounceTimer) {
                clearTimeout(this.validationDebounceTimer);
            }

            this.validationDebounceTimer = setTimeout(function () {
                self._validateForm();
            }, 300);
        },

        /**
         * Validate form
         */
        _validateForm: function () {
            // Client-side validation
            let isValid = true;

            // Check required SPH
            ['od', 'oi'].forEach(eye => {
                if (!this.formData[eye].sph) {
                    isValid = false;
                }
            });

            // Check AXIS if CYL present
            ['od', 'oi'].forEach(eye => {
                if (this.formData[eye].cyl && !this.formData[eye].axis) {
                    const $axisInput = this.container.find(`input[data-eye="${eye}"][data-field="axis"]`);
                    $axisInput.siblings('.field-error').text($t('Required when CYL is present'));
                    isValid = false;
                }
            });

            // Check PRISM_BASE if PRISM present
            ['od', 'oi'].forEach(eye => {
                if (this.formData[eye].prism && !this.formData[eye].prism_base) {
                    const $baseSelect = this.container.find(`select[data-eye="${eye}"][data-field="prism_base"]`);
                    $baseSelect.siblings('.field-error').text($t('Required when PRISM is present'));
                    isValid = false;
                }
            });

            // Check ADD for progressive/bifocal
            if (['progressive', 'bifocal', 'occupational'].indexOf(this.useType) !== -1) {
                ['od', 'oi'].forEach(eye => {
                    if (!this.formData[eye].add) {
                        isValid = false;
                    }
                });
            }

            // Check PD
            if (this.formData.pd.type === 'binocular') {
                if (!this.formData.pd.value) {
                    isValid = false;
                }
            } else {
                if (!this.formData.pd.od || !this.formData.pd.oi) {
                    isValid = false;
                }
            }

            return isValid;
        },

        /**
         * Initialize tooltips
         */
        _initializeTooltips: function () {
            // Tooltip functionality already handled by mouseenter/mouseleave events
        },

        /**
         * Show tooltip
         *
         * @param {jQuery} $icon
         */
        _showTooltip: function ($icon) {
            const text = $icon.data('tooltip');
            const offset = $icon.offset();

            const $tooltip = $('<div class="field-tooltip"></div>')
                .text(text)
                .css({
                    top: offset.top - 10,
                    left: offset.left + 20
                });

            $('body').append($tooltip);
            $tooltip.fadeIn(200);
        },

        /**
         * Hide tooltip
         */
        _hideTooltip: function () {
            $('.field-tooltip').fadeOut(200, function () {
                $(this).remove();
            });
        },

        /**
         * Validate step
         *
         * @return {boolean}
         */
        validate: function () {
            const isValid = this._validateForm();

            if (!isValid) {
                this.container.find('.validation-summary').html(
                    '<div class="message error">' + $t('Please complete all required fields.') + '</div>'
                );
            } else {
                this.container.find('.validation-summary').empty();
            }

            return isValid;
        },

        /**
         * Get step data
         *
         * @return {Object}
         */
        getData: function () {
            return {
                prescription_data: this.formData
            };
        }
    };
});
