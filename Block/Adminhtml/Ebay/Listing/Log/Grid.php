<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Log;

class Grid extends \Ess\M2ePro\Block\Adminhtml\Listing\Log\Grid
{
    //########################################

    protected function getComponentMode()
    {
        return \Ess\M2ePro\Helper\Component\Ebay::NICK;
    }

    //########################################

    protected function getActionTitles()
    {
        $allActions = $this->activeRecordFactory->getObject('Listing\Log')->getActionsTitles();
        $excludeActions = array(
            \Ess\M2ePro\Model\Listing\Log::ACTION_DELETE_AND_REMOVE_PRODUCT => '',
            \Ess\M2ePro\Model\Listing\Log::ACTION_DELETE_PRODUCT_FROM_COMPONENT => '',
            \Ess\M2ePro\Model\Listing\Log::ACTION_NEW_SKU_PRODUCT_ON_COMPONENT => '',
            \Ess\M2ePro\Model\Listing\Log::ACTION_SWITCH_TO_AFN_ON_COMPONENT => '',
            \Ess\M2ePro\Model\Listing\Log::ACTION_SWITCH_TO_MFN_ON_COMPONENT => ''
        );

        return array_diff_key($allActions, $excludeActions);
    }

    //########################################
}