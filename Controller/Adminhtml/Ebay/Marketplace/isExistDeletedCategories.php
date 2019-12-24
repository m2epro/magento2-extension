<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Marketplace;

use Ess\M2ePro\Controller\Adminhtml\Ebay\Marketplace;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Marketplace\isExistDeletedCategories
 */
class isExistDeletedCategories extends Marketplace
{
    //########################################

    public function execute()
    {
        if ($this->getHelper('Component_Ebay_Category_Ebay')->isExistDeletedCategories()) {
            $this->setAjaxContent('1');
        } else {
            $this->setAjaxContent('0');
        }

        return $this->getResult();
    }

    //########################################
}
