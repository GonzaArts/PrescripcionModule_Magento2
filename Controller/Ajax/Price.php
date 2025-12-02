<?php

declare(strict_types=1);

namespace Powerline\PrescripcionModule\Controller\Ajax;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;

/**
 * AJAX endpoint para cálculo de precios - VERSIÓN SIMPLIFICADA
 */
class Price implements HttpPostActionInterface
{
    // Precios base fijos por tipo de uso
    private const LENS_PRICES = [
        'monofocal' => 20.90,
        'progressive' => 86.90,
        'no_prescription' => 20.90
    ];

    public function __construct(
        private readonly RequestInterface $request,
        private readonly JsonFactory $jsonFactory,
        private readonly ProductRepositoryInterface $productRepository
    ) {
    }

    /**
     * Execute price calculation
     */
    public function execute(): ResultInterface
    {
        $result = $this->jsonFactory->create();

        try {
            // Leer datos de la petición
            $data = $this->request->getContent();
            $requestData = json_decode($data, true);

            // Validar datos básicos
            if (!isset($requestData['product_id']) || !isset($requestData['use_type'])) {
                return $result->setData([
                    'success' => false,
                    'error' => 'Faltan datos requeridos'
                ]);
            }

            $productId = (int) $requestData['product_id'];
            $useType = $requestData['use_type'];

            // 1. Obtener precio del producto (montura)
            $framePrice = 0.0;
            try {
                $product = $this->productRepository->getById($productId);
                $priceInfo = $product->getPriceInfo();
                $finalPrice = $priceInfo->getPrice('final_price');
                $framePrice = (float) $finalPrice->getAmount()->getValue();
            } catch (\Exception $e) {
                // Si falla, usar 0
            }

            // 2. Obtener precio base del cristal según tipo de uso
            $baseLensPrice = self::LENS_PRICES[$useType] ?? 0.0;

            // 3. Calcular total simple
            $totalPrice = $framePrice + $baseLensPrice;

            // 4. Devolver respuesta
            return $result->setData([
                'success' => true,
                'price_breakdown' => [
                    'frame_price' => $framePrice,
                    'base_lens_price' => $baseLensPrice,
                    'sphere_surcharge' => 0.0,
                    'cylinder_surcharge' => 0.0,
                    'addition_surcharge' => 0.0,
                    'prism_surcharge' => 0.0,
                    'treatments_total' => 0.0,
                    'extras_total' => 0.0,
                    'total_price' => $totalPrice
                ]
            ]);

        } catch (\Exception $e) {
            return $result->setData([
                'success' => false,
                'error' => 'Error al calcular precio: ' . $e->getMessage()
            ]);
        }
    }
}
