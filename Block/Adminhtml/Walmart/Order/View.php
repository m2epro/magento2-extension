<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Order;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractContainer;

class View extends AbstractContainer
{
    /** @var \Ess\M2ePro\Model\Order $order */
    protected $order;

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

        // Initialization block
        // ---------------------------------------
        $this->setId('walmartOrderView');
        $this->_controller = 'adminhtml_walmart_order';
        $this->_mode = 'view';
        // ---------------------------------------

        $this->order = $this->globalDataHelper->getValue('order');

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
        $url = $this->dataHelper->getBackUrl('*/*/index');
        $this->addButton('back', [
            'label'     => $this->__('Back'),
            'onclick'   => 'CommonObj.backClick(\''.$url.'\')',
            'class'     => 'back'
        ]);
        // ---------------------------------------

        if ($this->order->getReserve()->isPlaced()) {
            // ---------------------------------------
            $url = $this->getUrl('*/order/reservationCancel', ['ids' => $this->order->getId()]);
            $this->addButton('reservation_cancel', [
                'label'     => $this->__('Cancel QTY Reserve'),
                'onclick'   => "confirmSetLocation(M2ePro.translator.translate('Are you sure?'), '".$url."');",
                'class'     => 'primary'
            ]);
            // ---------------------------------------
        } elseif ($this->order->isReservable()) {
            // ---------------------------------------
            $url = $this->getUrl('*/order/reservationPlace', ['ids' => $this->order->getId()]);
            $this->addButton('reservation_place', [
                'label'     => $this->__('Reserve QTY'),
                'onclick'   => "confirmSetLocation(M2ePro.translator.translate('Are you sure?'), '".$url."');",
                'class'     => 'primary'
            ]);
            // ---------------------------------------
        }

        if ($this->order->getMagentoOrderId() === null) {
            // ---------------------------------------
            $url = $this->getUrl('*/*/createMagentoOrder', ['id' => $this->order->getId()]);
            $this->addButton('order', [
                'label'     => $this->__('Create Magento Order'),
                'onclick'   => "setLocation('".$url."');",
                'class'     => 'primary'
            ]);
            // ---------------------------------------
        } elseif ($this->order->getMagentoOrder() === null || $this->order->getMagentoOrder()->isCanceled()) {
            // ---------------------------------------
            $url = $this->getUrl('*/*/createMagentoOrder', ['id' => $this->order->getId(), 'force' => 'yes']);
            $confirm = $this->dataHelper->escapeJs(
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

    protected function _beforeToHtml()
    {
        $this->js->addRequireJs(['debug' => 'M2ePro/Order/Debug'], '');

        return parent::_beforeToHtml();
    }
}
