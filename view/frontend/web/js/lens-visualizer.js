/**
 * Lens Visualizer Component
 * 
 * Simple 2D visualization of lens configuration showing:
 * - Lens shape and thickness based on prescription
 * - Material representation
 * - Applied treatments as visual layers
 * - Responsive SVG-based rendering
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
            width: 300,
            height: 200,
            baseThickness: 3,
            maxThickness: 20,
            colors: {
                CR39: '#f0f0f0',
                POLYCARBONATE: '#e8f4f8',
                TRIVEX: '#f8f0e8',
                HIGH_INDEX: '#e8e8f8',
                GLASS: '#e0f0e0'
            },
            treatments: {
                AR_BASIC: { color: '#9370db', opacity: 0.3, label: 'AR' },
                AR_PREMIUM: { color: '#8a2be2', opacity: 0.4, label: 'AR+' },
                AR_BLUE_LIGHT: { color: '#4169e1', opacity: 0.5, label: 'AR+Blue' },
                BLUE_LIGHT: { color: '#1e90ff', opacity: 0.4, label: 'Blue' },
                PHOTOCHROMIC: { color: '#a9a9a9', opacity: 0.6, label: 'Photo' },
                POLARIZED: { color: '#2f4f4f', opacity: 0.5, label: 'Polar' },
                UV_PROTECTION: { color: '#ffd700', opacity: 0.3, label: 'UV' },
                MIRROR_COATING: { color: '#c0c0c0', opacity: 0.7, label: 'Mirror' }
            }
        },

        /**
         * Current visualization data
         */
        data: {
            material: null,
            design: null,
            prescription: null,
            treatments: [],
            index: null
        },

        /**
         * Container element
         */
        container: null,

        /**
         * SVG element
         */
        svg: null,

        /**
         * Initialize visualizer
         *
         * @param {jQuery} container
         * @param {Object} config
         */
        init: function (container, config) {
            this.container = $(container);
            this.config = $.extend(this.config, config || {});
            
            this.render();
        },

        /**
         * Update visualization with new data
         *
         * @param {Object} lensData - {material, design, prescription, treatments, index}
         */
        update: function (lensData) {
            this.data = $.extend(this.data, lensData);
            this.render();
        },

        /**
         * Render SVG visualization
         */
        render: function () {
            const svgNS = 'http://www.w3.org/2000/svg';
            const width = this.config.width;
            const height = this.config.height;

            // Clear container
            this.container.empty();

            // Create SVG
            const svg = document.createElementNS(svgNS, 'svg');
            svg.setAttribute('width', width);
            svg.setAttribute('height', height);
            svg.setAttribute('viewBox', `0 0 ${width} ${height}`);
            svg.classList.add('lens-visualizer-svg');

            // Add title
            const title = this.renderTitle();
            if (title) {
                this.container.append(title);
            }

            // Background
            this.drawBackground(svg, svgNS);

            // Lens shape
            if (this.data.material && this.data.prescription) {
                this.drawLens(svg, svgNS);
                
                // Treatment layers
                if (this.data.treatments && this.data.treatments.length > 0) {
                    this.drawTreatments(svg, svgNS);
                }

                // Thickness indicators
                this.drawThicknessIndicators(svg, svgNS);

                // Labels
                this.drawLabels(svg, svgNS);
            } else {
                // Placeholder
                this.drawPlaceholder(svg, svgNS);
            }

            this.container.append(svg);
            this.svg = svg;
        },

        /**
         * Render title section
         */
        renderTitle: function () {
            if (!this.data.material) {
                return null;
            }

            const material = this.getMaterialName(this.data.material);
            const design = this.data.design ? this.getDesignName(this.data.design) : '';
            const index = this.data.index ? `Index ${this.data.index}` : '';

            return $('<div>')
                .addClass('lens-visualizer-title')
                .html(`
                    <h4>${material} ${index}</h4>
                    ${design ? `<p>${design}</p>` : ''}
                `);
        },

        /**
         * Draw background
         */
        drawBackground: function (svg, svgNS) {
            const rect = document.createElementNS(svgNS, 'rect');
            rect.setAttribute('width', '100%');
            rect.setAttribute('height', '100%');
            rect.setAttribute('fill', '#fafafa');
            rect.setAttribute('rx', '8');
            svg.appendChild(rect);
        },

        /**
         * Draw lens shape
         */
        drawLens: function (svg, svgNS) {
            const thickness = this.calculateThickness();
            const centerX = this.config.width / 2;
            const centerY = this.config.height / 2;
            const lensWidth = 180;
            const lensHeight = 120;

            // Calculate curvature based on prescription
            const sphPower = Math.abs(this.getSphereValue());
            const curvature = Math.min(40, 10 + sphPower * 3);

            // Lens path (elliptical shape with curvature)
            const path = document.createElementNS(svgNS, 'path');
            const pathData = this.createLensPath(
                centerX,
                centerY,
                lensWidth,
                lensHeight,
                curvature,
                thickness
            );
            path.setAttribute('d', pathData);
            path.setAttribute('fill', this.getMaterialColor());
            path.setAttribute('stroke', '#999');
            path.setAttribute('stroke-width', '2');
            path.setAttribute('opacity', '0.9');
            svg.appendChild(path);

            // Edge highlight for high index
            if (this.data.index && parseFloat(this.data.index) >= 1.67) {
                const edgePath = document.createElementNS(svgNS, 'path');
                edgePath.setAttribute('d', pathData);
                edgePath.setAttribute('fill', 'none');
                edgePath.setAttribute('stroke', '#4169e1');
                edgePath.setAttribute('stroke-width', '3');
                edgePath.setAttribute('opacity', '0.5');
                svg.appendChild(edgePath);
            }
        },

        /**
         * Create lens path data
         */
        createLensPath: function (cx, cy, width, height, curvature, thickness) {
            const halfWidth = width / 2;
            const halfHeight = height / 2;

            // Adjust for thickness (thicker edges for minus, thicker center for plus)
            const sph = this.getSphereValue();
            const edgeAdjust = sph < 0 ? thickness * 0.5 : 0;
            const centerAdjust = sph > 0 ? thickness * 0.5 : 0;

            return `
                M ${cx - halfWidth} ${cy}
                Q ${cx - halfWidth - curvature} ${cy - halfHeight - centerAdjust}, ${cx} ${cy - halfHeight - centerAdjust}
                Q ${cx + halfWidth + curvature} ${cy - halfHeight - centerAdjust}, ${cx + halfWidth} ${cy}
                Q ${cx + halfWidth + curvature + edgeAdjust} ${cy + halfHeight + centerAdjust}, ${cx} ${cy + halfHeight + centerAdjust}
                Q ${cx - halfWidth - curvature - edgeAdjust} ${cy + halfHeight + centerAdjust}, ${cx - halfWidth} ${cy}
                Z
            `;
        },

        /**
         * Draw treatment layers
         */
        drawTreatments: function (svg, svgNS) {
            const centerX = this.config.width / 2;
            const centerY = this.config.height / 2;
            const tagY = 20;
            let tagX = 20;

            this.data.treatments.forEach((treatment, index) => {
                const treatmentConfig = this.config.treatments[treatment];
                if (!treatmentConfig) return;

                // Treatment tag
                const tag = document.createElementNS(svgNS, 'text');
                tag.setAttribute('x', tagX);
                tag.setAttribute('y', tagY + (index * 18));
                tag.setAttribute('fill', treatmentConfig.color);
                tag.setAttribute('font-size', '12');
                tag.setAttribute('font-weight', 'bold');
                tag.textContent = treatmentConfig.label;
                svg.appendChild(tag);
            });
        },

        /**
         * Draw thickness indicators
         */
        drawThicknessIndicators: function (svg, svgNS) {
            const thickness = this.calculateThickness();
            const centerX = this.config.width / 2;
            const bottomY = this.config.height - 30;

            // Thickness bar
            const barWidth = 100;
            const barHeight = 10;
            const barX = centerX - barWidth / 2;

            const bar = document.createElementNS(svgNS, 'rect');
            bar.setAttribute('x', barX);
            bar.setAttribute('y', bottomY);
            bar.setAttribute('width', barWidth * (thickness / this.config.maxThickness));
            bar.setAttribute('height', barHeight);
            bar.setAttribute('fill', '#4169e1');
            bar.setAttribute('opacity', '0.6');
            svg.appendChild(bar);

            // Thickness label
            const label = document.createElementNS(svgNS, 'text');
            label.setAttribute('x', centerX);
            label.setAttribute('y', bottomY + barHeight + 15);
            label.setAttribute('text-anchor', 'middle');
            label.setAttribute('font-size', '11');
            label.setAttribute('fill', '#666');
            label.textContent = $t('Thickness: ') + thickness.toFixed(1) + 'mm';
            svg.appendChild(label);
        },

        /**
         * Draw labels
         */
        drawLabels: function (svg, svgNS) {
            const centerX = this.config.width / 2;
            const centerY = this.config.height / 2;

            // Prescription label
            const sph = this.getSphereValue();
            const cyl = this.getCylinderValue();

            let prescText = this.formatPrescriptionValue(sph);
            if (cyl !== 0) {
                prescText += ' / ' + this.formatPrescriptionValue(cyl);
            }

            const prescLabel = document.createElementNS(svgNS, 'text');
            prescLabel.setAttribute('x', centerX);
            prescLabel.setAttribute('y', centerY);
            prescLabel.setAttribute('text-anchor', 'middle');
            prescLabel.setAttribute('font-size', '16');
            prescLabel.setAttribute('font-weight', 'bold');
            prescLabel.setAttribute('fill', '#333');
            prescLabel.textContent = prescText;
            svg.appendChild(prescLabel);
        },

        /**
         * Draw placeholder
         */
        drawPlaceholder: function (svg, svgNS) {
            const centerX = this.config.width / 2;
            const centerY = this.config.height / 2;

            const text = document.createElementNS(svgNS, 'text');
            text.setAttribute('x', centerX);
            text.setAttribute('y', centerY);
            text.setAttribute('text-anchor', 'middle');
            text.setAttribute('font-size', '14');
            text.setAttribute('fill', '#999');
            text.textContent = $t('Select material and prescription to visualize');
            svg.appendChild(text);
        },

        /**
         * Calculate lens thickness
         */
        calculateThickness: function () {
            const sph = Math.abs(this.getSphereValue());
            const cyl = Math.abs(this.getCylinderValue());
            
            // Base thickness
            let thickness = this.config.baseThickness;

            // Add thickness based on sphere
            thickness += sph * 0.8;

            // Add thickness for cylinder
            thickness += cyl * 0.4;

            // Reduce for high index
            if (this.data.index) {
                const indexFactor = (1.74 - parseFloat(this.data.index)) / (1.74 - 1.50);
                thickness *= (0.6 + indexFactor * 0.4);
            }

            return Math.min(thickness, this.config.maxThickness);
        },

        /**
         * Get sphere value from prescription
         */
        getSphereValue: function () {
            if (!this.data.prescription || !this.data.prescription.od) {
                return 0;
            }
            return parseFloat(this.data.prescription.od.sph) || 0;
        },

        /**
         * Get cylinder value from prescription
         */
        getCylinderValue: function () {
            if (!this.data.prescription || !this.data.prescription.od) {
                return 0;
            }
            return parseFloat(this.data.prescription.od.cyl) || 0;
        },

        /**
         * Get material color
         */
        getMaterialColor: function () {
            return this.config.colors[this.data.material] || '#f0f0f0';
        },

        /**
         * Get material name
         */
        getMaterialName: function (material) {
            const names = {
                CR39: 'CR-39',
                POLYCARBONATE: 'Polycarbonate',
                TRIVEX: 'Trivex',
                HIGH_INDEX: 'High Index',
                GLASS: 'Glass'
            };
            return names[material] || material;
        },

        /**
         * Get design name
         */
        getDesignName: function (design) {
            const names = {
                SPHERICAL: 'Spherical',
                ASPHERIC: 'Aspheric',
                DOUBLE_ASPHERIC: 'Double Aspheric',
                FREEFORM: 'Freeform',
                PERSONALIZED: 'Personalized'
            };
            return names[design] || design;
        },

        /**
         * Format prescription value
         */
        formatPrescriptionValue: function (value) {
            if (value === 0) return '0.00';
            return (value > 0 ? '+' : '') + value.toFixed(2);
        }
    };
});
