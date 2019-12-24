<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Servicing\Task;

/**
 * Class \Ess\M2ePro\Model\Servicing\Task\Cron
 */
class Cron extends \Ess\M2ePro\Model\Servicing\Task
{
    //########################################

    /**
     * @return string
     */
    public function getPublicNick()
    {
        return 'cron';
    }

    //########################################

    /**
     * @return bool
     */
    public function isAllowed()
    {
        $helper = $this->getHelper('Module\Cron');

        if ($this->getInitiator() === \Ess\M2ePro\Helper\Data::INITIATOR_DEVELOPER) {
            return true;
        }

        if ($helper->getLastRun() === null) {
            return true;
        }

        if ($helper->isRunnerService() && $helper->isLastRunMoreThan(900)) {
            return true;
        }

        if ($helper->isRunnerMagento()) {
            $currentTimeStamp = $this->getHelper('Data')->getCurrentGmtDate(true);
            $lastTypeChange = $helper->getLastRunnerChange();
            $lastRun = $this->cacheConfig
                           ->getGroupValue('/servicing/cron/', 'last_run');

            if (($lastTypeChange === null || $currentTimeStamp > strtotime($lastTypeChange) + 86400) &&
                ($lastRun === null || $currentTimeStamp > strtotime($lastRun) + 86400)) {
                $this->cacheConfig
                    ->setGroupValue('/servicing/cron/', 'last_run', $this->getHelper('Data')->getCurrentGmtDate());

                return true;
            }
        }

        return false;
    }

    // ---------------------------------------

    /**
     * @return array
     * @throws \Exception
     */
    public function getRequestData()
    {
        return [
            'base_url' => $this->storeManager->getStore(\Magento\Store\Model\Store::DEFAULT_STORE_ID)
                ->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_WEB, null)
        ];
    }

    public function processResponseData(array $data)
    {
        if (!isset($data['auth_key'])) {
            return;
        }

        $this->getHelper('Module')->getConfig()
                                  ->setGroupValue('/cron/service/', 'auth_key', $data['auth_key']);
    }

    //########################################
}
