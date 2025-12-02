/**
 * Powerline PrescripcionModule - Add to Cart (Frame Only)
 *
 * @category  Powerline
 * @package   Powerline_PrescripcionModule
 * @author    Powerline Development Team
 * @copyright Copyright (c) 2025 Powerline
 */

define([
    'jquery',
    'mage/translate',
    'Magento_Catalog/js/catalog-add-to-cart'
], function ($, $t) {
    'use strict';

    /**
     * Add to Cart Widget (Frame Only)
     */
    return function (config, element) {
        var $button = $(element);
        var $form = $('#product_addtocart_form');

        /**
         * Handle add to cart click
         */
        $button.on('click', function (e) {
            e.preventDefault();
            e.stopPropagation();

            // Verificar si hay una talla seleccionada
            var sizeSelected = false;
            var $sizeSelect = $(
                '#attribute-size, ' +
                'select[name="super_attribute[size]"], ' +
                'select[name="super_attribute[152]"], ' +
                '.product-options-wrapper select[attribute-code="size"]'
            );

            if ($sizeSelect.length) {
                var selectedValue = $sizeSelect.val();
                if (selectedValue && selectedValue !== '') {
                    sizeSelected = true;
                }
            } else {
                // Si no hay selector de talla, permitir continuar
                sizeSelected = true;
            }

            if (!sizeSelected) {
                alert($t('Por favor, seleccione una talla antes de añadir al carrito.'));

                // Hacer scroll al selector de talla y resaltarlo
                if ($sizeSelect.length) {
                    $('html, body').animate({
                        scrollTop: $sizeSelect.offset().top - 100
                    }, 500);

                    $sizeSelect.css({
                        'border': '2px solid #e02b27',
                        'box-shadow': '0 0 5px rgba(224, 43, 39, 0.5)'
                    });

                    setTimeout(function () {
                        $sizeSelect.css({
                            'border': '',
                            'box-shadow': ''
                        });
                    }, 3000);
                }

                return false;
            }

            // Añadir indicador de que es solo montura (sin cristales)
            $form.append('<input type="hidden" name="frame_only" value="1" />');

            // Deshabilitar botón y mostrar loading
            $button.addClass('disabled').prop('disabled', true);
            $button.find('span').text($t('Añadiendo...'));

            // Submit del formulario usando la funcionalidad nativa de Magento
            $form.trigger('submit');
        });

        // Escuchar evento de ajax:addToCart success
        $(document).on('ajax:addToCart', function () {
            $button.removeClass('disabled').prop('disabled', false);
            $button.find('span').text($t('Añadir solo la montura al carrito'));
        });
    };
});
