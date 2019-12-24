<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Order;

/**
 * Class \Ess\M2ePro\Model\Ebay\Order\ExternalTransaction
 */
class ExternalTransaction extends \Ess\M2ePro\Model\ActiveRecord\AbstractModel
{
    const NOT_PAYPAL_TRANSACTION = 'SIS';

    private $ebayFactory;

    /** @var $order \Ess\M2ePro\Model\Order */
    private $order = null;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->ebayFactory = $ebayFactory;
        parent::__construct(
            $modelFactory,
            $activeRecordFactory,
            $helperFactory,
            $context,
            $registry,
            $resource,
            $resourceCollection,
            $data
        );
    }

    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('Ess\M2ePro\Model\ResourceModel\Ebay\Order\ExternalTransaction');
    }

    //########################################

    /**
     * @param \Ess\M2ePro\Model\Order $order
     * @return $this
     */
    public function setOrder(\Ess\M2ePro\Model\Order $order)
    {
        $this->order = $order;
        return $this;
    }

    /**
     * @return \Ess\M2ePro\Model\Order
     */
    public function getOrder()
    {
        if ($this->order === null) {
            $this->order = $this->ebayFactory->getObjectLoaded('Order', $this->getData('order_id'));
        }

        return $this->order;
    }

    //########################################

    public function getTransactionId()
    {
        return $this->getData('transaction_id');
    }

    /**
     * @return float
     */
    public function getSum()
    {
        return (float)$this->getData('sum');
    }

    /**
     * @return float
     */
    public function getFee()
    {
        return (float)$this->getData('fee');
    }

    public function getTransactionDate()
    {
        return $this->getData('transaction_date');
    }

    //########################################

    /**
     * @return bool
     */
    public function isPaypal()
    {
        return $this->getTransactionId() != self::NOT_PAYPAL_TRANSACTION;
    }

    /**
     * @return string
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getPaypalUrl()
    {
        if (!$this->isPaypal()) {
            return '';
        }

        $params = [
            'cmd' => '_view-a-trans',
            'id'  => $this->getData('transaction_id')
        ];

        $modePrefix = $this->getOrder()->getAccount()->getChildObject()->isModeSandbox() ? 'sandbox.' : '';
        $baseUrl = $this->getHelper('Module')->getConfig()->getGroupValue('/other/paypal/', 'url');

        return 'https://www.' . $modePrefix . $baseUrl . '?' . http_build_query($params, '', '&');
    }

    //########################################
}
