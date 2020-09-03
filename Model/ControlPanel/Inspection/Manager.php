<?php

namespace Ess\M2ePro\Model\ControlPanel\Inspection;

use Ess\M2ePro\Model\ControlPanel\Inspection\AbstractInspection;
use Ess\M2ePro\Model\Exception\Logic;

class Manager
{
    const GROUP_ORDERS    = 'orders';
    const GROUP_PRODUCTS  = 'products';
    const GROUP_STRUCTURE = 'structure';
    const GROUP_GENERAL   = 'general';

    const EXECUTION_SPEED_SLOW = 'slow';
    const EXECUTION_SPEED_FAST = 'fast';

    /** @var array */
    protected $_inspections = [];

    /** @var array */
    protected $_byExecution = [];

    /** @var array */
    protected $_byGroup = [];

    /** @var \Magento\Framework\ObjectManagerInterface  */
    protected $objectManager;

    //########################################

    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;

        $this->initInspections('Inspector');
    }

    protected function initInspections($dirName)
    {
        $directoryIterator = new \DirectoryIterator(__DIR__ .DIRECTORY_SEPARATOR. $dirName);
        foreach ($directoryIterator as $item) {
            if (!$item->isFile()) {
                continue;
            }

            $namespace = "Ess\M2ePro\Model\ControlPanel\Inspection\\{$dirName}\\";
            $modelName =   $namespace . str_replace('.php', '', $item->getFilename());
            $model = $this->objectManager->create($modelName);
            if (!$model instanceof AbstractInspection) {
                continue;
            }

            $id = $this->getId($model);

            $this->_inspections[$id] = $model;

            $this->_byExecution[$model->getGroup()][] = $id;
            $this->_byGroup[$model->getExecutionSpeed()][] = $id;
        }
    }

    //########################################

    public function getInspections($keys = null)
    {
        if ($keys === null) {
            return $this->_inspections;
        }

        $inspections = [];
        foreach ($keys as $key) {
            $inspections[$key] = $this->getInspection($key);
        }

        return $inspections;
    }

    /**
     * @param string $type
     * @return \Ess\M2ePro\Model\ControlPanel\Inspection\AbstractInspection[]
     */
    public function getInspectionsByGroup($type)
    {
        if (!isset($this->_byGroup[$type])) {
            return [];
        }

        return $this->getInspections($this->_byGroup[$type]);
    }

    /**
     * @param string $type
     * @return \Ess\M2ePro\Model\ControlPanel\Inspection\AbstractInspection[]
     */
    public function getInspectionsByExecutionSpeed($type)
    {
        if (!isset($this->_byExecution[$type])) {
            return [];
        }

        return $this->getInspections($this->_byExecution[$type]);
    }

    /**
     * @param string $className
     * @return \Ess\M2ePro\Model\ControlPanel\Inspection\AbstractInspection
     * @throws Logic
     */
    public function getInspection($className)
    {
        if (!isset($this->_inspections[$className])) {
            throw new Logic("No such inspection {$className}.");
        }

        return $this->_inspections[$className];
    }

    public function getId(AbstractInspection $inspection)
    {
        return get_class($inspection);
    }

    //########################################
}
