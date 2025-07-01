<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\PromotedListing\Campaign;

class Logger
{
    private \Ess\M2ePro\Model\Listing\Log\Factory $logFactory;

    public function __construct(\Ess\M2ePro\Model\Listing\Log\Factory $logFactory)
    {
        $this->logFactory = $logFactory;
    }

    public function addSuccessLog(\Ess\M2ePro\Model\Listing\Product $listingProduct, string $message)
    {
        $this->addLogToProduct(
            $listingProduct,
            \Ess\M2ePro\Model\Log\AbstractModel::TYPE_SUCCESS,
            $message
        );
    }

    public function addErrorLog(\Ess\M2ePro\Model\Listing\Product $listingProduct, string $message)
    {
        $this->addLogToProduct(
            $listingProduct,
            \Ess\M2ePro\Model\Log\AbstractModel::TYPE_ERROR,
            $message
        );
    }

    public function addWarningLog(\Ess\M2ePro\Model\Listing\Product $listingProduct, string $message)
    {
        $this->addLogToProduct(
            $listingProduct,
            \Ess\M2ePro\Model\Log\AbstractModel::TYPE_WARNING,
            $message
        );
    }

    private function addLogToProduct(
        \Ess\M2ePro\Model\Listing\Product $listingProduct,
        int $type,
        string $message
    ) {
        /** @var \Ess\M2ePro\Model\Ebay\Listing\Log $log */
        $log = $this->logFactory->create();
        $log->setComponentMode($listingProduct->getComponentMode());

        $log->addProductMessage(
            $listingProduct->getListingId(),
            $listingProduct->getProductId(),
            $listingProduct->getId(),
            \Ess\M2ePro\Helper\Data::INITIATOR_USER,
            null,
            \Ess\M2ePro\Model\Listing\Log::ACTION_EBAY_PROMOTED_LISTING_CAMPAIGNS,
            $message,
            $type
        );
    }
}
