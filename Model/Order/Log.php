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
    /** @var int|null */
    protected $initiator = null;

    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('Ess\M2ePro\Model\ResourceModel\Order\Log');
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
     */
    public function addMessage($order, $description, $type, array $additionalData = [])
    {
        if (!($order instanceof \Ess\M2ePro\Model\Order)) {
            /** @var \Ess\M2ePro\Model\Order $order */
            $order = $this->parentFactory->getObjectLoaded($this->getComponentMode(), 'Order', $order);
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
            Message::TYPE_NOTICE  => self::TYPE_NOTICE,
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
}
