<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Order;

class Log extends \Ess\M2ePro\Model\Log\AbstractModel
{
    protected $initiator = NULL;

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

    // ########################################

    public function addMessage($orderId, $description, $type, array $additionalData = array())
    {
        $dataForAdd = $this->makeDataForAdd($orderId, $description, $type, $additionalData);
        $this->createMessage($dataForAdd);
    }

    // ########################################

    protected function createMessage($dataForAdd)
    {
        /** @var \Ess\M2ePro\Model\Order $order */
        $order = $this->parentFactory->getObjectLoaded(
            $this->getComponentMode(), 'Order', $dataForAdd['order_id']
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

    protected function makeDataForAdd($orderId, $description, $type, array $additionalData = array())
    {
        $dataForAdd = array(
            'order_id'        => $orderId,
            'description'     => $description,
            'type'            => (int)$type,
            'additional_data' => $this->getHelper('Data')->jsonEncode($additionalData)
        );

        return $dataForAdd;
    }

    //########################################

    public function delete()
    {
        return parent::delete();
    }

    //########################################
}