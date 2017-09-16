<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Runner;

use \Ess\M2ePro\Helper\Module\Cron\Service as CronService;

class Magento extends AbstractModel
{
    const MIN_DISTRIBUTION_EXECUTION_TIME = 300;
    const MAX_DISTRIBUTION_WAIT_INTERVAL  = 59;

    //########################################

    protected function getNick()
    {
        return \Ess\M2ePro\Helper\Module\Cron::RUNNER_MAGENTO;
    }

    protected function getInitiator()
    {
        return \Ess\M2ePro\Helper\Data::INITIATOR_UNKNOWN;
    }

    //########################################

    public function process()
    {
        if ($this->getHelper('Module')->getConfig()->getGroupValue('/cron/magento/','disabled')) {
            return false;
        }

        return parent::process();
    }

    /**
     * @return \Ess\M2ePro\Model\Cron\Strategy\AbstractModel
     */
    protected function getStrategyObject()
    {
        return $this->modelFactory->getObject('Cron\Strategy\Serial');
    }

    //########################################

    protected function initialize()
    {
        parent::initialize();

        $helper = $this->getHelper('Module\Cron');

        if ($helper->isRunnerMagento()) {
            return;
        }

        if ($helper->isLastRunMoreThan(CronService::MAX_INACTIVE_TIME)) {

            $helper->setRunner(\Ess\M2ePro\Helper\Module\Cron::RUNNER_MAGENTO);
            $helper->setLastRunnerChange($this->getHelper('Data')->getCurrentGmtDate());
        }
    }

    protected function isPossibleToRun()
    {
        return is_null($this->getHelper('Data\GlobalData')->getValue('cron_running')) &&
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
        $this->getHelper('Data\GlobalData')->setValue('cron_running',true);
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

        $maxExecutionTime = (int)@ini_get('max_execution_time');

        if ($maxExecutionTime <= 0 || $maxExecutionTime < self::MIN_DISTRIBUTION_EXECUTION_TIME) {
            return;
        }

        sleep(rand(0,self::MAX_DISTRIBUTION_WAIT_INTERVAL));
    }

    //########################################
}