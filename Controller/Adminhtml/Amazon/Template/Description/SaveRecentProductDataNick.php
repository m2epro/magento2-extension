<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Template\Description;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Template\Description;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Template\Description\SaveRecentProductDataNick
 */
class SaveRecentProductDataNick extends Description
{
    //########################################

    public function execute()
    {
        $marketplaceId   = $this->getRequest()->getPost('marketplace_id');
        $productDataNick = $this->getRequest()->getPost('product_data_nick');

        if (!$marketplaceId || !$productDataNick) {
            $this->setJsonContent(['result' => false]);
            return $this->getResult();
        }

        $this->getHelper('Component_Amazon_ProductData')->addRecent($marketplaceId, $productDataNick);
        $this->setJsonContent(['result' => true]);
        return $this->getResult();
    }

    //########################################
}
