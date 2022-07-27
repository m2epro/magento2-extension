<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task\System\Servicing\Statistic;

class InstructionType extends \Ess\M2ePro\Model\Cron\Task\AbstractModel
{
    const NICK = 'system/servicing/statistic/instruction_type';

    const REGISTRY_KEY_DATA = '/system/servicing/statistic/instruction_type/type_timing/';
    const REGISTRY_KEY_LAST_LAUNCH = '/system/servicing/statistic/instruction_type/last_launch/';

    const DEFAULT_TIME_LIMIT = 300;

    /** @var \Ess\M2ePro\Model\Registry\Manager $registryManager */
    private $registryManager;

    /** @var \Ess\M2ePro\Model\Servicing\Task\Statistic\Manager $statisticManager */
    private $statisticManager;

    /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Instruction $listingProductInstruction */
    private $listingProductInstruction;

    /** @var \DateTime */
    private $currentDateTime;

    public function __construct(
        \Magento\Framework\Event\Manager $eventManager,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Cron\Task\Repository $taskRepo,
        \Magento\Framework\App\ResourceConnection $resource,
        \Ess\M2ePro\Model\Registry\Manager $registryManager,
        \Ess\M2ePro\Model\Servicing\Task\Statistic\Manager $statisticManager,
        \Ess\M2ePro\Helper\Data $helperData,
        \Ess\M2ePro\Model\ResourceModel\Listing\Product\Instruction $listingProductInstruction
    ) {
        parent::__construct(
            $helperData,
            $eventManager,
            $parentFactory,
            $modelFactory,
            $activeRecordFactory,
            $helperFactory,
            $taskRepo,
            $resource
        );
        $this->registryManager = $registryManager;
        $this->statisticManager = $statisticManager;
        $this->listingProductInstruction = $listingProductInstruction;
        $this->currentDateTime = \Ess\M2ePro\Helper\Date::createCurrentGmt();
    }

    protected function performActions()
    {
        if (!$this->statisticManager->isTaskEnabled(
            \Ess\M2ePro\Model\Servicing\Task\Statistic\Manager::TASK_LISTING_PRODUCT_INSTRUCTION_TYPE
        )) {
            return;
        }

        $lastLaunch = $this->registryManager->getValue(self::REGISTRY_KEY_LAST_LAUNCH);
        $lastLaunchDateTime = $lastLaunch === null ? null : $this->helperData->createGmtDateTime($lastLaunch);

        $currentTime = $this->currentDateTime->format('Y-m-d H:i:s');

        // Set a new launch time value
        $this->registryManager->setValue(self::REGISTRY_KEY_LAST_LAUNCH, $currentTime);

        // If the previous launch was more than 5 minutes ago, we get instructions only for last 5 minutes
        // Set the start time of the selection to 5 minutes ago
        if (
            $lastLaunchDateTime === null
            || $this->currentDateTime->getTimestamp() - $lastLaunchDateTime->getTimestamp() > self::DEFAULT_TIME_LIMIT
        ) {
            $lastLaunchDateTime = clone $this->currentDateTime;
            $lastLaunchDateTime->modify('-' . self::DEFAULT_TIME_LIMIT . ' seconds');
        }

        $lastLaunchTime = $lastLaunchDateTime->format('Y-m-d H:i:s');
        $lastLaunchHour = $lastLaunchDateTime->format('H');
        $currentHour = $this->currentDateTime->format('H');

        $typeTiming = $this->registryManager->getValueFromJson(self::REGISTRY_KEY_DATA);

        if ($lastLaunchHour != $currentHour) {
            $this->addNewTypeTimings(
                $lastLaunchHour,
                $lastLaunchTime,
                $lastLaunchDateTime->format('Y-m-d H:59:59'),
                $typeTiming
            );

            $this->addNewTypeTimings(
                $currentHour,
                $this->currentDateTime->format('Y-m-d H:00:00'),
                $currentTime,
                $typeTiming
            );
        } else {
            $this->addNewTypeTimings(
                $currentHour,
                $lastLaunchTime,
                $currentTime,
                $typeTiming
            );
        }

        $this->registryManager->setValue(self::REGISTRY_KEY_DATA, $typeTiming);
    }

    //########################################

    /**
     * @param string $hour
     * @param string $startTime
     * @param string $endTime
     * @param array $typeTiming
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Db_Statement_Exception
     */
    private function addNewTypeTimings($hour, $startTime, $endTime, &$typeTiming)
    {
        $hour .= '-00';
        $date = $this->currentDateTime->format('Y-m-d');
        $tableName = $this->listingProductInstruction->getMainTable();

        $typeQty = $this->listingProductInstruction->getConnection()->query("
            SELECT `type`, COUNT(*) as `qty`
            FROM `{$tableName}`
            WHERE `create_date` BETWEEN '{$startTime}' AND '{$endTime}'
            GROUP BY `type`
         ")->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($typeQty as $item) {
            $type = $item['type'];
            $qty = (int)$item['qty'];

            if (!isset($typeTiming[$date][$hour][$type])) {
                $typeTiming[$date][$hour][$type] = $qty;
            } else {
                $typeTiming[$date][$hour][$type] += $qty;
            }
        }
    }

    //########################################
}
