<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\AutoAction;

class GetProductTypesList extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\AutoAction
{
    private \Ess\M2ePro\Model\Amazon\Template\ProductType\Repository $amazonTemplateProductTypeRepository;

    public function __construct(
        \Ess\M2ePro\Model\Amazon\Template\ProductType\Repository $amazonTemplateProductTypeRepository,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($amazonFactory, $context);
        $this->amazonTemplateProductTypeRepository = $amazonTemplateProductTypeRepository;
    }

    public function execute()
    {
        $marketplaceId = $this->getRequest()->getParam('marketplace_id', '');
        if (empty($marketplaceId)) {
            $this->setJsonContent([]);

            return $this->getResult();
        }

        $result = [];
        foreach ($this->amazonTemplateProductTypeRepository->findByMarketplaceId((int)$marketplaceId) as $template) {
            $result[] = [
                'id' => $template->getId(),
                'title' => $template->getTitle(),
            ];
        }

        $this->setJsonContent($result);

        return $this->getResult();
    }
}
