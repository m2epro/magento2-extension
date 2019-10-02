<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Other;

/**
 * Class Grid
 * @package Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Other
 */
class Grid extends \Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Other
{
    public function execute()
    {
        $this->setAjaxContent($this->createBlock('Walmart_Listing_Other_View_Grid'));
        return $this->getResult();
    }
}
