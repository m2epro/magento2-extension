<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\HealthStatus\Task\Result;

use Ess\M2ePro\Model\HealthStatus\Task;
use Ess\M2ePro\Block\Adminhtml\HealthStatus\Tabs;
use Ess\M2ePro\Model\HealthStatus\Task\IssueType;

class LocationResolver extends \Ess\M2ePro\Model\AbstractModel
{
    const KEY_TAB       = 'tab';
    const KEY_FIELD_SET = 'field_set';
    const KEY_FIELD     = 'field';

    //########################################

    public function resolveTabName(Task\AbstractModel $task)
    {
        $result = $this->usingMap($task);
        is_null($result) && $result = $this->usingClassName($task);

        if (is_null($result)) {

            $className = get_class($task);
            throw new \Exception("Unable to create Result object for task [{$className}]");
        }

        return $result[self::KEY_TAB];
    }

    public function resolveFieldSetName(Task\AbstractModel $task)
    {
        $result = $this->usingMap($task);
        is_null($result) && $result = $this->usingClassName($task);

        if (is_null($result)) {

            $className = get_class($task);
            throw new \Exception("Unable to create Result object for task [{$className}]");
        }

        return $result[self::KEY_FIELD_SET];
    }

    public function resolveFieldName(Task\AbstractModel $task)
    {
        $result = $this->usingMap($task);
        is_null($result) && $result = $this->usingClassName($task);

        if (is_null($result)) {

            $className = get_class($task);
            throw new \Exception("Unable to create Result object for task [{$className}]");
        }

        return $result[self::KEY_FIELD];
    }

    //########################################

    private function usingMap(Task\AbstractModel $task)
    {
        $key = get_class($task);
        return array_key_exists($key, $this->getMap()) ? $this->getMap()[$key] : null;
    }

    private function usingClassName(Task\AbstractModel $task)
    {
        $className = str_replace('Ess\M2ePro\Model\HealthStatus\Task\\', '', get_class($task));
        $className = explode('\\', $className);

        if (count($className) != 3) {
            return null;
        }

        $tabName      = preg_replace('/(?<!^)([A-Z0-9])/', ' $1', $className[0]);
        $fieldSetName = preg_replace('/(?<!^)([A-Z0-9])/', ' $1', $className[1]);
        $fieldName    = preg_replace('/(?<!^)([A-Z0-9])/', ' $1', $className[2]);

        $task->getType() == IssueType::TYPE && $tabName = Tabs::TAB_ID_DASHBOARD;

        return [
            self::KEY_TAB       => $tabName,
            self::KEY_FIELD_SET => $fieldSetName,
            self::KEY_FIELD     => $fieldName
        ];
    }

    //########################################

    private function getMap()
    {
        return [
            \Ess\M2ePro\Model\HealthStatus\Task\Database\MysqlInfo\CrashedTables::class => [
                self::KEY_TAB       => 'Problems',
                self::KEY_FIELD_SET => 'Database',
                self::KEY_FIELD     => 'Crashed Tables'
            ],
            \Ess\M2ePro\Model\HealthStatus\Task\Database\MysqlInfo\TablesStructure::class => [
                self::KEY_TAB       => 'Problems',
                self::KEY_FIELD_SET => 'Database',
                self::KEY_FIELD     => 'Scheme (tables, columns)'
            ],
            \Ess\M2ePro\Model\HealthStatus\Task\Server\Status\SystemLogs::class => [
                self::KEY_TAB       => 'Problems',
                self::KEY_FIELD_SET => 'Server',
                self::KEY_FIELD     => 'System Log'
            ],
            \Ess\M2ePro\Model\HealthStatus\Task\Server\Status\GmtTime::class => [
                self::KEY_TAB       => 'Problems',
                self::KEY_FIELD_SET => 'Server',
                self::KEY_FIELD     => 'Current Time'
            ],
            \Ess\M2ePro\Model\HealthStatus\Task\Orders\IntervalToTheLatest\Amazon::class => [
                self::KEY_TAB       => 'Problems',
                self::KEY_FIELD_SET => 'Orders',
                self::KEY_FIELD     => 'Amazon'
            ],
            \Ess\M2ePro\Model\HealthStatus\Task\Orders\IntervalToTheLatest\Ebay::class => [
                self::KEY_TAB       => 'Problems',
                self::KEY_FIELD_SET => 'Orders',
                self::KEY_FIELD     => 'eBay'
            ],
        ];
    }

    //########################################
}