<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\Magento;

class AttributeSet extends \Ess\M2ePro\Helper\Magento\AbstractHelper
{
    /** @var \Magento\Catalog\Model\ProductFactory */
    private $productFactory;
    /** @var \Magento\Catalog\Model\ResourceModel\Product */
    private $productResource;
    /** @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory */
    private $productColFactory;
    /** @var \Magento\Eav\Model\Entity\Attribute\SetFactory */
    private $entityAttributeSetFactory;
    /** @var \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory */
    private $entityAttributeSetColFactory;
    /** @var \Magento\Framework\App\ResourceConnection */
    private $resourceConnection;
    /** @var \Ess\M2ePro\Helper\Module\Database\Structure */
    private $dbStructure;

    /**
     * @param \Ess\M2ePro\Helper\Module\Database\Structure $dbStructure
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param \Magento\Catalog\Model\ResourceModel\Product $productResource
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productColFactory
     * @param \Magento\Eav\Model\Entity\Attribute\SetFactory $entityAttributeSetFactory
     * @param \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory $entityAttributeSetColFactory
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     */
    public function __construct(
        \Ess\M2ePro\Helper\Module\Database\Structure $dbStructure,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Catalog\Model\ResourceModel\Product $productResource,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productColFactory,
        \Magento\Eav\Model\Entity\Attribute\SetFactory $entityAttributeSetFactory,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory $entityAttributeSetColFactory,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        parent::__construct($objectManager);
        $this->productFactory = $productFactory;
        $this->productResource = $productResource;
        $this->productColFactory = $productColFactory;
        $this->entityAttributeSetFactory = $entityAttributeSetFactory;
        $this->entityAttributeSetColFactory = $entityAttributeSetColFactory;
        $this->resourceConnection = $resourceConnection;
        $this->dbStructure = $dbStructure;
    }

    // ----------------------------------------

    public function getAll($returnType = self::RETURN_TYPE_ARRAYS)
    {
        $attributeSetsCollection = $this->entityAttributeSetColFactory->create()
                                                                      ->setEntityTypeFilter(
                                                                          $this->productResource->getTypeId()
                                                                      )
                                                                      ->setOrder('attribute_set_name', 'ASC');

        return $this->_convertCollectionToReturnType($attributeSetsCollection, $returnType);
    }

    // ---------------------------------------

    public function getFromProducts($products, $returnType = self::RETURN_TYPE_ARRAYS)
    {
        $productIds = $this->_getIdsFromInput($products, 'product_id');
        if (empty($productIds)) {
            return [];
        }

        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->dbStructure->getTableNameWithPrefix('catalog_product_entity');

        $dbSelect = $connection->select()
                               ->from($tableName, 'attribute_set_id')
                               ->where('`entity_id` IN (' . implode(',', $productIds) . ')')
                               ->group('attribute_set_id');

        $result = $connection->query($dbSelect);
        $result->setFetchMode(\Zend_Db::FETCH_NUM);
        $fetchArray = $result->fetchAll();

        return $this->_convertFetchNumArrayToReturnType(
            $fetchArray,
            $returnType,
            \Eav\Model\Entity\Attribute\Set::class
        );
    }

    // ---------------------------------------

    public function getContainsAttribute($attribute, $returnType = self::RETURN_TYPE_ARRAYS)
    {
        $attributeId = $this->_getIdFromInput($attribute);
        if ($attributeId === false) {
            return [];
        }

        return $this->_getContainsAttributeIds([$attribute], $returnType);
    }

    public function getFullyContainsAttributes(array $attributes, $returnType = self::RETURN_TYPE_ARRAYS)
    {
        $attributeIds = $this->_getIdsFromInput($attributes, 'attribute_id');
        if (empty($attributeIds)) {
            return [];
        }

        return $this->_getContainsAttributeIds($attributeIds, $returnType, true);
    }

    public function getContainsAttributes(array $attributes, $returnType = self::RETURN_TYPE_ARRAYS)
    {
        $attributeIds = $this->_getIdsFromInput($attributes, 'attribute_id');
        if (empty($attributeIds)) {
            return [];
        }

        return $this->_getContainsAttributeIds($attributes, $returnType);
    }

    //########################################

    public function getProductsByAttributeSet($attributeSet, $returnType = self::RETURN_TYPE_IDS)
    {
        $attributeSetId = $this->_getIdFromInput($attributeSet);
        if ($attributeSetId === false) {
            return [];
        }

        return $this->getProductsByAttributeSets([$attributeSetId], $returnType);
    }

    public function getProductsByAttributeSets(array $attributeSets, $returnType = self::RETURN_TYPE_IDS)
    {
        $attributeSetIds = $this->_getIdsFromInput($attributeSets, 'attribute_set_id');
        if (empty($attributeSets)) {
            return [];
        }

        $productsCollection = $this->productColFactory->create();
        $productsCollection->addFieldToFilter('attribute_set_id', ['in' => $attributeSetIds]);

        return $this->_convertCollectionToReturnType($productsCollection, $returnType);
    }

    //########################################

    public function isDefault($setId)
    {
        return $this->productFactory->create()->getDefaultAttributeSetId() == $setId;
    }

    public function getName($setId)
    {
        $set = $this->entityAttributeSetFactory->create()->load($setId);

        if (!$set->getId()) {
            return null;
        }

        return $set->getData('attribute_set_name');
    }

    public function getNames(array $setIds)
    {
        $collection = $this->entityAttributeSetColFactory->create();
        $collection->addFieldToFilter('attribute_set_id', ['in' => $setIds]);

        return $collection->getColumnValues('attribute_set_name');
    }

    //########################################

    protected function _getContainsAttributeIds(
        array $attributeIds,
        $returnType = self::RETURN_TYPE_ARRAYS,
        $isFully = false
    ) {
        if (empty($attributeIds)) {
            return [];
        }

        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->dbStructure->getTableNameWithPrefix('eav_entity_attribute');

        $dbSelect = $connection->select()
                               ->from($tableName, 'attribute_set_id')
                               ->where('attribute_id IN (' . implode(',', $attributeIds) . ')')
                               ->where('entity_type_id = ?', $this->productResource->getTypeId())
                               ->group('attribute_set_id');

        if ($isFully) {
            $dbSelect->having('count(*) = ?', count($attributeIds));
        }

        $result = $connection->query($dbSelect);
        $result->setFetchMode(\Zend_Db::FETCH_NUM);
        $fetchArray = $result->fetchAll();

        return $this->_convertFetchNumArrayToReturnType(
            $fetchArray,
            $returnType,
            \Eav\Model\Entity\Attribute\Set::class
        );
    }
}
