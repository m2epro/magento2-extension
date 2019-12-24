<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Log\Listing\Other\View\Separated;

use Ess\M2ePro\Block\Adminhtml\Log\Listing\View;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Log\Listing\Other\View\Separated\AbstractGrid
 */
abstract class AbstractGrid extends \Ess\M2ePro\Block\Adminhtml\Log\Listing\Other\AbstractGrid
{
    //########################################

    protected function getViewMode()
    {
        return View\Switcher::VIEW_MODE_SEPARATED;
    }

    // ---------------------------------------

    protected function _prepareCollection()
    {
        $collection = $this->activeRecordFactory->getObject('Listing_Other_Log')->getCollection();

        $this->applyFilters($collection);

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    //########################################
}
