<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Template\Description;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Template\Description;

class GetVariationThemes extends Description
{
    //########################################

    public function execute()
    {
        $model = $this->modelFactory->getObject('Amazon\Marketplace\Details');
        $model->setMarketplaceId($this->getRequest()->getParam('marketplace_id'));

        $variationThemes = $model->getVariationThemes($this->getRequest()->getParam('product_data_nick'));
        $this->setJsonContent($variationThemes);
        return $this->getResult();
    }

    //########################################
}