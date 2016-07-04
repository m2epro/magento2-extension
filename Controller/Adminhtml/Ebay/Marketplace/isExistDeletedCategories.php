<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Marketplace;

use Ess\M2ePro\Controller\Adminhtml\Ebay\Marketplace;

class isExistDeletedCategories extends Marketplace
{
    //########################################

    public function execute()
    {
        if ($this->getHelper('Component\Ebay\Category\Ebay')->isExistDeletedCategories()) {
            $this->setAjaxContent('1');
        } else {
            $this->setAjaxContent('0');
        }

        return $this->getResult();
    }

    //########################################
}