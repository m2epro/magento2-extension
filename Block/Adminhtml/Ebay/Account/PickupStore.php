<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Account;

use Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractContainer;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Ebay\Account\PickupStore
 */
class PickupStore extends AbstractContainer
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('ebayAccountPickupStore');
        $this->_controller = 'adminhtml_ebay_account_pickupStore';
        // ---------------------------------------

        // Set buttons actions
        // ---------------------------------------
        $this->removeButton('back');
        $this->removeButton('reset');
        $this->removeButton('delete');
        $this->removeButton('save');
        $this->removeButton('edit');
        // ---------------------------------------

        // ---------------------------------------
        $this->addButton('back', [
            'label'   => $this->__('Back'),
            'onclick' => 'setLocation(\''.$this->getUrl('*/ebay_account/index').'\');',
            'class'   => 'back'
        ]);
        // ---------------------------------------

        // ---------------------------------------
        $this->addButton('add', [
            'label'   => $this->__('Add Store'),
            'onclick' => 'setLocation(\''.$this->getUrl(
                '*/ebay_account_pickupStore/new',
                ['_current' => true]
            ).'\');',
            'class'   => 'add primary'
        ]);
        // ---------------------------------------
    }

    //########################################

    protected function _prepareLayout()
    {
        $this->appendHelpBlock([
            'content' => $this->__(
                'In order to provide In-Store Pickup Shipping Service, first, you need to
                <strong>add the information</strong>
                about the Stores. Then, you will be able to <strong>specify Products availability</strong>
                for each of the
                created Stores. The Buyers will see the Item marked as available for In-Store Pickup depending on the
                distance to their location.<br/><br/>

                This Service is available for 3 marketplaces — Australia, United Stated and United Kingdom.'
            )
        ]);
        return parent::_prepareLayout();
    }

    //########################################
}
