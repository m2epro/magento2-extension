<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Runner;

use \Ess\M2ePro\Helper\Module\Cron\Service as CronService;

/**
 * Class \Ess\M2ePro\Model\Cron\Runner\Magento
 */
class Magento extends AbstractModel
{
    const MIN_DISTRIBUTION_EXECUTION_TIME = 300;
    const MAX_DISTRIBUTION_WAIT_INTERVAL  = 59;

    //########################################

    public function getNick()
    {
        return \Ess\M2ePro\Helper\Module\Cron::RUNNER_MAGENTO;
    }

    public function getInitiator()
    {
        return \Ess\M2ePro\Helper\Data::INITIATOR_UNKNOWN;
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Cron\Strategy\AbstractModel
     */
    protected function getStrategyObject()
    {
        return $this->modelFactory->getObject('Cron_Strategy_Serial');
    }

    //########################################

    protected function isPossibleToRun()
    {
        return $this->getHelper('Data\GlobalData')->getValue('cron_running') === null &&
               parent::isPossibleToRun();
    }

    // ---------------------------------------

    protected function beforeStart()
    {
        /*
         * Magento can execute M2ePro cron multiple times in same php process.
         * It can cause problems with items that were cached in first execution.
         */
        // ---------------------------------------
        $this->getHelper('Data\GlobalData')->setValue('cron_running', true);
        // ---------------------------------------

        parent::beforeStart();
        $this->distributeLoadIfNeed();
    }

    //########################################

    private function distributeLoadIfNeed()
    {
        if (!$this->getHelper('Module')->isProductionEnvironment()) {
            return;
        }

        $maxExecutionTime = (int)ini_get('max_execution_time');

        if ($maxExecutionTime <= 0 || $maxExecutionTime < self::MIN_DISTRIBUTION_EXECUTION_TIME) {
            return;
        }

        sleep(rand(0, self::MAX_DISTRIBUTION_WAIT_INTERVAL));
    }

    //########################################
}
