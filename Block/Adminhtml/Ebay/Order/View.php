<?php

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Order;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractContainer;

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
        $this->addButton('back', array(
            'label'     => $this->__('Back'),
            'onclick'   => 'CommonObj.backClick(\''.$url.'\')',
            'class'     => 'back'
        ));
        // ---------------------------------------

        if ($order->getChildObject()->canUpdateShippingStatus()) {
            // ---------------------------------------
            $url = $this->getUrl('*/*/updateShippingStatus', array('id' => $order->getId()));
            $this->addButton('ship', array(
                'label'     => $this->__('Mark as Shipped'),
                'onclick'   => "setLocation('".$url."');",
                'class'     => 'primary'
            ));
            // ---------------------------------------
        }

        if ($order->getChildObject()->canUpdatePaymentStatus()) {
            // ---------------------------------------
            $url = $this->getUrl('*/*/updatePaymentStatus', array('id' => $order->getId()));
            $this->addButton('pay', array(
                'label'     => $this->__('Mark as Paid'),
                'onclick'   => "setLocation('".$url."');",
                'class'     => 'primary'
            ));
            // ---------------------------------------
        }

        if ($order->getReserve()->isPlaced()) {
            // ---------------------------------------
            $url = $this->getUrl('*/order/reservationCancel', array('ids' => $order->getId()));
            $this->addButton('reservation_cancel', array(
                'label'     => $this->__('Cancel QTY Reserve'),
                'onclick'   => "confirmSetLocation(M2ePro.translator.translate('Are you sure?'), '".$url."');",
                'class'     => 'primary'
            ));
            // ---------------------------------------
        } elseif ($order->isReservable()) {
            // ---------------------------------------
            $url = $this->getUrl('*/order/reservationPlace', array('ids' => $order->getId()));
            $this->addButton('reservation_place', array(
                'label'     => $this->__('Reserve QTY'),
                'onclick'   => "confirmSetLocation(M2ePro.translator.translate('Are you sure?'), '".$url."');",
                'class'     => 'primary'
            ));
            // ---------------------------------------
        }

        if (is_null($order->getMagentoOrderId())) {
            // ---------------------------------------
            $url = $this->getUrl('*/*/createMagentoOrder', array('id' => $order->getId()));
            $this->addButton('order', array(
                'label'     => $this->__('Create Magento Order'),
                'onclick'   => "setLocation('".$url."');",
                'class'     => 'primary'
            ));
            // ---------------------------------------
        } else if (is_null($order->getMagentoOrder()) || $order->getMagentoOrder()->isCanceled()) {
            // ---------------------------------------
            $url = $this->getUrl('*/*/createMagentoOrder', array('id' => $order->getId(), 'force' => 'yes'));
            $confirm = $this->getHelper('Data')->escapeJs(
                $this->__('Are you sure that you want to create new Magento Order?')
            );

            $this->addButton('order', array(
                'label'     => $this->__('Create Magento Order'),
                'onclick'   => "confirmSetLocation('".$confirm."','".$url."');",
                'class'     => 'primary'
            ));
            // ---------------------------------------
        }
    }

    //########################################
}