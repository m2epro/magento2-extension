<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Connector\Order\Receive;

/**
 * Class \Ess\M2ePro\Model\Ebay\Connector\Order\Receive\Items
 */
class Items extends \Ess\M2ePro\Model\Ebay\Connector\Command\RealTime
{
    const TIMEOUT_ERRORS_COUNT_TO_RISE = 3;
    const TIMEOUT_RISE_ON_ERROR        = 30;
    const TIMEOUT_RISE_MAX_VALUE       = 1500;

    //########################################

    protected function getCommand()
    {
        return ['orders', 'get', 'items'];
    }

    protected function getRequestData()
    {
        $data = [];

        if (!empty($this->params['from_update_date']) && !empty($this->params['to_update_date'])) {
            $data['from_update_date'] = $this->params['from_update_date'];
            $data['to_update_date'] = $this->params['to_update_date'];
        }

        if (!empty($this->params['from_create_date']) && !empty($this->params['to_create_date'])) {
            $data['from_create_date'] = $this->params['from_create_date'];
            $data['to_create_date'] = $this->params['to_create_date'];
        }

        if (!empty($this->params['job_token'])) {
            $data['job_token'] = $this->params['job_token'];
        }

        return $data;
    }

    //########################################

    public function process()
    {
        try {
            parent::process();
        } catch (\Ess\M2ePro\Model\Exception\Connection $exception) {
            $data = $exception->getAdditionalData();
            if (!empty($data['curl_error_number']) && $data['curl_error_number'] == CURLE_OPERATION_TIMEOUTED) {
                $fails = (int)$this->getHelper('Module')->getRegistry()->getValue(
                    '/ebay/synchronization/orders/receive/timeout_fails/'
                );
                $fails++;

                $rise = (int)$this->getHelper('Module')->getRegistry()->getValue(
                    '/ebay/synchronization/orders/receive/timeout_rise/'
                );
                $rise += self::TIMEOUT_RISE_ON_ERROR;

                if ($fails >= self::TIMEOUT_ERRORS_COUNT_TO_RISE && $rise <= self::TIMEOUT_RISE_MAX_VALUE) {
                    $fails = 0;
                    $this->getHelper('Module')->getRegistry()->setValue(
                        '/ebay/synchronization/orders/receive/timeout_rise/',
                        $rise
                    );
                }

                $this->getHelper('Module')->getRegistry()->setValue(
                    '/ebay/synchronization/orders/receive/timeout_fails/',
                    $fails
                );
            }

            throw $exception;
        }

        $this->getHelper('Module')->getRegistry()->setValue('/ebay/synchronization/orders/receive/timeout_fails/', 0);
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
        $rise = (int)$this->getHelper('Module')->getRegistry()->getValue(
            '/ebay/synchronization/orders/receive/timeout_rise/'
        );
        $rise > self::TIMEOUT_RISE_MAX_VALUE && $rise = self::TIMEOUT_RISE_MAX_VALUE;

        return 300 + $rise;
    }

    //########################################
}
