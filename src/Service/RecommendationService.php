<?php

namespace App\Service;

use App\Entity\Product;
use App\Entity\User;
use App\Repository\PurchaseRepository;
use App\Repository\ProductRepository;
use Symfony\Contracts\Cache\CacheInterface;

class RecommendationService
{
    private $purchaseRepository;
    private $productRepository;
    private $cache;

    public function __construct(PurchaseRepository $purchaseRepository, ProductRepository $productRepository, CacheInterface $cache)
    {
        $this->purchaseRepository = $purchaseRepository;
        $this->productRepository = $productRepository;
        $this->cache = $cache;
    }

    public function getRecommendationsForUser(User $user): array
    {
        // Get purchase history for the user
        $purchases = $this->purchaseRepository->findBy(['user' => $user]);

        // For simplicity, let's assume we recommend the most frequently purchased products
        $productCounts = [];
        foreach ($purchases as $purchase) {
            $productId = $purchase->getProduct()->getId();
            if (!isset($productCounts[$productId])) {
                $productCounts[$productId] = 0;
            }
            $productCounts[$productId] += $purchase->getQuantity();
        }

        // Sort products by the most purchased
        arsort($productCounts);
        $recommendedProductIds = array_keys($productCounts);

        // Get product details for the recommended products
        $recommendedProducts = [];
        foreach ($recommendedProductIds as $productId) {
            $product = $this->productRepository->find($productId);
            if ($product) {
                $recommendedProducts[] = $product;
            }
        }

        return $recommendedProducts;
    }

    public function getRecommendationsForProduct(Product $product): array
    {
        // For simplicity, recommend products in the same category
        return $this->cache->get('product_recommendations_' . $product->getId(), function () use ($product) {
            return $this->productRepository->findBy(['category' => $product->getCategory()]);
        });
    }
}