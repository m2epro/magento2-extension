<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\ActiveRecord\Relation;

/**
 * @method \Ess\M2ePro\Model\ActiveRecord\Relation[] getItems()
 * @method \Ess\M2ePro\Model\ActiveRecord\Relation[] getItemsByColumnValue($column, $value)
 * @method \Ess\M2ePro\Model\ActiveRecord\Relation getFirstItem()
 * @method \Ess\M2ePro\Model\ActiveRecord\Relation getLastItem()
 * @method \Ess\M2ePro\Model\ActiveRecord\Relation getItemByColumnValue($column, $value)
 * @method \Ess\M2ePro\Model\ActiveRecord\Relation getItemById($idValue)
 * @method \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Relation\Collection addFieldToFilter($field, $condition = null)
 * @method \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Relation\Collection setOrder($field, $direction)
 */
class Collection extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\CollectionAbstract
{
    /** @var \Ess\M2ePro\Model\ActiveRecord\Relation */
    protected $relationModel;

    /** @var \Magento\Framework\ObjectManagerInterface */
    protected $objectManager;

    //########################################

    public function __construct(
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\Data\Collection\EntityFactoryInterface $entityFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Ess\M2ePro\Model\ActiveRecord\Relation $relationModel,
        \Magento\Framework\DB\Adapter\AdapterInterface $connection = null,
        \Magento\Framework\Model\ResourceModel\Db\AbstractDb $resource = null
    ) {
        $this->relationModel = $relationModel;
        $this->objectManager = $objectManager;
        parent::__construct(
            $helperFactory,
            $activeRecordFactory,
            $entityFactory,
            $logger,
            $fetchStrategy,
            $eventManager,
            $connection,
            $resource
        );
    }

    public function _construct()
    {
        parent::_construct();
        $this->_init(
            'Ess\M2ePro\Model\ActiveRecord\Relation',
            'Ess\M2ePro\Model\ResourceModel\ActiveRecord\Relation'
        );
    }

    //########################################

    public function getMainTable()
    {
        return $this->relationModel->getParentObject()->getResource()->getMainTable();
    }

    public function getSecondTable()
    {
        return $this->relationModel->getChildObject()->getResource()->getMainTable();
    }

    public function getNewEmptyItem()
    {
        $childModel = $this->activeRecordFactory->getObject(
            $this->relationModel->getChildObject()->getObjectModelName()
        );

        $parentModel = $this->activeRecordFactory->getObject(
            $this->relationModel->getParentObject()->getObjectModelName()
        );

        return $this->objectManager->create(
            \Ess\M2ePro\Model\ActiveRecord\Relation::class,
            [
                'parentObject' => $parentModel,
                'childObject'  => $childModel
            ]
        );
    }

    /**
     * @return \Ess\M2ePro\Model\ActiveRecord\Relation\ParentAbstract[]
     */
    public function getParentItems()
    {
        $items = [];
        foreach ($this->getItems() as $item) {
            $parentModel = $item->getParentObject();
            $items[$parentModel->getId()] = $parentModel;
        }

        return $items;
    }

    /**
     * @return \Ess\M2ePro\Model\ActiveRecord\Relation\ChildAbstract[]
     */
    public function getChildItems()
    {
        $items = [];
        foreach ($this->getItems() as $item) {
            $childModel = $item->getChildObject();
            $items[$childModel->getId()] = $childModel;
        }

        return $items;
    }

    //########################################

    protected function _initSelect()
    {
        parent::_initSelect();

        $primaryKey = $this->relationModel->getParentObject()->getResource()->getIdFieldName();
        $foreignKey = $this->relationModel->getRelationKey();

        $this->getSelect()->join(
            ['second_table' => $this->getSecondTable()],
            "`second_table`.`{$foreignKey}` = `main_table`.`{$primaryKey}`"
        );

        return $this;
    }

    protected function _initSelectFields()
    {
        parent::_initSelectFields();

        $removeChildTableWildcard = false;
        $columns = [];
        foreach ($this->_select->getPart(\Magento\Framework\DB\Select::COLUMNS) as $fieldData) {
            list($tableAlias, $fieldName, $alias) = $fieldData;
            /**
             * By default addFieldToSelect() method set all fields under main_table
             * We need split main_table and second table fields
             */
            if ($tableAlias === 'main_table') {
                if ($this->getResource()->isModelContainField($this->relationModel->getChildObject(), $fieldName)) {
                    $columns[] = ['second_table', $fieldName, $alias];
                    $removeChildTableWildcard = true;
                    continue;
                }
            }

            $columns[] = $fieldData;
        }

        if ($removeChildTableWildcard) {
            array_splice(
                $columns,
                array_search(['second_table', '*'], $columns),
                1
            );
        }

        $this->_select->setPart(\Magento\Framework\DB\Select::COLUMNS, $columns);
        return $this;
    }

    //########################################
}
