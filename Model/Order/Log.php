<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Order;

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

    // ########################################

    public function addMessage($orderId, $description, $type, array $additionalData = [])
    {
        $dataForAdd = $this->makeDataForAdd($orderId, $description, $type, $additionalData);
        $this->createMessage($dataForAdd);
    }

    // ########################################

    protected function createMessage($dataForAdd)
    {
        /** @var \Ess\M2ePro\Model\Order $order */
        $order = $this->parentFactory->getObjectLoaded(
            $this->getComponentMode(),
            'Order',
            $dataForAdd['order_id']
        );

        $dataForAdd['account_id']     = $order->getAccountId();
        $dataForAdd['marketplace_id'] = $order->getMarketplaceId();
        $dataForAdd['initiator'] = $this->initiator ? $this->initiator : \Ess\M2ePro\Helper\Data::INITIATOR_EXTENSION;
        $dataForAdd['component_mode'] = $this->getComponentMode();

        $this->isObjectNew(true);

        $this->setId(null)
            ->setData($dataForAdd)
            ->save();
    }

    protected function makeDataForAdd($orderId, $description, $type, array $additionalData = [])
    {
        $dataForAdd = [
            'order_id'        => $orderId,
            'description'     => $description,
            'type'            => (int)$type,
            'additional_data' => $this->getHelper('Data')->jsonEncode($additionalData)
        ];

        return $dataForAdd;
    }

    //########################################

    public function delete()
    {
        return parent::delete();
    }

    //########################################
}
