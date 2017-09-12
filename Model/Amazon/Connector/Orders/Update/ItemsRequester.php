<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Connector\Orders\Update;

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
                'request_data' => $this->getRequestData(),
                'order_id'     => $this->params['order']['order_id'],
                'change_id'    => $this->params['order']['change_id'],
                'start_date'   => $this->getHelper('Data')->getCurrentGmtDate(),
            )
        );
    }

    // ########################################

    protected function getRequestData()
    {
        $fulfillmentDate = new \DateTime($this->params['order']['fulfillment_date'], new \DateTimeZone('UTC'));

        $order = array(
            'id'               => $this->params['order']['change_id'],
            'order_id'         => $this->params['order']['amazon_order_id'],
            'tracking_number'  => $this->params['order']['tracking_number'],
            'carrier_name'     => $this->params['order']['carrier_name'],
            'fulfillment_date' => $fulfillmentDate->format('c'),
            'shipping_method'  => isset($orderUpdate['shipping_method']) ? $orderUpdate['shipping_method'] : null,
            'items'            => array()
        );

        if (isset($this->params['order']['items']) && is_array($this->params['order']['items'])) {
            foreach ($this->params['order']['items'] as $item) {
                $order['items'][] = array(
                    'item_code' => $item['amazon_order_item_id'],
                    'qty'       => (int)$item['qty']
                );
            }
        }

        return $order;
    }

    // ########################################
}