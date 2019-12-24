<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Walmart;

use Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractContainer;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Walmart\Order
 */
class Order extends AbstractContainer
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        $this->_controller = 'adminhtml_walmart_order';

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

    protected function getHelpBlockJavascript()
    {
        if (!$this->getRequest()->isXmlHttpRequest()) {
            return '';
        }

        return <<<HTML
<script type="text/javascript">
    setTimeout(function() {
        OrderHandlerObj.initializeGrids();
    }, 50);
</script>
HTML;
    }

    //########################################

    protected function _prepareLayout()
    {
        $this->appendHelpBlock([
            'content' => $this->__(
                <<<HTML
                <p>On this page, you can review the Channel Sales imported from Walmart.
                In the grid below, filter the records to narrow your search results,
                then click the Order line to review the details. Use the Action menu to manage
                each Channel Order individually or update them in bulk.
                </p><br>
                <p><strong>Note:</strong> you can enable an automatic creation of Magento Orders, Invoices,
                and Shipments in the Account Configuration under
                <i>Walmart Integration > Configuration > Accounts > Edit Account > Orders</i>.</p>
HTML
            )
        ]);

        // ---------------------------------------

        $this->setPageActionsBlock('Walmart_Order_PageActions');

        return parent::_prepareLayout();
    }

    public function getGridHtml()
    {
        $editItemBlock = $this->createBlock('Order_Item_Edit');

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

    //########################################
}
