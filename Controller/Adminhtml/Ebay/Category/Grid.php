<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Category;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Category\Grid
 */
class Grid extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Category
{
    //########################################

    public function execute()
    {
        $this->setAjaxContent($this->createBlock('Ebay_Category_Grid'));
        return $this->getResult();
    }

    //########################################
}
