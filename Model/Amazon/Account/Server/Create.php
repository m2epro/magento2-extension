<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Account\Server;

class Create
{
    /** @var \Ess\M2ePro\Model\Amazon\Connector\Dispatcher */
    private $dispatcher;
    /** @var \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory */
    private $amazonFactory;

    /**
     * @param \Ess\M2ePro\Model\Amazon\Connector\Dispatcher $dispatcher
     * @param \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory
     */
    public function __construct(
        \Ess\M2ePro\Model\Amazon\Connector\Dispatcher $dispatcher,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory
    ) {
        $this->dispatcher = $dispatcher;
        $this->amazonFactory = $amazonFactory;
    }

    /**
     * @param string $token
     * @param string $merchantId
     * @param int $marketplaceId
     *
     * @return \Ess\M2ePro\Model\Amazon\Account\Server\Create\Result
     */
    public function process(string $token, string $merchantId, int $marketplaceId): Create\Result
    {
        /** @var \Ess\M2ePro\Model\Marketplace $marketplace */
        $marketplace = $this->amazonFactory->getCachedObjectLoaded(
            'Marketplace',
            $marketplaceId
        );

        /** @var \Ess\M2ePro\Model\Amazon\Connector\Account\Add\EntityRequester $connectorObj */
        $connectorObj = $this->dispatcher->getConnector(
            'account',
            'add',
            'entityRequester',
            [
                'marketplace_id' => $marketplace->getNativeId(),
                'merchant_id'    => $merchantId,
                'token'          => $token,
            ]
        );

        $this->dispatcher->process($connectorObj);

        $responseData = $connectorObj->getResponseData();

        return new Create\Result(
            $responseData['hash'],
            $responseData['info']
        );
    }
}
