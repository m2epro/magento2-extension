<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Order;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractContainer;

class View extends AbstractContainer
{
    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;
    /** @var \Ess\M2ePro\Helper\Data\GlobalData */
    private $globalDataHelper;

    public function __construct(
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Widget $context,
        \Ess\M2ePro\Helper\Data $dataHelper,
        \Ess\M2ePro\Helper\Data\GlobalData $globalDataHelper,
        array $data = []
    ) {
        $this->dataHelper = $dataHelper;
        $this->globalDataHelper = $globalDataHelper;
        parent::__construct($context, $data);
    }

    public function _construct()
    {
        parent::_construct();

        $this->setId('ebayOrderView');
        $this->_controller = 'adminhtml_ebay_order';
        $this->_mode       = 'view';

        /** @var \Ess\M2ePro\Model\Order $order */
        $order = $this->globalDataHelper->getValue('order');

        $this->removeButton('back');
        $this->removeButton('reset');
        $this->removeButton('delete');
        $this->removeButton('add');
        $this->removeButton('save');
        $this->removeButton('edit');

        $url = $this->dataHelper->getBackUrl('*/ebay_order/index');
        $this->addButton('back', [
            'label'   => $this->__('Back'),
            'onclick' => 'CommonObj.backClick(\'' . $url . '\')',
            'class'   => 'back',
        ]);

        $isCanceled = $order->getChildObject()->isCanceled();

        if ($order->getChildObject()->canUpdateShippingStatus() && !$isCanceled) {
            $url = $this->getUrl('*/*/updateShippingStatus', ['id' => $order->getId()]);
            $this->addButton('ship', [
                'label'   => $this->__('Mark as Shipped'),
                'onclick' => "setLocation('" . $url . "');",
                'class'   => 'primary',
            ]);
        }

        if ($order->getChildObject()->canUpdatePaymentStatus() && !$isCanceled) {
            $url = $this->getUrl('*/*/updatePaymentStatus', ['id' => $order->getId()]);
            $this->addButton('pay', [
                'label'   => $this->__('Mark as Paid'),
                'onclick' => "setLocation('" . $url . "');",
                'class'   => 'primary',
            ]);
        }

        if ($order->getReserve()->isPlaced()) {
            $url = $this->getUrl('*/order/reservationCancel', ['ids' => $order->getId()]);
            $this->addButton('reservation_cancel', [
                'label'   => $this->__('Cancel QTY Reserve'),
                'onclick' => "confirmSetLocation(M2ePro.translator.translate('Are you sure?'), '" . $url . "');",
                'class'   => 'primary',
            ]);
        } elseif ($order->isReservable() && !$isCanceled) {
            $url = $this->getUrl('*/order/reservationPlace', ['ids' => $order->getId()]);
            $this->addButton('reservation_place', [
                'label'   => $this->__('Reserve QTY'),
                'onclick' => "confirmSetLocation(M2ePro.translator.translate('Are you sure?'), '" . $url . "');",
                'class'   => 'primary',
            ]);
        }

        if ($order->getMagentoOrderId() === null && !$isCanceled) {
            $url = $this->getUrl('*/*/createMagentoOrder', ['id' => $order->getId()]);
            $this->addButton('order', [
                'label'   => $this->__('Create Magento Order'),
                'onclick' => "setLocation('" . $url . "');",
                'class'   => 'primary',
            ]);
        } elseif ($order->getMagentoOrder() === null || $order->getMagentoOrder()->isCanceled()) {
            // ---------------------------------------
            $url     = $this->getUrl('*/*/createMagentoOrder', ['id' => $order->getId(), 'force' => 'yes']);
            $confirm = $this->dataHelper->escapeJs(
                $this->__('Are you sure that you want to create new Magento Order?')
            );

            $this->addButton('order', [
                'label'   => $this->__('Create Magento Order'),
                'onclick' => "confirmSetLocation('" . $confirm . "','" . $url . "');",
                'class'   => 'primary',
            ]);
        }
    }

    protected function _beforeToHtml()
    {
        $this->js->addRequireJs(['debug' => 'M2ePro/Order/Debug'], '');

        return parent::_beforeToHtml();
    }
}
