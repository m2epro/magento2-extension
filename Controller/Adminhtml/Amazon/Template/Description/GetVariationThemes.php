<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Template\Description;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Template\Description;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Template\Description\GetVariationThemes
 */
class GetVariationThemes extends Description
{
    //########################################

    public function execute()
    {
        $model = $this->modelFactory->getObject('Amazon_Marketplace_Details');
        $model->setMarketplaceId($this->getRequest()->getParam('marketplace_id'));

        $variationThemes = $model->getVariationThemes($this->getRequest()->getParam('product_data_nick'));
        $this->setJsonContent($variationThemes);
        return $this->getResult();
    }

    //########################################
}
