<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Promotion;

class Update
{
    private \Ess\M2ePro\Model\Ebay\Promotion\Repository $repository;
    private \Ess\M2ePro\Model\Listing\Log\Factory $logFactory;
    private \Ess\M2ePro\Model\Listing\ProductFactory $listingProductFactory;
    private \Ess\M2ePro\Model\Ebay\Listing\Product\PromotionFactory $listingProductPromotionFactory;
    private \Ess\M2ePro\Model\Ebay\Promotion\Channel\Update $updateConnector;

    public function __construct(
        \Ess\M2ePro\Model\Ebay\Promotion\Repository $repository,
        \Ess\M2ePro\Model\Listing\Log\Factory $logFactory,
        \Ess\M2ePro\Model\Listing\ProductFactory $listingProductFactory,
        \Ess\M2ePro\Model\Ebay\Listing\Product\PromotionFactory $listingProductPromotionFactory,
        \Ess\M2ePro\Model\Ebay\Promotion\Channel\Update $updateConnector
    ) {
        $this->repository = $repository;
        $this->logFactory = $logFactory;
        $this->listingProductFactory = $listingProductFactory;
        $this->listingProductPromotionFactory = $listingProductPromotionFactory;
        $this->updateConnector = $updateConnector;
    }

    /**
     * @param \Ess\M2ePro\Model\Ebay\Promotion $promotion
     * @param \Ess\M2ePro\Model\Listing\Product[] $selectedListingProducts
     *
     * @return void
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function replace(
        \Ess\M2ePro\Model\Ebay\Promotion $promotion,
        array $selectedListingProducts
    ): void {
        $itemsWithErrors = $this->updateConnector->updateChannelPromotion(
            $promotion,
            $selectedListingProducts
        );

        $existListingProductPromotions = $this->loadExistListingProductPromotions($promotion);

        $this->processListingProducts(
            $promotion,
            $selectedListingProducts,
            $existListingProductPromotions,
            $itemsWithErrors
        );

        if ($this->isAllItemsHaveErrors($selectedListingProducts, $itemsWithErrors)) {
            return;
        }

        $this->removeUnselectedListingProductPromotions(
            $promotion,
            $existListingProductPromotions,
            $selectedListingProducts
        );
    }

    /**
     * @param \Ess\M2ePro\Model\Ebay\Promotion $promotion
     * @param \Ess\M2ePro\Model\Listing\Product[] $selectedListingProducts
     *
     * @return void
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function add(
        \Ess\M2ePro\Model\Ebay\Promotion $promotion,
        array $selectedListingProducts
    ): void {
        $existListingProductPromotions = $this->loadExistListingProductPromotions($promotion);

        $existListingProducts = $this->getExistListingProducts(
            $selectedListingProducts,
            $existListingProductPromotions
        );

        $allListingProducts = array_merge($selectedListingProducts, $existListingProducts);

        //$itemsWithErrors = $this->updateConnector->updateChannelPromotion(
        //    $promotion,
        //    $allListingProducts
        //);

        $this->processListingProducts(
            $promotion,
            $allListingProducts,
            $existListingProductPromotions,
            []
        );
    }

    /**
     * @param \Ess\M2ePro\Model\Ebay\Promotion $promotion
     * @param \Ess\M2ePro\Model\Listing\Product[] $listingProducts
     * @param \Ess\M2ePro\Model\Ebay\Listing\Product\Promotion[] $existListingProductPromotions
     * @param array<string, string> $itemsWithErrors
     *
     * @return void
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function processListingProducts(
        \Ess\M2ePro\Model\Ebay\Promotion $promotion,
        array $listingProducts,
        array $existListingProductPromotions,
        array $itemsWithErrors
    ): void {
        foreach ($listingProducts as $listingProduct) {
            $itemId = $listingProduct->getChildObject()->getEbayItem()->getItemId();
            $listingProductId = $listingProduct->getId();

            if (isset($itemsWithErrors[$itemId])) {
                if (isset($existListingProductPromotions[$listingProductId])) {
                    $this->repository->removeListingProductPromotion($existListingProductPromotions[$listingProductId]);

                    $message = (string)__(
                        'Item was removed from Discount "%discount_name"',
                        ['discount_name' => $promotion->getName()]
                    );
                    $this->writeLog($listingProduct, $message, \Ess\M2ePro\Model\Log\AbstractModel::TYPE_SUCCESS);
                } else {
                    $message = (string)__(
                        'Item was not added to Discount "%discount_name"',
                        ['discount_name' => $promotion->getName()]
                    );
                    $this->writeLog($listingProduct, $message, \Ess\M2ePro\Model\Log\AbstractModel::TYPE_WARNING);
                }

                continue;
            }

            if (isset($existListingProductPromotions[$listingProductId])) {
                continue;
            }

            $this->createListingProductPromotion($promotion, $listingProduct);

            $message = (string)__(
                'Item was added to Discount "%discount_name"',
                ['discount_name' => $promotion->getName()]
            );
            $this->writeLog($listingProduct, $message, \Ess\M2ePro\Model\Log\AbstractModel::TYPE_SUCCESS);
        }
    }

    private function getExistListingProducts($selectedListingProducts, $existListingProductPromotions): array
    {
        $existListingProducts = [];

        foreach ($existListingProductPromotions as $existListingProductPromotion) {
            $existListingProductId = $existListingProductPromotion->getListingProductId();
            if (isset($selectedListingProducts[$existListingProductId])) {
                continue;
            }

            $existListingProducts[] = $this->loadListingProduct($existListingProductId);
        }

        return $existListingProducts;
    }

    private function loadExistListingProductPromotions(\Ess\M2ePro\Model\Ebay\Promotion $promotion): array
    {
        $existListingProductPromotionsTemp = $this->repository->findListingProductsByAccountAndMarketplaceAndPromotion(
            $promotion->getAccountId(),
            $promotion->getMarketplaceId(),
            $promotion->getId()
        );

        $existListingProductPromotions = [];
        foreach ($existListingProductPromotionsTemp as $existListingProductPromotion) {
            $listingProductId = $existListingProductPromotion->getListingProductId();
            $existListingProductPromotions[$listingProductId] = $existListingProductPromotion;
        }

        return $existListingProductPromotions;
    }

    private function createListingProductPromotion(
        \Ess\M2ePro\Model\Ebay\Promotion $promotion,
        \Ess\M2ePro\Model\Listing\Product $listingProduct
    ): void {
        $listingProductPromotion = $this->listingProductPromotionFactory->create();
        $listingProductPromotion->init(
            $promotion->getAccountId(),
            $promotion->getMarketplaceId(),
            (int)$listingProduct->getId(),
            $promotion->getId(),
            null
        );

        $this->repository->createListingProductPromotion($listingProductPromotion);
    }

    private function writeLog(
        \Ess\M2ePro\Model\Listing\Product $listingProduct,
        string $message,
        int $type
    ): void {
        /** @var \Ess\M2ePro\Model\Ebay\Listing\Log $log */
        $log = $this->logFactory->create();
        $log->setComponentMode($listingProduct->getComponentMode());

        $log->addProductMessage(
            $listingProduct->getListingId(),
            $listingProduct->getProductId(),
            $listingProduct->getId(),
            \Ess\M2ePro\Helper\Data::INITIATOR_USER,
            null,
            \Ess\M2ePro\Model\Listing\Log::ACTION_PROMOTION,
            $message,
            $type
        );
    }

    private function loadListingProduct(int $listingProductId): \Ess\M2ePro\Model\Listing\Product
    {
        $listingProduct = $this->listingProductFactory->create();
        $listingProduct->load($listingProductId);

        return $listingProduct;
    }

    private function removeListingProductPromotion(
        \Ess\M2ePro\Model\Ebay\Promotion $promotion,
        \Ess\M2ePro\Model\Ebay\Listing\Product\Promotion $listingProductPromotion
    ): void {
        $this->repository->removeListingProductPromotion($listingProductPromotion);

        $listingProduct = $this->loadListingProduct($listingProductPromotion->getListingProductId());

        $message = (string)__(
            'Item was removed from Discount "%discount_name"',
            ['discount_name' => $promotion->getName()]
        );
        $this->writeLog($listingProduct, $message, \Ess\M2ePro\Model\Log\AbstractModel::TYPE_SUCCESS);
    }

    /**
     * @param \Ess\M2ePro\Model\Listing\Product[] $selectedListingProducts
     * @param array<string, string> $itemsWithErrors
     *
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function isAllItemsHaveErrors(array $selectedListingProducts, array $itemsWithErrors): bool
    {
        $allHaveErrors = true;
        foreach ($selectedListingProducts as $listingProduct) {
            $itemId = $listingProduct->getChildObject()->getEbayItem()->getItemId();
            if (!isset($itemsWithErrors[$itemId])) {
                $allHaveErrors = false;
                break;
            }
        }

        return $allHaveErrors;
    }

    /**
     * @param \Ess\M2ePro\Model\Ebay\Promotion $promotion
     * @param \Ess\M2ePro\Model\Ebay\Listing\Product\Promotion[] $existListingProductPromotions
     * @param \Ess\M2ePro\Model\Listing\Product[] $selectedListingProducts
     *
     * @return void
     */
    private function removeUnselectedListingProductPromotions(
        \Ess\M2ePro\Model\Ebay\Promotion $promotion,
        array $existListingProductPromotions,
        array $selectedListingProducts
    ): void {
        foreach ($existListingProductPromotions as $existListingProductPromotion) {
            if (isset($selectedListingProducts[$existListingProductPromotion->getListingProductId()])) {
                continue;
            }

            $this->removeListingProductPromotion($promotion, $existListingProductPromotion);
        }
    }
}
