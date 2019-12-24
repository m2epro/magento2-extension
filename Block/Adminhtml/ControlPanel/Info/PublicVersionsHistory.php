<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\ControlPanel\Info;

use Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\ControlPanel\Info\PublicVersionsHistory
 */
class PublicVersionsHistory extends AbstractBlock
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('controlPanelInfoPublicVersionsHistory');
        // ---------------------------------------

        $this->setTemplate('control_panel/info/public_versions_history.phtml');
    }

    //########################################

    protected function _beforeToHtml()
    {
        $collection = $this->activeRecordFactory->getObject('VersionsHistory')->getCollection();
        $collection->setOrder('create_date', $collection::SORT_ORDER_DESC);

        $this->versionsData = $collection->toArray()['items'];

        return parent::_beforeToHtml();
    }

    //########################################
}
