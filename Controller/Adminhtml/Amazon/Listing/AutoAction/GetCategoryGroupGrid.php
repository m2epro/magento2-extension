<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\AutoAction;

use Ess\M2ePro\Block\Adminhtml\Amazon\Listing\AutoAction\Mode\Category\Group\Grid;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\AutoAction\GetCategoryGroupGrid
 */
class GetCategoryGroupGrid extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\AutoAction
{
    //########################################

    public function execute()
    {
        $grid = $this->getLayout()->createBlock(Grid::class);
        $this->setAjaxContent($grid);
        return $this->getResult();
    }

    //########################################
}
