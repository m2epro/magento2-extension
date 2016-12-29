<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Servicing\Task;

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

        if (is_null($helper->getLastRun())) {
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

            if ((is_null($lastTypeChange) || $currentTimeStamp > strtotime($lastTypeChange) + 86400) &&
                (is_null($lastRun) || $currentTimeStamp > strtotime($lastRun) + 86400)) {

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
        $adminStore = $this->storeManager->getStore(\Magento\Store\Model\Store::DEFAULT_STORE_ID);

        return array(
            'base_url' => $adminStore->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_LINK, NULL),
            'calculation_url' => $adminStore->getUrl(
                'M2ePro/cron/test',
                array(
                    '_use_rewrite' => true,
                    '_nosid' => true,
                    '_secure' => false
                )
            )
        );
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