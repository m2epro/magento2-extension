<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\AutoAction;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\AutoAction\GetCategoryGroupGrid
 */
class GetCategoryGroupGrid extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\AutoAction
{
    //########################################

    public function execute()
    {
        $grid = $this->getLayout()
                     ->createBlock(\Ess\M2ePro\Block\Adminhtml\Ebay\Listing\AutoAction\Mode\Category\Group\Grid::class);
        $this->setAjaxContent($grid);
        return $this->getResult();
    }

    //########################################
}
