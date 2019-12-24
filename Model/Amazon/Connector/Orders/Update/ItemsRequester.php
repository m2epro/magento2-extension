<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Connector\Orders\Update;

/**
 * Class \Ess\M2ePro\Model\Amazon\Connector\Orders\Update\ItemsRequester
 */
abstract class ItemsRequester extends \Ess\M2ePro\Model\Amazon\Connector\Command\Pending\Requester
{
    protected $activeRecordFactory;

    // ########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\Account $account = null,
        array $params = []
    ) {
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
        return ['orders','update','entities'];
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
        return 'Amazon_Connector_Orders_Update_ProcessingRunner';
    }

    protected function getProcessingParams()
    {
        return array_merge(
            parent::getProcessingParams(),
            [
                'request_data' => $this->getRequestData(),
                'order_id'     => $this->params['order']['order_id'],
                'change_id'    => $this->params['order']['change_id'],
                'start_date'   => $this->getHelper('Data')->getCurrentGmtDate(),
            ]
        );
    }

    // ########################################

    protected function getRequestData()
    {
        $order = $this->params['order'];
        $fulfillmentDate = new \DateTime($order['fulfillment_date'], new \DateTimeZone('UTC'));

        $request = [
            'id'               => $order['change_id'],
            'order_id'         => $order['amazon_order_id'],
            'tracking_number'  => $order['tracking_number'],
            'carrier_name'     => $order['carrier_name'],
            'carrier_code'     => $order['carrier_code'],
            'fulfillment_date' => $fulfillmentDate->format('c'),
            'shipping_method'  => isset($order['shipping_method']) ? $order['shipping_method'] : null,
            'items'            => []
        ];

        if (isset($order['items']) && is_array($order['items'])) {
            foreach ($order['items'] as $item) {
                $request['items'][] = [
                    'item_code' => $item['amazon_order_item_id'],
                    'qty'       => (int)$item['qty']
                ];
            }
        }

        return $request;
    }

    // ########################################
}
