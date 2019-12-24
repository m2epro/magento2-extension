<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

/**
 * @method \Ess\M2ePro\Model\Order getParentObject()
 * @method \Ess\M2ePro\Model\ResourceModel\Walmart\Order getResource()
 */

namespace Ess\M2ePro\Model\Walmart;

/**
 * Class \Ess\M2ePro\Model\Walmart\ThrottlingManager
 */
class ThrottlingManager extends \Ess\M2ePro\Model\AbstractModel
{
    const REQUEST_TYPE_UPDATE_DETAILS = 'update_details';
    const REQUEST_TYPE_UPDATE_PRICE = 'update_price';
    const REQUEST_TYPE_UPDATE_PROMOTIONS = 'update_promotions';
    const REQUEST_TYPE_UPDATE_QTY = 'update_qty';

    const REGISTRY_KEY = '/walmart/listing/product/request/throttling/last_request_info/';

    protected $activeRecordFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $data = []
    ) {
        $this->activeRecordFactory = $activeRecordFactory;
        parent::__construct($helperFactory, $modelFactory, $data);
    }

    //########################################

    public function getAvailableRequestsCount($accountId, $requestType)
    {
        /** @var \Ess\M2ePro\Model\Registry $registry */
        $registry = $this->activeRecordFactory->getObjectLoaded(
            'Registry',
            self::REGISTRY_KEY . $accountId . '/',
            'key',
            false
        );

        if ($registry === null) {
            $registry = $this->activeRecordFactory->getObject('Registry');
        }

        $lastRequestInfo = $registry->getValueFromJson();

        $throttlingInfo = $this->getThrottlingInfo($requestType);

        if (empty($lastRequestInfo[$requestType])) {
            return $throttlingInfo['quota'];
        }

        $lastRequestInfo = $lastRequestInfo[$requestType];

        $currentDateTime = new \DateTime($this->getHelper('Data')->getCurrentGmtDate(), new \DateTimeZone('UTC'));
        $lastRequestDateTime = new \DateTime($lastRequestInfo['date'], new \DateTimeZone('UTC'));

        $datesDiff = $currentDateTime->diff($lastRequestDateTime);

        if ($datesDiff->y > 0 || $datesDiff->m > 0 || $datesDiff->d > 0) {
            return $throttlingInfo['quota'];
        }

        $minutesFromLastRequest = $datesDiff->i + ($datesDiff->h * 60);

        $availableRequestsCount = (int)($minutesFromLastRequest * $throttlingInfo['restore_rate']) +
            $lastRequestInfo['available_requests_count'];

        if ($availableRequestsCount > $throttlingInfo['quota']) {
            return $throttlingInfo['quota'];
        }

        return $availableRequestsCount;
    }

    public function registerRequests($accountId, $requestType, $requestsCount)
    {
        $availableRequestsCount = $this->getAvailableRequestsCount($accountId, $requestType);
        if ($availableRequestsCount <= 0) {
            return;
        }

        $availableRequestsCount -= $requestsCount;
        if ($availableRequestsCount < 0) {
            $availableRequestsCount = 0;
        }

        $lastRequestInfo = [
            'date'                     => $this->getHelper('Data')->getCurrentGmtDate(),
            'available_requests_count' => $availableRequestsCount,
        ];

        /** @var \Ess\M2ePro\Model\Registry $registry */
        $registry = $this->activeRecordFactory->getObjectLoaded(
            'Registry',
            self::REGISTRY_KEY . $accountId . '/',
            'key',
            false
        );

        if ($registry === null) {
            $registry = $this->activeRecordFactory->getObject('Registry');
        }

        $existedLastRequestInfo = $registry->getValueFromJson();
        $existedLastRequestInfo[$requestType] = $lastRequestInfo;

        $registry->addData([
            'key'   => self::REGISTRY_KEY . $accountId . '/',
            'value' => $this->getHelper('Data')->jsonEncode($existedLastRequestInfo),
        ]);
        $registry->save();
    }

    //########################################

    private function getThrottlingInfo($requestType)
    {
        $throttlingInfo = [
            self::REQUEST_TYPE_UPDATE_DETAILS    => [
                'quota'        => 10,
                'restore_rate' => 0.16, // 10 per hour
            ],
            self::REQUEST_TYPE_UPDATE_PRICE      => [
                'quota'        => 10,
                'restore_rate' => 0.16, // 10 per hour
            ],
            self::REQUEST_TYPE_UPDATE_PROMOTIONS => [
                'quota'        => 6,
                'restore_rate' => 0.0042, // 6 per day
            ],
            self::REQUEST_TYPE_UPDATE_QTY        => [
                'quota'        => 10,
                'restore_rate' => 0.16, // 10 per hour
            ],
        ];

        return $throttlingInfo[$requestType];
    }

    //########################################
}
