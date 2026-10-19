<?php

declare(strict_types=1);

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Template;

class GroupByMarketplace extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Template
{
    private \Ess\M2ePro\Model\Ebay\Listing\Product\GroupListingProductsByMarketplaces $groupListingProductsByMarketplaces;

    public function __construct(
        \Ess\M2ePro\Model\Ebay\Listing\Product\GroupListingProductsByMarketplaces $groupListingProductsByMarketplaces,
        \Ess\M2ePro\Model\Ebay\Template\Manager $templateManager,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($templateManager, $ebayFactory, $context);
        $this->groupListingProductsByMarketplaces = $groupListingProductsByMarketplaces;
    }

    public function execute()
    {
        $ids = $this->getRequestIds('product_id');
        if (empty($ids)) {
            $this->setJsonContent([
                'success' => false,
                'message' => __('No products were found.'),
            ]);

            return $this->getResult();
        }

        if (is_string($ids)) {
            $ids = explode(',', $ids);
        }

        $result = $this->groupListingProductsByMarketplaces->execute($ids);

        $this->setJsonContent([
            'success' => true,
            'data' => $result
        ]);

        return $this->getResult();
    }
}
