<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Walmart;

class Account extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractContainer
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
        $this->removeButton('save');
        $this->removeButton('edit');

        // ---------------------------------------

        $this->_controller = 'adminhtml_walmart_account';

        $url = $this->getUrl('*/walmart_account/new');
        $this->buttonList->update('add', 'label', $this->__('Add Account'));
        $this->buttonList->update('add', 'onclick', 'setLocation(\''.$url.'\');');
    }

    protected function _prepareLayout()
    {
        $url = $this->getHelper('Module\Support')->getDocumentationArticleUrl('x/XgBhAQ');
        $this->appendHelpBlock([
            'content' => $this->__(<<<HTML
<p>In this section, you can create, edit and delete Accounts for Walmart integration</p><br>
<p>Please be advised that Account is created per Marketplace using the relevant API credentials.</p><br>
<p>The detailed information can be found <a href="{$url}" target="_blank">here</a></p><br>
HTML
)
        ]);

        return parent::_prepareLayout();
    }

    //########################################
}