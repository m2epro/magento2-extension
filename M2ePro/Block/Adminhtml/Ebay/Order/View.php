<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Order;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractContainer;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Ebay\Order\View
 */
class View extends AbstractContainer
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('ebayOrderView');
        $this->_controller = 'adminhtml_ebay_order';
        $this->_mode = 'view';
        // ---------------------------------------

        /** @var $order \Ess\M2ePro\Model\Order */
        $order = $this->getHelper('Data\GlobalData')->getValue('order');

        // Set buttons actions
        // ---------------------------------------
        $this->removeButton('back');
        $this->removeButton('reset');
        $this->removeButton('delete');
        $this->removeButton('add');
        $this->removeButton('save');
        $this->removeButton('edit');
        // ---------------------------------------

        // ---------------------------------------
        $url = $this->getHelper('Data')->getBackUrl('*/ebay_order/index');
        $this->addButton('back', [
            'label'     => $this->__('Back'),
            'onclick'   => 'CommonObj.backClick(\''.$url.'\')',
            'class'     => 'back'
        ]);
        // ---------------------------------------

        if ($order->getChildObject()->canUpdateShippingStatus()) {
            // ---------------------------------------
            $url = $this->getUrl('*/*/updateShippingStatus', ['id' => $order->getId()]);
            $this->addButton('ship', [
                'label'     => $this->__('Mark as Shipped'),
                'onclick'   => "setLocation('".$url."');",
                'class'     => 'primary'
            ]);
            // ---------------------------------------
        }

        if ($order->getChildObject()->canUpdatePaymentStatus()) {
            // ---------------------------------------
            $url = $this->getUrl('*/*/updatePaymentStatus', ['id' => $order->getId()]);
            $this->addButton('pay', [
                'label'     => $this->__('Mark as Paid'),
                'onclick'   => "setLocation('".$url."');",
                'class'     => 'primary'
            ]);
            // ---------------------------------------
        }

        if ($order->getReserve()->isPlaced()) {
            // ---------------------------------------
            $url = $this->getUrl('*/order/reservationCancel', ['ids' => $order->getId()]);
            $this->addButton('reservation_cancel', [
                'label'     => $this->__('Cancel QTY Reserve'),
                'onclick'   => "confirmSetLocation(M2ePro.translator.translate('Are you sure?'), '".$url."');",
                'class'     => 'primary'
            ]);
            // ---------------------------------------
        } elseif ($order->isReservable()) {
            // ---------------------------------------
            $url = $this->getUrl('*/order/reservationPlace', ['ids' => $order->getId()]);
            $this->addButton('reservation_place', [
                'label'     => $this->__('Reserve QTY'),
                'onclick'   => "confirmSetLocation(M2ePro.translator.translate('Are you sure?'), '".$url."');",
                'class'     => 'primary'
            ]);
            // ---------------------------------------
        }

        if ($order->getMagentoOrderId() === null) {
            // ---------------------------------------
            $url = $this->getUrl('*/*/createMagentoOrder', ['id' => $order->getId()]);
            $this->addButton('order', [
                'label'     => $this->__('Create Magento Order'),
                'onclick'   => "setLocation('".$url."');",
                'class'     => 'primary'
            ]);
            // ---------------------------------------
        } elseif ($order->getMagentoOrder() === null || $order->getMagentoOrder()->isCanceled()) {
            // ---------------------------------------
            $url = $this->getUrl('*/*/createMagentoOrder', ['id' => $order->getId(), 'force' => 'yes']);
            $confirm = $this->getHelper('Data')->escapeJs(
                $this->__('Are you sure that you want to create new Magento Order?')
            );

            $this->addButton('order', [
                'label'     => $this->__('Create Magento Order'),
                'onclick'   => "confirmSetLocation('".$confirm."','".$url."');",
                'class'     => 'primary'
            ]);
            // ---------------------------------------
        }
    }

    //########################################

    protected function _beforeToHtml()
    {
        $this->js->addRequireJs(['debug' => 'M2ePro/Order/Debug'], '');

        return parent::_beforeToHtml();
    }

    //########################################
}
