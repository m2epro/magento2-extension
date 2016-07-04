<?php

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Order;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractContainer;

class View extends AbstractContainer
{
    /** @var $order \Ess\M2ePro\Model\Order */
    protected $order = null;

    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('amazonOrderView');
        $this->_controller = 'adminhtml_amazon_order';
        $this->_mode = 'view';
        // ---------------------------------------

        $this->order = $this->getHelper('Data\GlobalData')->getValue('order');

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
        $url = $this->getHelper('Data')->getBackUrl('*/*/index');
        $this->addButton('back', array(
            'label'     => $this->__('Back'),
            'onclick'   => 'CommonObj.backClick(\''.$url.'\')',
            'class'     => 'back'
        ));
        // ---------------------------------------

        if ($this->order->getChildObject()->canUpdateShippingStatus() && !$this->order->getChildObject()->isPrime()) {
            // ---------------------------------------
            $url = $this->getUrl('*/*/updateShippingStatus', array('id' => $this->order->getId()));
            $this->addButton('update_shipping_status', array(
                'label'     => $this->__('Mark as Shipped'),
                'onclick'   => "setLocation('".$url."');",
                'class'     => 'primary'
            ));
            // ---------------------------------------
        }

        if ($this->order->getReserve()->isPlaced()) {
            // ---------------------------------------
            $url = $this->getUrl('*/order/reservationCancel', array('ids' => $this->order->getId()));
            $this->addButton('reservation_cancel', array(
                'label'     => $this->__('Cancel QTY Reserve'),
                'onclick'   => "confirmSetLocation(M2ePro.translator.translate('Are you sure?'), '".$url."');",
                'class'     => 'primary'
            ));
            // ---------------------------------------
        } elseif ($this->order->isReservable()) {
            // ---------------------------------------
            $url = $this->getUrl('*/order/reservationPlace', array('ids' => $this->order->getId()));
            $this->addButton('reservation_place', array(
                'label'     => $this->__('Reserve QTY'),
                'onclick'   => "confirmSetLocation(M2ePro.translator.translate('Are you sure?'), '".$url."');",
                'class'     => 'primary'
            ));
            // ---------------------------------------
        }

        if (is_null($this->order->getMagentoOrderId())) {
            // ---------------------------------------
            $url = $this->getUrl('*/*/createMagentoOrder', array('id' => $this->order->getId()));
            $this->addButton('order', array(
                'label'     => $this->__('Create Magento Order'),
                'onclick'   => "setLocation('".$url."');",
                'class'     => 'primary'
            ));
            // ---------------------------------------
        } elseif (is_null($this->order->getMagentoOrder()) || $this->order->getMagentoOrder()->isCanceled()) {
            // ---------------------------------------
            $url = $this->getUrl('*/*/createMagentoOrder', array('id' => $this->order->getId(), 'force' => 'yes'));
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