<?php

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\AutoAction;

class GetProductTypesList extends \Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\AutoAction
{
    private \Ess\M2ePro\Model\Walmart\ProductType\Repository $productTypeRepository;

    public function __construct(
        \Ess\M2ePro\Model\Walmart\ProductType\Repository $productTypeRepository,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($walmartFactory, $context);

        $this->productTypeRepository = $productTypeRepository;
    }

    public function execute()
    {
        $marketplaceId = $this->getRequest()->getParam('marketplace_id', '');
        if (empty($marketplaceId)) {
            $this->setJsonContent([]);

            return $this->getResult();
        }

        $result = [];
        foreach ($this->productTypeRepository->retrieveByMarketplaceId((int)$marketplaceId) as $template) {
            $result[] = [
                'id' => $template->getId(),
                'title' => $template->getTitle(),
            ];
        }

        $this->setJsonContent($result);

        return $this->getResult();
    }
}
