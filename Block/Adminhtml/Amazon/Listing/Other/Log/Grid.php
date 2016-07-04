<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Other\Log;

class Grid extends \Ess\M2ePro\Block\Adminhtml\Listing\Other\Log\Grid
{
    //########################################

    protected function getComponentMode()
    {
        return \Ess\M2ePro\Helper\Component\Amazon::NICK;
    }

    //########################################

    protected function getColumnTitles()
    {
        return array(
            'create_date' => $this->__('Creation Date'),
            'identifier' => $this->__('Identifier'),
            'title' => $this->__('Title'),
            'action' => $this->__('Action'),
            'description' => $this->__('Description'),
            'initiator' => $this->__('Run Mode'),
            'type' => $this->__('Type')
        );
    }

    //########################################

    protected function getActionTitles()
    {
        return $this->activeRecordFactory->getObject('Listing\Other\Log')->getActionsTitles();
    }

    //########################################
}