<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Servicing\Task\Analytics;

/**
 * Class \Ess\M2ePro\Model\Servicing\Task\Analytics\EntityManager
 */
class EntityManager extends \Ess\M2ePro\Model\AbstractModel
{
    protected $component;
    protected $entityType;

    protected $activeRecordFactory;
    protected $parentFactory;
    protected $registry;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory,
        \Ess\M2ePro\Model\Servicing\Task\Analytics\Registry $registry,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $params = [],
        array $data = []
    ) {
        $this->activeRecordFactory = $activeRecordFactory;
        $this->parentFactory = $parentFactory;
        $this->registry = $registry;

        parent::__construct($helperFactory, $modelFactory, $data);

        if (empty($params['component']) || empty($params['entityType'])) {
            throw new \Ess\M2ePro\Model\Exception('component or entityType was not provided.');
        }

        $this->component  = $params['component'];
        $this->entityType = $params['entityType'];

        $this->getLastId() === null && $this->initLastEntityId();
    }

    protected function initLastEntityId()
    {
        $lastIdCollection = $this->getCollection();
        $idFieldName = $lastIdCollection->getResource()->getIdFieldName();

        $lastIdCollection->getSelect()->order($idFieldName .' '. \Zend_Db_Select::SQL_DESC);
        $lastIdCollection->getSelect()->limit(1);

        $this->setLastId($lastIdCollection->getFirstItem()->getId());
    }

    //########################################

    public function getEntities()
    {
        $collection = $this->getCollection();
        $idFieldName = $collection->getResource()->getIdFieldName();

        $collection->getSelect()->order($idFieldName .' '. \Zend_Db_Select::SQL_ASC);
        $collection->getSelect()->limit($this->getLimit());
        $collection->addFieldToFilter($idFieldName, ['gt' => (int)$this->getLastProcessedId()]);

        return $collection->getItems();
    }

    /**
     * @return \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function getCollection()
    {
        $model = $this->activeRecordFactory->getObject($this->entityType);
        if ($model instanceof \Ess\M2ePro\Model\ActiveRecord\Component\Parent\AbstractModel) {
            $model = $this->parentFactory->getObject($this->component, $this->entityType);
        }

        return $model->getCollection();
    }

    //########################################

    public function isCompleted()
    {
        return (int)$this->getLastProcessedId() >= (int)$this->getLastId();
    }

    // ---------------------------------------

    public function getLastProcessedId()
    {
        return $this->registry->getProgressData($this->getEntityKey(), 'last_processed_id');
    }

    public function setLastProcessedId($id)
    {
        return $this->registry->setProgressData($this->getEntityKey(), 'last_processed_id', (int)$id);
    }

    // ---------------------------------------

    public function getLastId()
    {
        return $this->registry->getProgressData($this->getEntityKey(), 'last_id');
    }

    public function setLastId($id)
    {
        return $this->registry->setProgressData($this->getEntityKey(), 'last_id', (int)$id);
    }

    // ---------------------------------------

    public function getLimit()
    {
        return 500;
    }

    //########################################

    public function getEntityType()
    {
        return $this->entityType;
    }

    public function getComponent()
    {
        return $this->component;
    }

    public function getEntityKey()
    {
        return $this->component . '::' . $this->entityType;
    }

    //########################################
}
