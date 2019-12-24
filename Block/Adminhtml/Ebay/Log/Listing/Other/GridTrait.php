<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Log\Listing\Other;

/**
 * Trait \Ess\M2ePro\Block\Adminhtml\Ebay\Log\Listing\Other\GridTrait
 */
trait GridTrait
{
    //########################################

    protected function getComponentMode()
    {
        return \Ess\M2ePro\Helper\Component\Ebay::NICK;
    }

    protected function getColumnTitles()
    {
        return [
            'create_date' => 'Creation Date',
            'identifier' => 'Item',
            'title' => 'Title',
            'action' => 'Action',
            'description' => 'Message',
            'initiator' => 'Run Mode',
            'type' => 'Type'
        ];
    }

    //########################################
}
