<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ControlPanel\Database;

use Ess\M2ePro\Model\Exception;
use \Ess\M2ePro\Model\ActiveRecord\Component\Parent\AbstractModel as ParentAbstractModel;

class TableModel extends \Magento\Framework\DataObject
{
    //########################################

    protected $tableName;
    protected $modelName;
    protected $isMergeModeEnabled = false;
    protected $mergeModeComponent;

    protected $modelFactory;
    protected $parentFactory;
    protected $activeRecordFactory;
    protected $helperFactory;
    protected $resourceConnection;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        array $data = []
    )
    {
        $this->modelFactory = $modelFactory;
        $this->parentFactory = $parentFactory;
        $this->activeRecordFactory = $activeRecordFactory;
        $this->helperFactory = $helperFactory;
        $this->resourceConnection = $resourceConnection;
        parent::__construct(
            $data
        );

        $this->tableName          = isset($data['table_name']) ? $data['table_name'] : null;
        $this->isMergeModeEnabled = isset($data['merge_mode']) ? $data['merge_mode'] : false;
        $this->mergeModeComponent = isset($data['merge_mode_component']) ? $data['merge_mode_component'] : null;

        $this->init();
    }

    private function init()
    {
        $helper = $this->helperFactory->getObject('Module\Database\Structure');
        $this->modelName = $helper->getTableModel($this->tableName);

        if (!$this->modelName) {
            throw new Exception("Specified table '{$this->tableName}' cannot be managed.");
        }

        if (!$this->isMergeModeEnabled) {
            return;
        }

        if (!$helper->isTableHorizontal($this->tableName)) {

            $this->isMergeModeEnabled = false;
            $this->mergeModeComponent = null;
            return;
        }

        if ($helper->isTableHorizontalChild($this->tableName)) {

            preg_match('/(Ebay|Amazon|Buy)/i', $this->modelName, $matches);
            $this->mergeModeComponent = isset($matches[1]) ? strtolower($matches[1]) : null;
            $this->modelName = str_replace($matches[1].'\\', '', $this->modelName);
        }

        if (!$this->mergeModeComponent && $this->isMergeModeEnabled) {
            $this->isMergeModeEnabled = false;
        }
    }

    //########################################

    public function getColumns()
    {
        $prefix = $this->helperFactory->getObject('Magento')->getDatabaseTablesPrefix();
        $helper = $this->helperFactory->getObject('Module\Database\Structure');

        $tableName = $this->activeRecordFactory->getObject($this->modelName)
                                               ->getResource()->getMainTable();
        $tableName = str_replace($prefix, '', $tableName);

        $resultColumns = $helper->getTableInfo($tableName);
        $isParent = $this->isMergeModeEnabled && $helper->isTableHorizontalParent($tableName);
        $isChild  = $this->isMergeModeEnabled && $helper->isTableHorizontalChild($tableName);

        array_walk($resultColumns, function (&$el) use ($isParent, $isChild) {
            $el['is_parent'] = $isParent;
            $el['is_child']  = $isChild;
        });

        if ($this->isMergeModeEnabled) {

            $mergeTableName = $this->activeRecordFactory->getObject($this->getMergeModelName())
                                                        ->getResource()->getMainTable();
            $mergeTableName = str_replace($prefix, '', $mergeTableName);

            $columns  = $helper->getTableInfo($mergeTableName);
            $isParent = $helper->isTableHorizontalParent($mergeTableName);
            $isChild  = $helper->isTableHorizontalChild($mergeTableName);

            array_walk($columns, function (&$el) use ($isParent, $isChild) {
                $el['is_parent'] = $isParent;
                $el['is_child']  = $isChild;
            });

            $resultColumns = array_merge($resultColumns, $columns);
        }

        return $resultColumns;
    }

    public function getModel()
    {
        if (!$this->isMergeModeEnabled) {
            return $this->activeRecordFactory->getObject($this->modelName);
        }

        return $this->parentFactory->getObject($this->mergeModeComponent, $this->modelName);
    }

    //########################################

    public function createEntry(array $data)
    {
        $helper = $this->helperFactory->getObject('Module\Database\Structure');
        $modelInstance = $this->getModel();

        $idFieldName = $modelInstance->getIdFieldName();
        $isIdAutoIncrement = $helper->isIdColumnAutoIncrement($this->tableName);
        if ($isIdAutoIncrement) {
            unset($data[$idFieldName]);
        }

        $modelInstance->setData($data);

        $modelInstance->isObjectCreatingState(true);
        $modelInstance->getResource()->save($modelInstance);
        $modelInstance->isObjectCreatingState(false);
    }

    public function deleteEntries(array $ids)
    {
        $modelInstance = $this->getModel();
        $collection = $modelInstance->getCollection();
        $collection->addFieldToFilter($modelInstance->getIdFieldName(), array('in' => $ids));

        foreach ($collection as $item) {

            $item->getResource()->delete($item);

            if ($this->getIsMergeModeEnabled() && $item instanceof ParentAbstractModel) {
                $item->getChildObject()->getResource()->delete($item->getChildObject());
            }
        }
    }

    public function updateEntries(array $ids,  array $data)
    {
        $helper = $this->helperFactory->getObject('Module\Database\Structure');
        $modelInstance = $this->getModel();

        $collection = $modelInstance->getCollection();
        $collection->addFieldToFilter($modelInstance->getIdFieldName(), array('in' => $ids));

        $idFieldName = $modelInstance->getIdFieldName();
        $isIdAutoIncrement = $helper->isIdColumnAutoIncrement($this->tableName);
        if ($isIdAutoIncrement) {
            unset($data[$idFieldName]);
        }

        if ($this->getIsMergeModeEnabled() && $modelInstance instanceof ParentAbstractModel) {

            $childObject = $this->activeRecordFactory->getObject($this->getMergeModelName());
            $childIdFieldName = $childObject->getIdFieldName();
            unset($data[$childIdFieldName]);
        }

        if (empty($data)) {
            return;
        }

        foreach ($collection->getItems() as $item) {
            /** @var \Ess\M2ePro\Model\ActiveRecord\AbstractModel $item */

            foreach ($data as $field => $value) {

                if ($field == $idFieldName && !$isIdAutoIncrement) {

                    $this->resourceConnection->getConnection()->update(
                        $this->resourceConnection->getTableName($this->tableName),
                        array($idFieldName => $value),
                        "`{$idFieldName}` = {$item->getId()}"
                    );
                }

                $item->setData($field, $value);

                if ($this->getIsMergeModeEnabled() && $item instanceof ParentAbstractModel) {
                    $item->getChildObject()->setData($field, $value);
                }
            }

            $item->getResource()->save($item);
            if ($item instanceof ParentAbstractModel && $item->hasChildObjectLoaded()) {
                $item->getChildObject()->getResource()->save($item->getChildObject());
            }
        }
    }

    //########################################

    public function getTableName()
    {
        return $this->tableName;
    }

    public function getModelName()
    {
        return $this->modelName;
    }

    public function getMergeModelName()
    {
        if (!$this->getIsMergeModeEnabled()) {
            return null;
        }
        return ucfirst($this->mergeModeComponent).'\\'.$this->modelName;
    }

    public function getIsMergeModeEnabled()
    {
        return $this->isMergeModeEnabled;
    }

    public function getMergeModeComponent()
    {
        return $this->mergeModeComponent;
    }

    //########################################
}