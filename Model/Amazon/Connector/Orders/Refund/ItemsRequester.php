<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Connector\Orders\Refund;

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
        return array('orders','refund','entities');
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
        return 'Amazon\Connector\Orders\Refund\ProcessingRunner';
    }

    protected function getProcessingParams()
    {
        $ordersIds = array();
        foreach ($this->params['items'] as $itemData) {
            $ordersIds[] = $itemData['order_id'];
        }

        return array_merge(
            parent::getProcessingParams(),
            array(
                'request_data' => $this->getRequestData(),
                'orders_ids'   => array_unique($ordersIds),
            )
        );
    }

    // ########################################

    protected function getResponserParams()
    {
        $params = array();

        foreach ($this->params['items'] as $item) {
            if (!is_array($item)) {
                continue;
            }

            $params[$item['change_id']] = $item;
        }

        return $params;
    }

    // ########################################

    public function eventBeforeExecuting()
    {
        parent::eventBeforeExecuting();

        $changeIds = array();

        foreach ($this->params['items'] as $orderRefund) {
            if (!is_array($orderRefund)) {
                continue;
            }

            $changeIds[] = $orderRefund['change_id'];
        }

        $this->activeRecordFactory->getObject('Order\Change')->getResource()->deleteByIds($changeIds);
    }

    // ########################################

    protected function getRequestData()
    {
        if (!isset($this->params['items']) || !is_array($this->params['items'])) {
            return array('orders' => array());
        }

        $orders = array();

        foreach ($this->params['items'] as $orderRefund) {
            if (!is_array($orderRefund)) {
                continue;
            }

            $orders[$orderRefund['change_id']] = array(
                'order_id' => $orderRefund['amazon_order_id'],
                'currency' => $orderRefund['currency'],
                'type'     => 'Refund',
                'items'    => $orderRefund['items'],
            );
        }

        return array('orders' => $orders);
    }

    // ########################################
}