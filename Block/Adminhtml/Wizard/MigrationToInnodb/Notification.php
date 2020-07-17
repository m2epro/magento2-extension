<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Wizard\MigrationToInnodb;

/**
 * Class  \Ess\M2ePro\Block\Adminhtml\Wizard\MigrationToInnodb\Notification
 */
class Notification extends \Ess\M2ePro\Block\Adminhtml\Magento\AbstractContainer
{
    //########################################

    protected function _prepareLayout()
    {
        $this->css->addFile('wizard.css');

        return parent::_prepareLayout();
    }

    //########################################

    protected function _beforeToHtml()
    {
        $this->setTemplate('wizard/' . $this->getNick() . '/notification.phtml');

        return parent::_beforeToHtml();
    }

    //########################################
}
