<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Template;

/**
 * Class \Ess\M2ePro\Model\Template\AffectedListingsProductsAbstract
 */
abstract class AffectedListingsProductsAbstract extends \Ess\M2ePro\Model\AbstractModel
{
    protected $activeRecordFactory;

    /** @var \Ess\M2ePro\Model\ActiveRecord\AbstractModel */
    protected $model = null;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $data = []
    ) {
        $this->activeRecordFactory = $activeRecordFactory;
        parent::__construct($helperFactory, $modelFactory, $data);
    }

    //########################################

    public function setModel(\Ess\M2ePro\Model\ActiveRecord\AbstractModel $model)
    {
        $this->model = $model;
        return $this;
    }

    public function getModel()
    {
        return $this->model;
    }

    //########################################

    public function getObjects(array $filters = [])
    {
        return $this->loadCollection($filters)->getItems();
    }

    public function getObjectsData($columns = '*', array $filters = [])
    {
        $productCollection = $this->loadCollection($filters);

        if (is_array($columns) && !empty($columns)) {
            $productCollection->getSelect()->reset(\Zend_Db_Select::COLUMNS);
            $columns && $productCollection->getSelect()->columns($columns);
        }

        return $productCollection->getData();
    }

    public function getIds(array $filters = [])
    {
        return $this->loadCollection($filters)->getAllIds();
    }

    //########################################

    /**
     * @param array $filters
     * @return \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Collection\Component\AbstractModel
     */
    abstract public function loadCollection(array $filters = []);

    //########################################
}
