<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Listing\Auto\Actions\Mode;

class DuplicateProducts
{
    /** @var DuplicateProducts\Repository */
    private $repository;
    /** @var \Ess\M2ePro\Helper\Component\Ebay\Configuration */
    private $ebayConfig;
    /** @var \Ess\M2ePro\Model\Listing\LogFactory */
    private $logFactory;

    public function __construct(
        DuplicateProducts\Repository $repository,
        \Ess\M2ePro\Helper\Component\Ebay\Configuration $ebayConfig,
        \Ess\M2ePro\Model\Listing\LogFactory $logFactory
    ) {
        $this->repository = $repository;
        $this->ebayConfig = $ebayConfig;
        $this->logFactory = $logFactory;
    }

    /**
     * @param \Ess\M2ePro\Model\Listing $listing
     * @param \Magento\Catalog\Model\Product $magentoProduct
     *
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function checkDuplicateListingProduct(
        \Ess\M2ePro\Model\Listing $listing,
        \Magento\Catalog\Model\Product $magentoProduct
    ): bool {
        if ($listing->isComponentModeEbay() && !$this->ebayConfig->isEnablePreventItemDuplicatesMode()) {
            return false;
        }

        $listingProductIdsArr = $this->repository->getListingProductIds($listing, $magentoProduct);

        if ($listingProductIdsArr === []) {
            return false;
        }

        foreach ($listingProductIdsArr as $listingProductId) {
            $this->addLog(
                $listingProductId,
                $listing,
                $magentoProduct
            );
        }

        return true;
    }

    /**
     * @param int $listingProductId
     * @param \Ess\M2ePro\Model\Listing $listing
     * @param \Magento\Catalog\Model\Product $magentoProduct
     *
     * @return void
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function addLog(
        int $listingProductId,
        \Ess\M2ePro\Model\Listing $listing,
        \Magento\Catalog\Model\Product $magentoProduct
    ): void {
        $message = 'Product was not added since the item is already presented in another Listing related to ' .
            'the Channel account and marketplace.';

        $logModel = $this->logFactory->create();
        $logModel->setComponentMode($listing->getComponentMode());
        $logModel->addProductMessage(
            $listing->getId(),
            $magentoProduct->getId(),
            $listingProductId,
            \Ess\M2ePro\Helper\Data::INITIATOR_EXTENSION,
            $logModel->getResource()->getNextActionId(),
            \Ess\M2ePro\Model\Listing\Log::ACTION_ADD_PRODUCT_TO_LISTING,
            $message,
            \Ess\M2ePro\Model\Log\AbstractModel::TYPE_INFO
        );
    }
}
