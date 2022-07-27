<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Order;

use \Ess\M2ePro\Model\Connector\Connection\Response\Message;

/**
 * Class \Ess\M2ePro\Model\Order\Log
 */
class Log extends \Ess\M2ePro\Model\Log\AbstractModel
{
    private $orderLogCollection;
    private $helperData;

    /** @var int|null */
    protected $initiator = null;

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Ess\M2ePro\Model\ResourceModel\Order\Log\CollectionFactory $orderLogCollection,
        \Ess\M2ePro\Helper\Data $helperData,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $parentFactory,
            $resourceConnection,
            $modelFactory,
            $activeRecordFactory,
            $helperFactory,
            $context,
            $registry,
            $resource,
            $resourceCollection,
            $data
        );

        $this->orderLogCollection = $orderLogCollection;
        $this->helperData = $helperData;
    }

    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init(\Ess\M2ePro\Model\ResourceModel\Order\Log::class);
    }

    //########################################

    /**
     * @param int $initiator
     * @return $this
     */
    public function setInitiator($initiator = \Ess\M2ePro\Helper\Data::INITIATOR_UNKNOWN)
    {
        $this->initiator = (int)$initiator;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getInitiator()
    {
        return $this->initiator;
    }

    //########################################

    /**
     * @param \Ess\M2ePro\Model\Order|int $order
     * @param string $description
     * @param int $type
     * @param array $additionalData
     * @param bool $isUnique
     *
     * @return bool
     */
    public function addMessage($order, $description, $type, array $additionalData = [], $isUnique = false)
    {
        if (!($order instanceof \Ess\M2ePro\Model\Order)) {
            /** @var \Ess\M2ePro\Model\Order $order */
            $order = $this->parentFactory->getObjectLoaded($this->getComponentMode(), 'Order', $order);
        }

        if ($isUnique && $this->isExist($order->getId(), $description)) {
            return false;
        }

        $dataForAdd = [
            'account_id'      => $order->getAccountId(),
            'marketplace_id'  => $order->getMarketplaceId(),
            'order_id'        => $order->getId(),
            'description'     => $description,
            'type'            => (int)$type,
            'additional_data' => $this->getHelper('Data')->jsonEncode($additionalData),

            'initiator'      => $this->initiator ? $this->initiator : \Ess\M2ePro\Helper\Data::INITIATOR_EXTENSION,
            'component_mode' => $this->getComponentMode()
        ];

        $this->activeRecordFactory->getObject('Order_Log')
            ->setData($dataForAdd)
            ->save();

        return true;
    }

    /**
     * @param \Ess\M2ePro\Model\Order|int $order
     * @param Message $message
     */
    public function addServerResponseMessage($order, Message $message)
    {
        if (!($order instanceof \Ess\M2ePro\Model\Order)) {
            /** @var \Ess\M2ePro\Model\Order $order */
            $order = $this->parentFactory->getObjectLoaded($this->getComponentMode(), 'Order', $order);
        }

        $map = [
            Message::TYPE_NOTICE  => self::TYPE_INFO,
            Message::TYPE_SUCCESS => self::TYPE_SUCCESS,
            Message::TYPE_WARNING => self::TYPE_WARNING,
            Message::TYPE_ERROR   => self::TYPE_ERROR
        ];

        $this->addMessage(
            $order,
            $message->getText(),
            isset($map[$message->getType()]) ? $map[$message->getType()] : self::TYPE_ERROR
        );
    }

    //########################################

    public function isExist(int $orderId, string $message): bool
    {
        $collection = $this->orderLogCollection->create();
        $collection->addFieldToFilter('order_id', $orderId);
        $collection->addFieldToFilter('description', $message);

        if ($collection->getSize()) {
            return true;
        }

        return false;
    }

    //########################################
}
