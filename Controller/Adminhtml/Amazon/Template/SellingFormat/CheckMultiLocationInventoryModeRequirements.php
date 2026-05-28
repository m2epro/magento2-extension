<?php

declare(strict_types=1);

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Template\SellingFormat;

class CheckMultiLocationInventoryModeRequirements extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing
{
    private \Ess\M2ePro\Helper\Magento $magentoHelper;
    private \Magento\Framework\ObjectManagerInterface $objectManager;
    private \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder;
    private \Ess\M2ePro\Model\Amazon\Listing\Repository $listingRepository;

    public function __construct(
        \Ess\M2ePro\Helper\Magento $magentoHelper,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Ess\M2ePro\Model\Amazon\Listing\Repository $listingRepository,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($amazonFactory, $context);
        $this->magentoHelper = $magentoHelper;
        $this->objectManager = $objectManager;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->listingRepository = $listingRepository;
    }

    public function execute()
    {
        if (
            !$this->magentoHelper->isMSISupportingVersion()
            || $this->isOnlyDefaultMsiUsed()
        ) {
            $message = \__(
                'To enable location-based mapping, make sure Multi-Source Inventory (MSI) ' .
                'is enabled in Magento and multiple inventory sources are configured.'
            );

            $this->setJsonContent(['success' => false, 'message' => $message]);

            return $this->getResult();
        }

        $sellingPolicyId = $this->getSellingPolicyIdFromRequest();
        if (
            !empty($sellingPolicyId)
            && !$this->listingRepository->isSellingPolicyUseOnlyForUsMarketplaces($sellingPolicyId)
        ) {
            $message = __(
                '<p>Amazon Multi Location Inventory is supported only for Amazon US marketplace, ' .
                'but this Selling Policy is assigned to non-US Listings. To avoid inconsistent behavior:</p>' .
                '<ul style="padding-left: 20px">' .
                '<li>assign a different policy to non-US Listings, or</li>' .
                '<li>disable the "Use Multi Location Inventory" option.</li></ul>'
            );

            $this->setJsonContent(['success' => false, 'message' => $message]);

            return $this->getResult();
        }

        $this->setJsonContent(['success' => true]);

        return $this->getResult();
    }

    private function isOnlyDefaultMsiUsed(): bool
    {
        if (!$this->magentoHelper->isMSISupportingVersion()) {
            return false;
        }

        $searchCriteria = $this->searchCriteriaBuilder->create();

        $sources = $this->objectManager
            ->get(\Magento\InventoryApi\Api\SourceRepositoryInterface::class)
            ->getList($searchCriteria)
            ->getItems();

        $stocks = $this->objectManager
            ->get(\Magento\InventoryApi\Api\StockRepositoryInterface::class)
            ->getList($searchCriteria)
            ->getItems();

        if (
            \count($sources) !== 1
            || \count($stocks) !== 1
        ) {
            return false;
        }

        $defaultSource = reset($sources);
        $defaultStock = reset($stocks);

        return $defaultSource->getSourceCode() === 'default'
            && (int)$defaultStock->getStockId() === 1;
    }

    private function getSellingPolicyIdFromRequest(): ?int
    {
        $value = $this->getRequest()->getParam('selling_template_id');
        if (empty($value)) {
            return null;
        }

        return (int)$value;
    }
}
