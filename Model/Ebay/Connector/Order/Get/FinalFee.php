<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Connector\Order\Get;

class FinalFee extends \Ess\M2ePro\Model\Connector\Command\RealTime
{
    public function __construct(
        \Ess\M2ePro\Model\Ebay\Connector\Protocol $protocol,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $params = []
    ) {
        parent::__construct($helperFactory, $modelFactory, $params);

        $this->setProtocol($protocol);
    }

    protected function getRequestData()
    {
        return [
            'account'  => $this->params['account_server_hash'],
            'order_id' => $this->params['order_id'],
        ];
    }

    protected function getCommand()
    {
        return ['orders', 'get', 'finalFee'];
    }

    public function prepareResponseData()
    {
        $this->responseData = new \Ess\M2ePro\Model\Ebay\Connector\Order\Get\FinalFee\Response(
            $this->getResponse()->getResponseData()['final_fee'] ?? null
        );
    }
}
