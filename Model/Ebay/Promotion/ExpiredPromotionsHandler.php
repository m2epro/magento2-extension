<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Promotion;

class ExpiredPromotionsHandler
{
    private \Ess\M2ePro\Model\Ebay\Promotion\Repository $repository;
    private \Ess\M2ePro\Model\Listing\ProductFactory $listingProductFactory;
    private \Ess\M2ePro\Model\Listing\Log\Factory $logFactory;

    public function __construct(
        \Ess\M2ePro\Model\Ebay\Promotion\Repository $repository,
        \Ess\M2ePro\Model\Listing\ProductFactory $listingProductFactory,
        \Ess\M2ePro\Model\Listing\Log\Factory $logFactory
    ) {
        $this->repository = $repository;
        $this->listingProductFactory = $listingProductFactory;
        $this->logFactory = $logFactory;
    }

    public function process(
        \Ess\M2ePro\Model\Ebay\Account $ebayAccount,
        \Ess\M2ePro\Model\Marketplace $marketplace
    ): void {
        $expiredPromotions = $this->repository->findExpiredPromotions();

        if (empty($expiredPromotions)) {
            return;
        }

        foreach ($expiredPromotions as $promotion) {
            $this->removeExpiredPromotion($promotion, $ebayAccount, $marketplace);
        }
    }

    private function removeExpiredPromotion(
        \Ess\M2ePro\Model\Ebay\Promotion $promotion,
        \Ess\M2ePro\Model\Ebay\Account $ebayAccount,
        \Ess\M2ePro\Model\Marketplace $marketplace
    ): void {
        $listingProductPromotions = $this->repository->findListingProductsByAccountAndMarketplaceAndPromotion(
            (int)$ebayAccount->getId(),
            (int)$marketplace->getId(),
            $promotion->getId()
        );

        foreach ($listingProductPromotions as $listingProductPromotion) {
            $this->repository->removeListingProductPromotion($listingProductPromotion);

            $listingProduct = $this->loadListingProduct((int)$listingProductPromotion->getListingProductId());

            $this->writeLog($listingProduct, $promotion);
        }

        $this->repository->remove($promotion);
    }

    private function loadListingProduct(int $listingProductId): \Ess\M2ePro\Model\Listing\Product
    {
        $listingProduct = $this->listingProductFactory->create();
        $listingProduct->load($listingProductId);

        return $listingProduct;
    }

    private function writeLog(
        \Ess\M2ePro\Model\Listing\Product $listingProduct,
        \Ess\M2ePro\Model\Ebay\Promotion $promotion
    ): void {
        /** @var \Ess\M2ePro\Model\Ebay\Listing\Log $log */
        $log = $this->logFactory->create();
        $log->setComponentMode($listingProduct->getComponentMode());
        $log->addProductMessage(
            $listingProduct->getListingId(),
            $listingProduct->getProductId(),
            $listingProduct->getId(),
            \Ess\M2ePro\Helper\Data::INITIATOR_EXTENSION,
            null,
            \Ess\M2ePro\Model\Listing\Log::ACTION_PROMOTION,
            (string)__(
                'Promotion "%promotion_name" has ended. The Item is no longer being promoted',
                ['promotion_name' => $promotion->getName()]
            ),
            \Ess\M2ePro\Model\Log\AbstractModel::TYPE_INFO
        );
    }
}
