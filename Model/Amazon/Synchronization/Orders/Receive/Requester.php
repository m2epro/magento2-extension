<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Synchronization\Orders\Receive;

class Requester
    extends \Ess\M2ePro\Model\Amazon\Connector\Orders\Get\ItemsRequester
{
    const TIMEOUT_ERRORS_COUNT_TO_RISE = 3;
    const TIMEOUT_RISE_ON_ERROR        = 30;
    const TIMEOUT_RISE_MAX_VALUE       = 1500;

    protected $cacheConfig;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\Config\Manager\Cache $cacheConfig,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\Account $account = null,
        array $params = []
    )
    {
        $this->cacheConfig = $cacheConfig;
        parent::__construct($helperFactory, $modelFactory, $account, $params);
    }

    //########################################

    public function process()
    {
        $cacheConfigGroup = '/amazon/synchronization/orders/receive/timeout';

        try {

            parent::process();

        } catch (\Ess\M2ePro\Model\Exception\Connection $exception) {

            $data = $exception->getAdditionalData();
            if (!empty($data['curl_error_number']) && $data['curl_error_number'] == CURLE_OPERATION_TIMEOUTED) {

                $fails = (int)$this->cacheConfig->getGroupValue($cacheConfigGroup, 'fails');
                $fails++;

                $rise = (int)$this->cacheConfig->getGroupValue($cacheConfigGroup, 'rise');
                $rise += self::TIMEOUT_RISE_ON_ERROR;

                if ($fails >= self::TIMEOUT_ERRORS_COUNT_TO_RISE && $rise <= self::TIMEOUT_RISE_MAX_VALUE) {

                    $fails = 0;
                    $this->cacheConfig->setGroupValue($cacheConfigGroup, 'rise', $rise);
                }
                $this->cacheConfig->setGroupValue($cacheConfigGroup, 'fails', $fails);
            }

            throw $exception;
        }

        $this->cacheConfig->setGroupValue($cacheConfigGroup, 'fails', 0);
    }

    protected function buildConnectionInstance()
    {
        $connection = parent::buildConnectionInstance();
        $connection->setTimeout($this->getRequestTimeOut());

        return $connection;
    }

    //########################################

    protected function getRequestTimeOut()
    {
        $cacheConfigGroup = '/amazon/synchronization/orders/receive/timeout';

        $rise = (int)$this->cacheConfig->getGroupValue($cacheConfigGroup, 'rise');
        $rise > self::TIMEOUT_RISE_MAX_VALUE && $rise = self::TIMEOUT_RISE_MAX_VALUE;

        return 300 + $rise;
    }

    //########################################
}