<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Amazon\Settings
 */
class Settings extends \Ess\M2ePro\Block\Adminhtml\Magento\AbstractContainer
{
    //########################################

    protected function _construct()
    {
        parent::_construct();

        // Set buttons actions
        // ---------------------------------------
        $this->removeButton('back');
        $this->removeButton('reset');
        $this->removeButton('delete');
        $this->removeButton('add');
        $this->removeButton('save');
        $this->removeButton('edit');

        $this->addButton('save', [
            'label'     => $this->__('Save'),
            'onclick'   => 'SettingsObj.saveSettingsTab()',
            'class'     => 'primary'
        ]);
    }

    //########################################

    protected function _toHtml()
    {
        return parent::_toHtml().'<div id="tabs_container"></div>';
    }

    //########################################
}
