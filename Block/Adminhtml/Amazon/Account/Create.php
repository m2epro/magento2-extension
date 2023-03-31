<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Account;

class Create extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractContainer
{
    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('amazonAccountCreate');
        $this->_controller = 'adminhtml_amazon_account';
        $this->_mode = 'create';
        // ---------------------------------------

        // Set buttons actions
        // ---------------------------------------
        $this->removeButton('back');
        $this->removeButton('reset');
        $this->removeButton('delete');
        $this->removeButton('add');
        $this->removeButton('save');
        $this->removeButton('edit');
        // ---------------------------------------

        $this->addButton('continue', [
            'label' => $this->__('Continue'),
            'onclick' => 'AmazonAccountCreateObj.continueClick()',
            'class' => 'action-primary',
        ]);
    }
}
