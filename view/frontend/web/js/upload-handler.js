/**
 * Upload Handler Component
 * 
 * Handles prescription file upload with:
 * - Drag & drop support
 * - File preview (images and PDFs)
 * - Client-side validation
 * - Progress tracking
 * - Image compression for files >2MB
 * - Error handling
 */
define([
    'jquery',
    'mage/translate'
], function ($, $t) {
    'use strict';

    return {
        /**
         * Configuration
         */
        config: {
            uploadUrl: null,
            maxFileSize: 5242880, // 5MB
            allowedExtensions: ['jpg', 'jpeg', 'png', 'gif', 'pdf'],
            allowedMimeTypes: ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'application/pdf'],
            compressionThreshold: 2097152, // 2MB
            compressionQuality: 0.8,
            previewMaxWidth: 300,
            previewMaxHeight: 300
        },

        /**
         * State
         */
        state: {
            uploading: false,
            uploadedFile: null,
            attachmentId: null,
            attachmentHash: null,
            attachmentFilename: null,
            attachmentFilepath: null
        },

        /**
         * DOM elements
         */
        elements: {},

        /**
         * Callbacks
         */
        callbacks: {
            onSuccess: null,
            onError: null,
            onProgress: null
        },

        /**
         * Initialize upload handler
         *
         * @param {jQuery} container - Container element
         * @param {Object} config - Configuration options
         * @param {Function} onSuccess - Success callback
         * @param {Function} onError - Error callback
         */
        init: function (container, config, onSuccess, onError) {
            this.config = $.extend(this.config, config);
            this.callbacks.onSuccess = onSuccess || function () {};
            this.callbacks.onError = onError || function () {};

            this.elements = {
                container: container,
                dropZone: container.find('.upload-drop-zone'),
                fileInput: container.find('#prescription_file'),
                previewContainer: container.find('.upload-preview'),
                progressBar: container.find('.upload-progress-bar'),
                progressFill: container.find('.upload-progress-fill'),
                progressText: container.find('.upload-progress-text'),
                errorContainer: container.find('.upload-error'),
                removeBtn: container.find('.upload-remove-btn')
            };

            this.bindEvents();
            this.setupDragAndDrop();
        },

        /**
         * Bind event handlers
         */
        bindEvents: function () {
            const self = this;

            // File input change
            this.elements.fileInput.on('change', function (e) {
                const file = e.target.files[0];
                if (file) {
                    self.handleFileSelect(file);
                }
            });

            // Remove button
            this.elements.removeBtn.on('click', function () {
                self.removeFile();
            });

            // Click drop zone to open file dialog
            this.elements.dropZone.on('click', function (e) {
                if (!$(e.target).closest('.upload-preview, .upload-remove-btn').length) {
                    self.elements.fileInput.trigger('click');
                }
            });
        },

        /**
         * Setup drag and drop
         */
        setupDragAndDrop: function () {
            const self = this;
            const dropZone = this.elements.dropZone[0];

            // Prevent default drag behaviors
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                dropZone.addEventListener(eventName, function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                }, false);
            });

            // Add visual feedback
            ['dragenter', 'dragover'].forEach(eventName => {
                dropZone.addEventListener(eventName, function () {
                    self.elements.dropZone.addClass('drag-over');
                }, false);
            });

            ['dragleave', 'drop'].forEach(eventName => {
                dropZone.addEventListener(eventName, function () {
                    self.elements.dropZone.removeClass('drag-over');
                }, false);
            });

            // Handle drop
            dropZone.addEventListener('drop', function (e) {
                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    self.handleFileSelect(files[0]);
                }
            }, false);
        },

        /**
         * Handle file selection
         *
         * @param {File} file
         */
        handleFileSelect: function (file) {
            this.clearError();

            // Validate file
            const validation = this.validateFile(file);
            if (!validation.valid) {
                this.showError(validation.error);
                return;
            }

            // Check if compression needed
            if (this.isImage(file) && file.size > this.config.compressionThreshold) {
                this.compressImage(file).then(compressedFile => {
                    this.uploadFile(compressedFile || file);
                }).catch(() => {
                    this.uploadFile(file);
                });
            } else {
                this.uploadFile(file);
            }
        },

        /**
         * Validate file
         *
         * @param {File} file
         * @return {Object} {valid: boolean, error: string}
         */
        validateFile: function (file) {
            // Check file exists
            if (!file) {
                return {
                    valid: false,
                    error: $t('No file selected')
                };
            }

            // Check file size
            if (file.size > this.config.maxFileSize) {
                return {
                    valid: false,
                    error: $t('File size exceeds maximum allowed size of 5MB')
                };
            }

            // Check extension
            const extension = file.name.split('.').pop().toLowerCase();
            if (!this.config.allowedExtensions.includes(extension)) {
                return {
                    valid: false,
                    error: $t('Invalid file type. Allowed types: JPG, PNG, GIF, PDF')
                };
            }

            // Check MIME type
            if (!this.config.allowedMimeTypes.includes(file.type)) {
                return {
                    valid: false,
                    error: $t('Invalid file format')
                };
            }

            return { valid: true };
        },

        /**
         * Check if file is an image
         *
         * @param {File} file
         * @return {boolean}
         */
        isImage: function (file) {
            return file.type.startsWith('image/');
        },

        /**
         * Compress image
         *
         * @param {File} file
         * @return {Promise<File|null>}
         */
        compressImage: function (file) {
            const self = this;

            return new Promise((resolve, reject) => {
                const reader = new FileReader();
                
                reader.onload = function (e) {
                    const img = new Image();
                    
                    img.onload = function () {
                        const canvas = document.createElement('canvas');
                        const ctx = canvas.getContext('2d');
                        
                        // Calculate new dimensions maintaining aspect ratio
                        let width = img.width;
                        let height = img.height;
                        const maxDimension = 1920;
                        
                        if (width > maxDimension || height > maxDimension) {
                            if (width > height) {
                                height = (height / width) * maxDimension;
                                width = maxDimension;
                            } else {
                                width = (width / height) * maxDimension;
                                height = maxDimension;
                            }
                        }
                        
                        canvas.width = width;
                        canvas.height = height;
                        
                        ctx.drawImage(img, 0, 0, width, height);
                        
                        canvas.toBlob(function (blob) {
                            if (blob) {
                                const compressedFile = new File([blob], file.name, {
                                    type: file.type,
                                    lastModified: Date.now()
                                });
                                
                                console.log('Image compressed:', {
                                    original: file.size,
                                    compressed: compressedFile.size,
                                    reduction: ((1 - compressedFile.size / file.size) * 100).toFixed(2) + '%'
                                });
                                
                                resolve(compressedFile);
                            } else {
                                resolve(null);
                            }
                        }, file.type, self.config.compressionQuality);
                    };
                    
                    img.onerror = reject;
                    img.src = e.target.result;
                };
                
                reader.onerror = reject;
                reader.readAsDataURL(file);
            });
        },

        /**
         * Upload file to server
         *
         * @param {File} file
         */
        uploadFile: function (file) {
            const self = this;

            // Set uploading state
            this.state.uploading = true;
            this.elements.dropZone.addClass('uploading');
            this.showProgress(0);

            // Create FormData
            const formData = new FormData();
            formData.append('prescription_file', file);
            formData.append('form_key', $.cookie('form_key'));

            // AJAX upload
            $.ajax({
                url: this.config.uploadUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                xhr: function () {
                    const xhr = new window.XMLHttpRequest();
                    
                    // Upload progress
                    xhr.upload.addEventListener('progress', function (e) {
                        if (e.lengthComputable) {
                            const percentComplete = (e.loaded / e.total) * 100;
                            self.showProgress(percentComplete);
                        }
                    }, false);
                    
                    return xhr;
                },
                success: function (response) {
                    self.handleUploadSuccess(response, file);
                },
                error: function (xhr) {
                    self.handleUploadError(xhr);
                },
                complete: function () {
                    self.state.uploading = false;
                    self.elements.dropZone.removeClass('uploading');
                    self.hideProgress();
                }
            });
        },

        /**
         * Handle upload success
         *
         * @param {Object} response
         * @param {File} file
         */
        handleUploadSuccess: function (response, file) {
            if (response.success) {
                this.state.uploadedFile = file;
                this.state.attachmentId = response.attachment_id;
                this.state.attachmentHash = response.hash;
                this.state.attachmentFilename = response.filename;
                this.state.attachmentFilepath = response.file_path;

                // Show preview
                this.showPreview(file, response.thumbnail_path);

                // Show remove button
                this.elements.removeBtn.show();

                // Callback
                this.callbacks.onSuccess({
                    attachmentId: response.attachment_id,
                    hash: response.hash,
                    filename: response.filename,
                    filePath: response.file_path
                });

            } else {
                this.showError(response.error || $t('Upload failed'));
                this.callbacks.onError(response.error);
            }
        },

        /**
         * Handle upload error
         *
         * @param {Object} xhr
         */
        handleUploadError: function (xhr) {
            let errorMessage = $t('An error occurred during file upload');

            if (xhr.responseJSON && xhr.responseJSON.error) {
                errorMessage = xhr.responseJSON.error;
            } else if (xhr.status === 413) {
                errorMessage = $t('File is too large');
            }

            this.showError(errorMessage);
            this.callbacks.onError(errorMessage);
        },

        /**
         * Show file preview
         *
         * @param {File} file
         * @param {string|null} thumbnailPath
         */
        showPreview: function (file, thumbnailPath) {
            const self = this;
            this.elements.previewContainer.empty();

            if (this.isImage(file)) {
                // Image preview
                const reader = new FileReader();
                
                reader.onload = function (e) {
                    const img = $('<img>')
                        .attr('src', e.target.result)
                        .attr('alt', file.name)
                        .css({
                            maxWidth: self.config.previewMaxWidth + 'px',
                            maxHeight: self.config.previewMaxHeight + 'px'
                        });
                    
                    self.elements.previewContainer.html(img);
                };
                
                reader.readAsDataURL(file);

            } else if (file.type === 'application/pdf') {
                // PDF preview
                const pdfIcon = $('<div>')
                    .addClass('pdf-preview')
                    .html(`
                        <i class="fa fa-file-pdf" style="font-size: 60px; color: #dc3545;"></i>
                        <p>${file.name}</p>
                    `);
                
                this.elements.previewContainer.html(pdfIcon);
            }

            this.elements.previewContainer.show();
        },

        /**
         * Remove uploaded file
         */
        removeFile: function () {
            this.state.uploadedFile = null;
            this.state.attachmentId = null;
            this.state.attachmentHash = null;
            this.state.attachmentFilename = null;
            this.state.attachmentFilepath = null;

            this.elements.fileInput.val('');
            this.elements.previewContainer.hide().empty();
            this.elements.removeBtn.hide();
            this.clearError();

            // Callback
            this.callbacks.onSuccess(null);
        },

        /**
         * Show upload progress
         *
         * @param {number} percent
         */
        showProgress: function (percent) {
            this.elements.progressBar.show();
            this.elements.progressFill.css('width', percent + '%');
            this.elements.progressText.text(Math.round(percent) + '%');
        },

        /**
         * Hide upload progress
         */
        hideProgress: function () {
            setTimeout(() => {
                this.elements.progressBar.hide();
                this.elements.progressFill.css('width', '0%');
                this.elements.progressText.text('');
            }, 500);
        },

        /**
         * Show error message
         *
         * @param {string} message
         */
        showError: function (message) {
            this.elements.errorContainer
                .html(`<i class="fa fa-exclamation-circle"></i> ${message}`)
                .show();
        },

        /**
         * Clear error message
         */
        clearError: function () {
            this.elements.errorContainer.hide().empty();
        },

        /**
         * Get uploaded attachment ID
         *
         * @return {number|null}
         */
        getAttachmentId: function () {
            return this.state.attachmentId;
        },

        /**
         * Get uploaded file info
         *
         * @return {Object|null}
         */
        getFileInfo: function () {
            if (!this.state.uploadedFile) {
                return null;
            }

            return {
                name: this.state.uploadedFile.name,
                size: this.state.uploadedFile.size,
                type: this.state.uploadedFile.type,
                attachmentId: this.state.attachmentId,
                hash: this.state.attachmentHash,
                filename: this.state.attachmentFilename,
                filepath: this.state.attachmentFilepath
            };
        }
    };
});
