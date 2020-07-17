<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

/**
 * @method \Ess\M2ePro\Model\Order getParentObject()
 * @method \Ess\M2ePro\Model\ResourceModel\Amazon\Order getResource()
 */

namespace Ess\M2ePro\Model\Amazon;

/**
 * Class \Ess\M2ePro\Model\Amazon\ThrottlingManager
 */
class ThrottlingManager extends \Ess\M2ePro\Model\AbstractModel
{
    const REQUEST_TYPE_FEED   = 'feed';
    const REQUEST_TYPE_REPORT = 'report';

    const RESERVED_REQUESTS_REGISTRY_KEY = '/amazon/throttling/reserved_requests/';

    protected $availableRequestsCount = [];

    protected $amazonFactory;
    protected $activeRecordFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $data = []
    ) {
        $this->amazonFactory = $amazonFactory;
        $this->activeRecordFactory = $activeRecordFactory;
        parent::__construct($helperFactory, $modelFactory, $data);
    }

    //########################################

    public function getAvailableRequestsCount($merchantId, $requestType)
    {
        if (empty($this->availableRequestsCount)) {
            $this->availableRequestsCount = $this->receiveAvailableRequestsCount();
        }

        if (!isset($this->availableRequestsCount[$merchantId][$requestType])) {
            return 0;
        }

        $availableRequestsCount = $this->availableRequestsCount[$merchantId][$requestType];

        $requestsCount = $availableRequestsCount - $this->getReservedRequestsCount($merchantId, $requestType);

        return $requestsCount > 0 ? $requestsCount : 0;
    }

    public function registerRequests($merchantId, $requestType, $requestsCount)
    {
        if (!isset($this->availableRequestsCount[$merchantId][$requestType])) {
            return;
        }

        if ($this->availableRequestsCount[$merchantId][$requestType] <= 0) {
            return;
        }

        $this->availableRequestsCount[$merchantId][$requestType] -= $requestsCount;

        if ($this->availableRequestsCount[$merchantId][$requestType] <= 0) {
            $this->availableRequestsCount[$merchantId][$requestType] = 0;
        }
    }

    //########################################

    public function getReservedRequestsCount($merchantId, $requestType)
    {
        $reservedRequests = $this->getHelper('Module')->getRegistry()
            ->getValueFromJson(self::RESERVED_REQUESTS_REGISTRY_KEY);

        if (!isset($reservedRequests[$merchantId][$requestType])) {
            return 0;
        }

        return (int)$reservedRequests[$merchantId][$requestType];
    }

    public function reserveRequests($merchantId, $requestType, $requestsCount)
    {
        $reservedRequests = $this->getHelper('Module')->getRegistry()
            ->getValueFromJson(self::RESERVED_REQUESTS_REGISTRY_KEY);

        if (!isset($reservedRequests[$merchantId][$requestType])) {
            $reservedRequests[$merchantId][$requestType] = 0;
        }

        $reservedRequests[$merchantId][$requestType] += $requestsCount;

        $this->getHelper('Module')->getRegistry()->setValue(self::RESERVED_REQUESTS_REGISTRY_KEY, $reservedRequests);
    }

    public function releaseReservedRequests($merchantId, $requestType, $requestsCount)
    {
        $reservedRequests = $this->getHelper('Module')->getRegistry()
            ->getValueFromJson(self::RESERVED_REQUESTS_REGISTRY_KEY);

        if (!isset($reservedRequests[$merchantId][$requestType])) {
            return;
        }

        $reservedRequests[$merchantId][$requestType] -= $requestsCount;

        if ($reservedRequests[$merchantId][$requestType] <= 0) {
            unset($reservedRequests[$merchantId][$requestType]);
        }

        $this->getHelper('Module')->getRegistry()->setValue(self::RESERVED_REQUESTS_REGISTRY_KEY, $reservedRequests);
    }

    //########################################

    protected function receiveAvailableRequestsCount()
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Account\Collection $accountCollection */
        $accountCollection = $this->amazonFactory->getObject('Account')->getCollection();

        /** @var \Ess\M2ePro\Model\Account[] $accounts */
        $accounts = $accountCollection->getItems();
        if (empty($accounts)) {
            return [];
        }

        $serverHashes = [];

        /** @var \Ess\M2ePro\Model\Account $account */
        foreach ($accounts as $account) {
            $serverHashes[] = $account->getChildObject()->getServerHash();
        }

        /** @var \Ess\M2ePro\Model\Amazon\Connector\Dispatcher $dispatcher */
        $dispatcher = $this->modelFactory->getObject('Amazon_Connector_Dispatcher');
        $connector = $dispatcher->getVirtualConnector(
            'account',
            'get',
            'throttlingInfo',
            ['accounts' => $serverHashes],
            'data',
            null
        );

        $dispatcher->process($connector);

        $responseData = $connector->getResponseData();
        if (empty($responseData)) {
            return [];
        }

        $availableRequestsCount = [];

        foreach ($responseData as $serverHash => $accountData) {
            /** @var \Ess\M2ePro\Model\Amazon\Account $amazonAccount */
            $amazonAccount = $this->activeRecordFactory->getObjectLoaded(
                'Amazon_Account',
                $serverHash,
                'server_hash'
            );

            if (isset($availableRequestsCount[$amazonAccount->getMerchantId()])) {
                continue;
            }

            $availableRequestsCount[$amazonAccount->getMerchantId()] = $accountData;
        }

        return $availableRequestsCount;
    }

    //########################################
}
