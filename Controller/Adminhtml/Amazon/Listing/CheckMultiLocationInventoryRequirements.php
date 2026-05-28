<?php

declare(strict_types=1);

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing;

class CheckMultiLocationInventoryRequirements extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing
{
    private \Ess\M2ePro\Model\Amazon\Template\SellingFormat\Repository $sellingPolicyRepository;

    public function __construct(
        \Ess\M2ePro\Model\Amazon\Template\SellingFormat\Repository $sellingPolicyRepository,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($amazonFactory, $context);
        $this->sellingPolicyRepository = $sellingPolicyRepository;
    }

    public function execute()
    {
        $sellingPolicy = $this->getSellingTemplateFromRequest();
        $marketplaceId = $this->getMarketplaceIdFromRequest();

        $isNotAvailableSellingPolicy =  $marketplaceId !== \Ess\M2ePro\Helper\Component\Amazon::MARKETPLACE_US
            && $sellingPolicy->getChildObject()->isQtyModeMultiLocationInventory();

        if ($isNotAvailableSellingPolicy) {
            $this->setJsonContent([
                'success' => false,
                'message' => \__(
                    'This Selling Policy cannot be assigned to the selected Listing ' .
                    'because it has Multi Location Inventory enabled, which is supported for Amazon US ' .
                    'marketplace Listings only.'
                ),
            ]);

            return $this->getResult();
        }

        $this->setJsonContent(['success' => true]);

        return $this->getResult();
    }

    /**
     * @throws \Ess\M2ePro\Model\Exception\EntityNotFound
     */
    private function getSellingTemplateFromRequest(): \Ess\M2ePro\Model\Template\SellingFormat
    {
        $sellingPolicyId = (int)$this->getRequest()->getParam('selling_policy_id');

        return $this->sellingPolicyRepository->get($sellingPolicyId);
    }

    /**
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function getMarketplaceIdFromRequest(): int
    {
        $marketplaceId = (int)$this->getRequest()->getParam('marketplace_id');
        if (empty($marketplaceId)) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Parameter marketplace_id is required.');
        }

        return $marketplaceId;
    }
}
