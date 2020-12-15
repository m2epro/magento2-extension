<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Listing\SynchronizeInventory;

use Ess\M2ePro\Model\Amazon\Listing\Product\Variation\Manager\Type\Relation\ParentRelation\Processor\Mass
    as AmazonProcessor;
use Ess\M2ePro\Model\Walmart\Listing\Product\Variation\Manager\Type\Relation\ParentRelation\Processor\Mass
    as WalmartProcessor;

/**
 * Class \Ess\M2ePro\Model\Listing\SynchronizeInventory\AbstractExistingProductsHandler
 * @package
 */
abstract class AbstractExistingProductsHandler extends AbstractHandler
{
    /** @var array */
    protected $responseData;

    //########################################

    /**
     * @return \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Collection\AbstractModel
     */
    abstract protected function getPreparedProductsCollection();

    /**
     * @param array $ids
     * @return \Zend_Db_Statement_Interface
     */
    protected function getPdoStatementExistingListings(array $ids)
    {
        $ids = array_map(function ($id) { return (string) $id; }, $ids);

        $select = clone $this->getPreparedProductsCollection()->getSelect();
        $select->where("`second_table`.`{$this->getInventoryIdentifier()}` IN (?)", $ids);

        return $this->resourceConnection->getConnection()->query($select->__toString());
    }

    /**
     * @param array $parentIds
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function processParentProcessors(array $parentIds)
    {
        if (empty($parentIds)) {
            return;
        }

        $component = ucfirst($this->getComponentMode());

        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection $collection */
        $collection = $this->parentFactory->getObject($this->getComponentMode(), 'Listing\Product')->getCollection();
        $collection->addFieldToFilter('id', ['in' => array_unique($parentIds)]);

        $parentListingsProducts = $collection->getItems();
        if (empty($parentListingsProducts)) {
            return;
        }

        /** @var AmazonProcessor|WalmartProcessor $massProcessor */
        $massProcessor = $this->modelFactory->getObject(
            "{$component}_Listing_Product_Variation_Manager_Type_Relation_ParentRelation_Processor_Mass"
        );
        $massProcessor->setListingsProducts($parentListingsProducts);
        $massProcessor->setForceExecuting(false);

        $massProcessor->execute();
    }

    /**
     * @param array $existData
     * @param array $newData
     * @param $key
     * @return bool
     */
    protected function isDataChanged($existData, $newData, $key)
    {
        if (!isset($existData[$key]) || !isset($newData[$key])) {
            return false;
        }

        return $existData[$key] != $newData[$key];
    }

    //########################################
}
