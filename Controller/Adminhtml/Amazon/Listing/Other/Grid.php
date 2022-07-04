<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Other;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Other\Grid
 */
class Grid extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Other
{
    public function execute()
    {
        $this->setAjaxContent(
            $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Other\View\Grid::class)
        );
        return $this->getResult();
    }
}
