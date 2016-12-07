<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2016 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Log\Listing\Other\View\Separated;

use Ess\M2ePro\Block\Adminhtml\Log\Listing\View;

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
        $collection = $this->activeRecordFactory->getObject('Listing\Other\Log')->getCollection();

        $this->applyFilters($collection);

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    //########################################
}