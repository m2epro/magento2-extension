<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Servicing\Task\Statistic;

/**
 * Class \Ess\M2ePro\Model\Servicing\Task\Statistic\Manager
 */
class Manager
{
    const STORAGE_KEY = 'servicing/statistic';
    const TASK_LISTING_PRODUCT_INSTRUCTION_TYPE = 'listing_product_instruction_type_statistic';

    /** @var \Ess\M2ePro\Model\Registry\Manager $registryManager */
    private $registryManager;

    public function __construct(\Ess\M2ePro\Model\Registry\Manager $registryManager)
    {
        $this->registryManager = $registryManager;
    }

    //########################################

    /**
     * @param array $data
     */
    public function setTasksStates($data)
    {
        $regData = $this->getStoredData();
        $regData['tasks'] = $data;

        $this->registryManager->setValue(self::STORAGE_KEY, $regData);
    }

    //########################################

    /**
     * @param string $taskKey
     *
     * @return bool
     */
    public function isTaskEnabled($taskKey)
    {
        $regData = $this->getStoredData();

        return isset($regData['tasks'][$taskKey]) ? (bool)$regData['tasks'][$taskKey] : false;
    }

    //########################################

    /**
     * @return array
     */
    private function getStoredData()
    {
        return $this->registryManager->getValueFromJson(self::STORAGE_KEY);
    }

    //########################################
}
