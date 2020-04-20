<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Magento\Payment;

use Ess\M2ePro\Helper\Component\Amazon;
use Ess\M2ePro\Helper\Component\Ebay;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Magento\Payment\Info
 */
class Info extends \Magento\Payment\Block\Info
{
    protected $orderFactory;
    protected $helperFactory;

    private $order = null;

    protected $_template = 'Ess_M2ePro::magento/order/payment/info.phtml';

    //########################################

    public function __construct(
        \Magento\Store\Model\App\Emulation $appEmulation,
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

    /**
     * Magento has forcibly set FRONTEND area
     * vendor/magento/module-payment/Helper/Data.php::getInfoBlockHtm()
     *
     * @return string
     */
    protected function _toHtml()
    {
        $this->setData('area', \Magento\Framework\App\Area::AREA_ADMINHTML);
        return parent::_toHtml();
    }

    //########################################

    /**
     * @return \Magento\Sales\Model\Order
     */
    public function getOrder()
    {
        if ($this->order !== null) {
            return $this->order;
        }

        $orderId = $this->getInfo()->getData('parent_id');
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

        return $this->getUrl('m2epro/amazon_order/goToAmazon', [
            'magento_order_id' => $this->getOrder()->getId()
        ]);
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
            ? $this->getInfo()->getAdditionalInformation('transactions') : [];

        return is_array($transactions) ? $transactions : [];
    }

    //########################################

    public function getHelper($name)
    {
        return $this->helperFactory->getObject($name);
    }

    //########################################
}
