<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\Magento;

/**
 * Class \Ess\M2ePro\Helper\Magento\AttributeSet
 */
class AttributeSet extends \Ess\M2ePro\Helper\Magento\AbstractHelper
{
    protected $productFactory;
    protected $productResource;
    protected $productColFactory;
    protected $entityAttributeSetFactory;
    protected $entityAttributeSetColFactory;
    protected $resourceConnection;

    //########################################

    public function __construct(
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Catalog\Model\ResourceModel\Product $productResource,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productColFactory,
        \Magento\Eav\Model\Entity\Attribute\SetFactory $entityAttributeSetFactory,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory $entityAttributeSetColFactory,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\App\Helper\Context $context
    ) {
        $this->productFactory = $productFactory;
        $this->productResource = $productResource;
        $this->productColFactory = $productColFactory;
        $this->entityAttributeSetFactory = $entityAttributeSetFactory;
        $this->entityAttributeSetColFactory = $entityAttributeSetColFactory;
        $this->resourceConnection = $resourceConnection;
        parent::__construct(
            $objectManager,
            $helperFactory,
            $context
        );
    }

    //########################################

    public function getAll($returnType = self::RETURN_TYPE_ARRAYS)
    {
        $attributeSetsCollection = $this->entityAttributeSetColFactory->create()
            ->setEntityTypeFilter($this->productResource->getTypeId())
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
        $tableName = $this->getHelper('Module_Database_Structure')->getTableNameWithPrefix('catalog_product_entity');

        $dbSelect = $connection->select()
            ->from($tableName, 'attribute_set_id')
            ->where('`entity_id` IN ('.implode(',', $productIds).')')
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
        $tableName = $this->getHelper('Module_Database_Structure')->getTableNameWithPrefix('eav_entity_attribute');

        $dbSelect = $connection->select()
            ->from($tableName, 'attribute_set_id')
            ->where('attribute_id IN ('.implode(',', $attributeIds).')')
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

    //########################################
}
