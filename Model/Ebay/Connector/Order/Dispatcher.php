<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Connector\Order;

/**
 * Class \Ess\M2ePro\Model\Ebay\Connector\Order\Dispatcher
 */
class Dispatcher extends \Ess\M2ePro\Model\AbstractModel
{
    const ACTION_PAY        = 1;
    const ACTION_SHIP       = 2;
    const ACTION_SHIP_TRACK = 3;

    protected $ebayFactory;

    // ########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    ) {
        $this->ebayFactory = $ebayFactory;
        parent::__construct($helperFactory, $modelFactory);
    }

    // ########################################

    public function process($action, $orders, array $params = [])
    {
        $orders = $this->prepareOrders($orders);

        switch ($action) {
            case self::ACTION_PAY:
                $result = $this->processOrders(
                    $orders,
                    $action,
                    'Ebay_Connector_Order_Update_Payment',
                    $params
                );
                break;

            case self::ACTION_SHIP:
            case self::ACTION_SHIP_TRACK:
                $result = $this->processOrders(
                    $orders,
                    $action,
                    'Ebay_Connector_Order_Update_Shipping',
                    $params
                );
                break;

            default;
                $result = false;
                break;
        }

        return $result;
    }

    // ########################################

    protected function processOrders(array $orders, $action, $connectorName, array $params = [])
    {
        if (count($orders) == 0) {
            return false;
        }

        /** @var $orders \Ess\M2ePro\Model\Order[] */

        foreach ($orders as $order) {
            try {
                /** @var \Ess\M2ePro\Model\Ebay\Connector\Dispatcher $dispatcher */
                $dispatcher = $this->modelFactory->getObject('Ebay_Connector_Dispatcher');

                /** @var \Ess\M2ePro\Model\Ebay\Connector\Order\Update\AbstractModel $connector */
                $connector = $dispatcher->getCustomConnector($connectorName, $params);
                $connector->setOrder($order);
                $connector->setAction($action);

                $dispatcher->process($connector);

                if ($connector->getStatus() == \Ess\M2ePro\Helper\Data::STATUS_ERROR) {
                    return false;
                }
            } catch (\Exception $e) {
                $order->addErrorLog(
                    'eBay Order Action was not completed. Reason: %msg%',
                    ['msg' => $e->getMessage()]
                );

                return false;
            }
        }

        return true;
    }

    // ########################################

    protected function prepareOrders($orders)
    {
        !is_array($orders) && $orders = [$orders];

        $preparedOrders = [];

        foreach ($orders as $order) {
            if ($order instanceof \Ess\M2ePro\Model\Order) {
                $preparedOrders[] = $order;
            } elseif (is_numeric($order)) {
                $preparedOrders[] = $this->ebayFactory->getObjectLoaded('Order', $order);
            }
        }

        return $preparedOrders;
    }

    // ########################################
}
