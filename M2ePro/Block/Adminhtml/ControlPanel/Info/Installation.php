<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\ControlPanel\Info;

use Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\ControlPanel\Info\Installation
 */
class Installation extends AbstractBlock
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('controlPanelInfoInstallation');
        // ---------------------------------------

        $this->setTemplate('control_panel/info/installation.phtml');
    }

    //########################################

    protected function _beforeToHtml()
    {
        $collection = $this->activeRecordFactory->getObject('Setup')->getCollection();
        $collection->setOrder('create_date', $collection::SORT_ORDER_DESC);

        $this->setupData = $collection->toArray()['items'];

        return parent::_beforeToHtml();
    }

    //########################################
}
