<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\HealthStatus\Task\Result;

use Ess\M2ePro\Block\Adminhtml\HealthStatus\Tabs;
use Ess\M2ePro\Model\HealthStatus\Task\IssueType;

class Factory
{
    const CLASS_NAME = 'Ess\M2ePro\Model\HealthStatus\Task\Result';

    protected $_objectManager = null;

    //########################################

    public function __construct(
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Magento\Framework\ObjectManagerInterface $objectManager
    ){
        $this->_objectManager = $objectManager;
    }

    //########################################

    /**
     * @param \Ess\M2ePro\Model\HealthStatus\Task\AbstractModel $task
     * @return \Ess\M2ePro\Model\HealthStatus\Task\Result
     * @throws \Exception
     */
    public function create(\Ess\M2ePro\Model\HealthStatus\Task\AbstractModel $task)
    {
        $className = str_replace('Ess\M2ePro\Model\HealthStatus\Task\\', '', get_class($task));
        $className = explode('\\', $className);

        if (count($className) != 3) {
            $className = get_class($task);
            throw new \Exception("Unable to create result object for task [{$className}]");
        }

        $tabName      = preg_replace('/(?<!^)([A-Z0-9])/', ' $1', $className[0]);
        $fieldSetName = preg_replace('/(?<!^)([A-Z0-9])/', ' $1', $className[1]);
        $fieldName    = preg_replace('/(?<!^)([A-Z0-9])/', ' $1', $className[2]);

        return $this->_objectManager->create(self::CLASS_NAME, [
            'taskHash'                 => get_class($task),
            'taskType'                 => $task->getType(),
            'taskMustBeShownIfSuccess' => $task->mustBeShownIfSuccess(),
            'tabName'                  => $task->getType() == IssueType::TYPE ? $tabName : Tabs::TAB_ID_DASHBOARD,
            'fieldSetName'             => $fieldSetName,
            'fieldName'                => $fieldName
        ]);
    }

    //########################################
}
