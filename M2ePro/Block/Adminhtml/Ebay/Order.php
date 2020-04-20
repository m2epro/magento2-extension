<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay;

use Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractContainer;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Ebay\Order
 */
class Order extends AbstractContainer
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('ebayOrder');
        $this->_controller = 'adminhtml_ebay_order';
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
    }

    //########################################

    protected function _prepareLayout()
    {
        $this->appendHelpBlock([
            'content' => $this->__(
                <<<HTML
                <p>In this section, you can find the list of the Orders imported from eBay. </p>
                <p>An eBay Order, for which Magento Order is created, contains a value in
                <strong>Magento Order #</strong> column of the grid. You can find the corresponding
                Magento Order in Sales > Orders section of your Magento</p><br>

                <p>To manage the imported eBay Orders, you can use Mass Action options available in the
                Actions bulk: Reserve QTY, Cancel QTY Reserve, Mark Order(s) as Shipped or Paid and Resend
                Shipping Information.</p><br>

                <p>Also, you can view the detailed Order information by clicking on the appropriate
                row of the grid.</p><br>

                <p><strong>Note:</strong> Automatic creation of Magento Orders, Invoices, and Shipments is
                performed in accordance with the Order settings specified in <br>
                <strong>Account Settings (eBay Integration > Configuration > Accounts)</strong>. </p>
HTML
            ),
        ]);

        // ---------------------------------------

        $this->setPageActionsBlock('Ebay_Order_PageActions');

        return parent::_prepareLayout();
    }

    public function getGridHtml()
    {
        // ---------------------------------------
        $editItemBlock = $this->createBlock('Order_Item_Edit');
        // ---------------------------------------

        return
          $editItemBlock->toHtml()
        . parent::getGridHtml();
    }

    protected function _beforeToHtml()
    {
        $this->jsPhp->addConstants(
            $this->getHelper('Data')->getClassConstants(\Ess\M2ePro\Controller\Adminhtml\Order\EditItem::class)
        );

        return parent::_beforeToHtml();
    }
}
