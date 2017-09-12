<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\HealthStatus\Task\Result;

use Ess\M2ePro\Model\HealthStatus\Task\Result as TaskResult;

class Factory
{
    /** @var \Ess\M2ePro\Model\HealthStatus\Task\Result\LocationResolver */
    protected $locationResolver;

    /** @var \Magento\Framework\ObjectManagerInterface */
    protected $_objectManager = null;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\HealthStatus\Task\Result\LocationResolver $locationResolver,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Magento\Framework\ObjectManagerInterface $objectManager
    ){
        $this->locationResolver = $locationResolver;
        $this->_objectManager = $objectManager;
    }

    //########################################

    /**
     * @param \Ess\M2ePro\Model\HealthStatus\Task\AbstractModel $task
     * @return \Ess\M2ePro\Model\HealthStatus\Task\Result
     */
    public function create(\Ess\M2ePro\Model\HealthStatus\Task\AbstractModel $task)
    {
        return $this->_objectManager->create(TaskResult::class, [
            'taskHash'                 => get_class($task),
            'taskType'                 => $task->getType(),
            'taskMustBeShownIfSuccess' => $task->mustBeShownIfSuccess(),
            'tabName'                  => $this->locationResolver->resolveTabName($task),
            'fieldSetName'             => $this->locationResolver->resolveFieldSetName($task),
            'fieldName'                => $this->locationResolver->resolveFieldName($task)
        ]);
    }

    //########################################
}
