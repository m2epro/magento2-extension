<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\HealthStatus\Task\Result;

use Ess\M2ePro\Model\HealthStatus\Task\Result;

class Set extends \Ess\M2ePro\Model\AbstractModel
{
    /** @var Result[] */
    private $results = [];
    private $keys = [];

    private $worstState = Result::STATE_SUCCESS;

    //########################################

    /**
     * @param Result $result
     */
    public function add(Result $result)
    {
        $key = $result->getTaskHash();
        $this->results[$key] = $result;

        $this->keys['tab'][$this->getTabKey($result)][] = $key;
        $this->keys['fieldset'][$this->getFieldSetKey($result)][] = $key;
        $this->keys['type'][$result->getTaskType()][] = $key;

        if ($result->getTaskResult() > $this->worstState) {
            $this->worstState = $result->getTaskResult();
        }
    }

    /**
     * @param Result[] $results
     */
    public function fill(array $results)
    {
        $this->clear();

        foreach ($results as $result) {
            $this->add($result);
        }
    }

    public function clear()
    {
        $this->results = [];
        $this->keys = [];
    }

    //########################################

    /**
     * @param string $taskType
     * @return Result[]
     */
    public function getByType($taskType)
    {
        $affectedKeys = isset($this->keys['type'][$taskType]) ? $this->keys['type'][$taskType] : [];
        return $this->getByKeys($affectedKeys);
    }

    /**
     * @param string $tabKey
     * @return Result[]
     */
    public function getByTab($tabKey)
    {
        $affectedKeys = isset($this->keys['tab'][$tabKey]) ? $this->keys['tab'][$tabKey] : [];
        return $this->getByKeys($affectedKeys);
    }

    /**
     * @param string $fieldSetKey
     * @return Result[]
     */
    public function getByFieldSet($fieldSetKey)
    {
        $affectedKeys = isset($this->keys['fieldset'][$fieldSetKey]) ? $this->keys['fieldset'][$fieldSetKey] : [];
        return $this->getByKeys($affectedKeys);
    }

    /**
     * @param array|NULL $affectedKeys
     * @return Result[]
     */
    public function getByKeys($affectedKeys = null)
    {
        if (is_null($affectedKeys)) {
            return $this->results;
        }

        $results = [];
        foreach ($affectedKeys as $affectedKey) {
            if (isset($this->results[$affectedKey])) {
                $results[$affectedKey] = $this->results[$affectedKey];
            }
        }

        return $results;
    }

    //########################################

    public function getTabKey(Result $result)
    {
        return strtolower(preg_replace('/[^A-za-z0-9_]/', '', $result->getTabName()));
    }

    public function getFieldSetKey(Result $result)
    {
        return strtolower(preg_replace('/[^A-za-z0-9_]/', '', $result->getTabName() . $result->getFieldSetName()));
    }

    //########################################

    public function getWorstState()
    {
        return $this->worstState;
    }

    public function isCritical()
    {
        return $this->getWorstState() == Result::STATE_CRITICAL;
    }

    public function isWaring()
    {
        return $this->getWorstState() == Result::STATE_WARNING;
    }

    public function isNotice()
    {
        return $this->getWorstState() == Result::STATE_NOTICE;
    }

    public function isSuccess()
    {
        return $this->getWorstState() == Result::STATE_SUCCESS;
    }

    //########################################
}