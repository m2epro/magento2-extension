<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Magento\Payment;

use Ess\M2ePro\Helper\Component\Amazon;
use Ess\M2ePro\Helper\Component\Ebay;

class Info extends \Magento\Payment\Block\Info
{
    protected $orderFactory;
    protected $helperFactory;

    private $order = NULL;

    //########################################

    public function __construct(
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\View\Element\Template\Context $context,
        array $data = []
    ) {
        $this->orderFactory = $orderFactory;
        $this->helperFactory = $helperFactory;

        parent::__construct($context, $data);
    }

    //########################################

    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('Ess_M2ePro::magento/order/payment/info.phtml');
    }

    /**
     * @return \Magento\Sales\Model\Order
     */
    public function getOrder()
    {
        if (!is_null($this->order)) {
            return $this->order;
        }

        $orderId = $this->getInfo()->getAdditionalInformation('order_id');
        if (empty($orderId)) {
            return null;
        }

        $this->order = $this->orderFactory->create();
        $this->order->load($orderId);

        return $this->order;
    }

    public function getPaymentMethod()
    {
        return (string)$this->getInfo()->getAdditionalInformation('payment_method');
    }

    public function getChannelOrderId()
    {
        return (string)$this->getInfo()->getAdditionalInformation('channel_order_id');
    }

    public function getTaxId()
    {
        return (string)$this->getInfo()->getAdditionalInformation('tax_id');
    }

    public function getChannelOrderUrl()
    {
        $url = '';

        if ($this->getInfo()->getAdditionalInformation('component_mode') != Amazon::NICK || !$this->getOrder()) {
            return $url;
        }

        return $this->getUrl('m2epro/amazon_order/goToAmazon', array(
            'magento_order_id' => $this->getOrder()->getId()
        ));
    }

    public function getChannelFinalFee()
    {
        return !$this->getIsSecureMode() ? (float)$this->getInfo()->getAdditionalInformation('channel_final_fee') : 0;
    }

    public function getCashOnDeliveryCost()
    {
        return !$this->getIsSecureMode()
            ? (float)$this->getInfo()->getAdditionalInformation('cash_on_delivery_cost') : 0;
    }

    public function getChannelTitle()
    {
        $component = $this->getInfo()->getAdditionalInformation('component_mode');
        return $this->helperFactory->getObject('Component\\' . ucfirst($component))->getChannelTitle();
    }

    public function getTransactions()
    {
        $transactions = !$this->getIsSecureMode()
            ? $this->getInfo()->getAdditionalInformation('transactions') : array();

        return is_array($transactions) ? $transactions : array();
    }

    //########################################

    public function getHelper($name)
    {
        return $this->helperFactory->getObject($name);
    }

    //########################################
}