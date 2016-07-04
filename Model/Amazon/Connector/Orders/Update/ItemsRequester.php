<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Connector\Orders\Update;

class ItemsRequester extends \Ess\M2ePro\Model\Amazon\Connector\Command\Pending\Requester
{
    protected $activeRecordFactory;

    // ########################################

    function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\Account $account,
        array $params
    )
    {
        $this->activeRecordFactory = $activeRecordFactory;
        parent::__construct(
            $helperFactory,
            $modelFactory,
            $account,
            $params
        );
    }

    // ########################################

    public function getCommand()
    {
        return array('orders','update','entities');
    }

    // ########################################

    public function process()
    {
        $this->eventBeforeExecuting();
        $this->getProcessingRunner()->start();
    }

    // ########################################

    protected function getProcessingRunnerModelName()
    {
        return 'Amazon\Connector\Orders\Update\ProcessingRunner';
    }

    protected function getProcessingParams()
    {
        return array_merge(
            parent::getProcessingParams(),
            array(
                'request_data' => $this->getRequestData()
            )
        );
    }

    // ########################################

    protected function getResponserParams()
    {
        $params = array();

        foreach ($this->params['items'] as $orderUpdate) {
            if (!is_array($orderUpdate)) {
                continue;
            }

            $params[$orderUpdate['change_id']] = $orderUpdate;
        }

        return $params;
    }

    // ########################################

    public function eventBeforeExecuting()
    {
        parent::eventBeforeExecuting();

        $changeIds = array();

        foreach ($this->params['items'] as $orderUpdate) {
            if (!is_array($orderUpdate)) {
                continue;
            }

            $changeIds[] = $orderUpdate['change_id'];
        }

        $this->activeRecordFactory->getObject('Order\Change')->getResource()->deleteByIds($changeIds);
    }

    // ########################################

    protected function getRequestData()
    {
        if (!isset($this->params['items']) || !is_array($this->params['items'])) {
            return array('items' => array());
        }

        $orders = array();

        foreach ($this->params['items'] as $orderUpdate) {
            if (!is_array($orderUpdate)) {
                continue;
            }

            $fulfillmentDate = new \DateTime($orderUpdate['fulfillment_date'], new \DateTimeZone('UTC'));

            $order = array(
                'id'               => $orderUpdate['change_id'],
                'order_id'         => $orderUpdate['amazon_order_id'],
                'tracking_number'  => $orderUpdate['tracking_number'],
                'carrier_name'     => $orderUpdate['carrier_name'],
                'fulfillment_date' => $fulfillmentDate->format('c'),
                'shipping_method'  => isset($orderUpdate['shipping_method']) ? $orderUpdate['shipping_method'] : null,
                'items'            => array()
            );

            if (isset($orderUpdate['items']) && is_array($orderUpdate['items'])) {
                foreach ($orderUpdate['items'] as $item) {
                    $order['items'][] = array(
                        'item_code' => $item['amazon_order_item_id'],
                        'qty'       => (int)$item['qty']
                    );
                }
            }

            $orders[$orderUpdate['change_id']] = $order;
        }

        return array('items' => $orders);
    }

    // ########################################
}