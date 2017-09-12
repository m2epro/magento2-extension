<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Connector\Orders\Refund;

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
        return array(
            'order_id' => $this->params['order']['amazon_order_id'],
            'currency' => $this->params['order']['currency'],
            'type'     => 'Refund',
            'items'    => $this->params['order']['items'],
        );
    }

    // ########################################
}