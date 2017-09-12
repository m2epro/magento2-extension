<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\Magento;

class Attribute extends AbstractHelper
{
    const PRICE_CODE = 'price';
    const SPECIAL_PRICE_CODE = 'special_price';

    private $modelFactory;
    private $productResource;
    private $resourceConnection;
    private $attributeColFactory;
    private $eavEntityAttributeColFactory;
    private $eavConfig;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Magento\Catalog\Model\ResourceModel\Product $productResource,
        \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory $attributeColFactory,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute\CollectionFactory $eavEntityAttributeColFactory,
        \Magento\Eav\Model\Config $eavConfig,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\App\Helper\Context $context
    )
    {
        $this->modelFactory = $modelFactory;
        $this->productResource = $productResource;
        $this->attributeColFactory = $attributeColFactory;
        $this->eavEntityAttributeColFactory = $eavEntityAttributeColFactory;
        $this->eavConfig = $eavConfig;
        $this->resourceConnection = $resourceConnection;
        parent::__construct($objectManager, $helperFactory, $context);
    }

    //########################################

    public function getAll()
    {
        /** @var \Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection $attributeCollection */
        $attributeCollection = $this->getPreparedAttributeCollection();
        $attributeCollection->addVisibleFilter();

        $resultAttributes = array();
        foreach ($attributeCollection->getItems() as $attribute) {
            $resultAttributes[] = array(
                'code' => $attribute['attribute_code'],
                'label' => $attribute['frontend_label']
            );
        }

        return $resultAttributes;
    }

    public function getAllAsObjects()
    {
        $attributes = $this->getPreparedAttributeCollection()
            ->addVisibleFilter()
            ->getItems();

        return $attributes;
    }

    // ---------------------------------------

    public function getByCode($code, $returnType = self::RETURN_TYPE_ARRAYS)
    {
        $attributeCollection = $this->getPreparedAttributeCollection()
            ->addVisibleFilter()
            ->setCodeFilter($code);

        $attributes = $this->_convertCollectionToReturnType($attributeCollection, $returnType);
        if ($returnType != self::RETURN_TYPE_ARRAYS) {
            return $attributes;
        }

        $resultAttributes = array();
        foreach ($attributeCollection->getItems() as $attribute) {
            $resultAttributes[] = array(
                'code' => $attribute['attribute_code'],
                'label' => $attribute['frontend_label']
            );
        }

        return $resultAttributes;
    }

    // ---------------------------------------

    public function getByAttributeSet($attributeSet, $returnType = self::RETURN_TYPE_ARRAYS)
    {
        $attributeSetId = $this->_getIdFromInput($attributeSet);
        if ($attributeSetId === false) {
            return array();
        }

        return $this->getByAttributeSets(array($attributeSetId), $returnType);
    }

    public function getByAttributeSets(array $attributeSets, $returnType = self::RETURN_TYPE_ARRAYS)
    {
        $attributeSetIds = $this->_getIdsFromInput($attributeSets, 'attribute_set_id');
        if (empty($attributeSetIds)) {
            return array();
        }

        $attributeCollection = $this->getPreparedAttributeCollection()
            ->addVisibleFilter()
            ->setAttributeSetFilter($attributeSetIds);

        $attributeCollection->getSelect()->group('entity_attribute.attribute_id');

        $attributes = $this->_convertCollectionToReturnType($attributeCollection, $returnType);
        if ($returnType != self::RETURN_TYPE_ARRAYS) {
            return $attributes;
        }

        $resultAttributes = array();
        foreach ($attributes as $attribute) {
            $resultAttributes[] = array(
                'code' => $attribute['attribute_code'],
                'label' => $attribute['frontend_label']
            );
        }

        return $resultAttributes;
    }

    //########################################

    public function getGeneralFromAttributeSets(array $attributeSets)
    {
        $attributeSetIds = $this->_getIdsFromInput($attributeSets, 'attribute_set_id');
        if (empty($attributeSetIds)) {
            return array();
        }

        $attributes = array();
        $isFirst = true;
        $idsParts = array_chunk($attributeSetIds, 50);
        foreach ($idsParts as $part) {
            $tempAttributes = $this->_getGeneralFromAttributeSets($part);

            if ($isFirst) {
                $attributes = $tempAttributes;
                $isFirst = false;

                continue;
            }

            if (!$isFirst && empty($attributes)) {
                return array();
            }

            $attributes = array_intersect($attributes, $tempAttributes);
        }

        if (empty($attributes)) {
            return array();
        }

        $attributesData = $this->getPreparedAttributeCollection()
            ->addVisibleFilter()
            ->addFieldToFilter('main_table.attribute_id', array('in' => $attributes))
            ->toArray();

        $resultAttributes = array();
        foreach ($attributesData['items'] as $attribute) {
            $resultAttributes[] = array(
                'code' => $attribute['attribute_code'],
                'label' => $attribute['frontend_label'],
            );
        }

        return $resultAttributes;
    }

    public function getGeneralFromAllAttributeSets()
    {
        $allAttributeSets = $this->getHelper('Magento\AttributeSet')->getAll(self::RETURN_TYPE_IDS);
        return $this->getGeneralFromAttributeSets($allAttributeSets);
    }

    // ---------------------------------------

    private function _getGeneralFromAttributeSets(array $attributeSetIds)
    {
        if (count($attributeSetIds) > 50) {
            throw new \Ess\M2ePro\Model\Exception("Attribute sets must be less then 50");
        }

        $attributeCollection = $this->getPreparedAttributeCollection()
            ->addVisibleFilter()
            ->setInAllAttributeSetsFilter($attributeSetIds);

        return $attributeCollection->getAllIds();
    }

    // ---------------------------------------

    public function getGeneralFromProducts(array $products)
    {
        $productsAttributeSetIds = $this->getHelper('Magento\AttributeSet')->getFromProducts(
            $products, self::RETURN_TYPE_IDS
        );

        return $this->getGeneralFromAttributeSets($productsAttributeSetIds);
    }

    //########################################

    public function getConfigurableByAttributeSets(array $attributeSets)
    {
        if (empty($attributeSets)) {
            return array();
        }

        return $this->getConfigurable($attributeSets);
    }

    public function getAllConfigurable()
    {
        return $this->getConfigurable();
    }

    // ---------------------------------------

    private function getConfigurable(array $attributeSetIds = array())
    {
        /** @var $connection \Magento\Framework\DB\Adapter\AdapterInterface */
        $connection = $this->resourceConnection->getConnection();

        $cpTable  = $this->resourceConnection->getTableName('catalog_product_entity');
        $saTable  = $this->resourceConnection->getTableName('catalog_product_super_attribute');
        $aTable   = $this->resourceConnection->getTableName('eav_attribute');

        $select = $connection->select()
            ->distinct(true)
            ->from(array('p' => $cpTable), null)
            ->join(
                array('sa' => $saTable),
                'p.entity_id = sa.product_id',
                null
            )
            ->join(
                array('a' => $aTable),
                'sa.attribute_id = a.attribute_id',
                array('label' => 'frontend_label', 'code' => 'attribute_code')
            )
            ->where('p.type_id = ?', \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE);

        if (!empty($attributeSetIds)) {
            $select->where('e.attribute_set_id IN ?', $attributeSetIds);
        }

        return $connection->fetchAll($select);
    }

    //########################################

    public function getAttributeLabel($attributeCode, $storeId = \Magento\Store\Model\Store::DEFAULT_STORE_ID)
    {
        /** @var $attribute \Magento\Eav\Model\Entity\Attribute\AbstractAttribute */
        $attribute = $this->productResource->getAttribute($attributeCode);

        if (!$attribute) {
            return $attributeCode;
        }

        $label = $attribute->getStoreLabel($storeId);
        $label == '' && $label = $attribute->getFrontendLabel();

        return $label == '' ? $attributeCode : $label;
    }

    public function getAttributesLabels(array $attributeCodes)
    {
        if (empty($attributeCodes)) {
            return array();
        }

        /** @var $connection \Magento\Framework\DB\Adapter\AdapterInterface */
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName('eav_attribute');

        $entityTypeId = $this->eavConfig->getEntityType(\Magento\Catalog\Model\Product::ENTITY)->getId();
        $dbSelect = $connection->select();
        $dbSelect->from($tableName)
            ->where('attribute_code in (\''.implode('\',\'', $attributeCodes).'\')')
            ->where('entity_type_id = ?', $entityTypeId);
        $fetchResult = $connection->fetchAll($dbSelect);

        $result = array();
        foreach ($fetchResult as $attribute) {
            $result[] = array(
                'label' => $attribute['frontend_label'],
                'code'  => $attribute['attribute_code']
            );
        }

        return $result;
    }

    public function isExistInAttributesArray($attributeCode, array $attributes)
    {
        if ($attributeCode == '') {
            return false;
        }

        foreach ($attributes as $attribute) {
            if ($attribute['code'] == $attributeCode) {
                return true;
            }
        }
        return false;
    }

    public function filterByInputTypes(array $attributes,
                                       array $frontendInputTypes = [],
                                       array $backendInputTypes = [])
    {
        if (empty($attributes)) {
            return array();
        }

        if (empty($frontendInputTypes) && empty($backendInputTypes)) {
            return $attributes;
        }

        $attributeCodes = array();
        foreach ($attributes as $attribute) {
            $attributeCodes[] = $attribute['code'];
        }

        $attributeCollection = $this->getPreparedAttributeCollection()
            ->addFieldToFilter('attribute_code', array('in' => $attributeCodes));

        if (!empty($frontendInputTypes)) {
            $attributeCollection->addFieldToFilter('frontend_input', array('in' => $frontendInputTypes));
        }
        if (!empty($backendInputTypes)) {
            $attributeCollection->addFieldToFilter('backend_type', array('in' => $backendInputTypes));
        }

        $filteredAttributes = $attributeCollection->toArray();
        $resultAttributes = array();
        foreach ($filteredAttributes['items'] as $attribute) {
            $resultAttributes[] = array(
                'code' => $attribute['attribute_code'],
                'label' => $attribute['frontend_label'],
            );
        }

        return $resultAttributes;
    }

    //########################################

    public function getSetsFromProductsWhichLacksAttributes(array $attributes, array $productIds)
    {
        if (count($attributes) == 0 || count($productIds) == 0) {
            return array();
        }

        $scopeAttributesOptionArray = $this->getHelper('Magento\Attribute')->getGeneralFromProducts($productIds);
        $scopeAttributes = array();
        foreach ($scopeAttributesOptionArray as $scopeAttributesOption) {
            $scopeAttributes[] = $scopeAttributesOption['code'];
        }

        $missingAttributes = array_diff($attributes, $scopeAttributes);

        if (count($missingAttributes) == 0) {
            return array();
        }

        $attributesCollection = $this->eavEntityAttributeColFactory->create()
            ->setEntityTypeFilter($this->productResource->getTypeId())
            ->addFieldToFilter('attribute_code', array('in' => $missingAttributes))
            ->addSetInfo(true);

        $attributeSets = $this->getHelper('Magento\AttributeSet')
            ->getFromProducts(
                $productIds,
                \Ess\M2ePro\Helper\Magento\AbstractHelper::RETURN_TYPE_IDS
            );

        $missingAttributesSets = array();

        foreach ($attributesCollection->getItems() as $attribute) {
            foreach ($attributeSets as $setId) {
                if (!$attribute->isInSet($setId)) {
                    $missingAttributesSets[] = $setId;
                }
            }
        }

        return array_unique($missingAttributesSets);
    }

    //########################################

    /**
     * Now Magento returns strange combined QTY and StockStatus Attribute. This attribute will not work for
     * Tracking of Attributes and we will skip it.
     *
     * @return \Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection
     */
    private function getPreparedAttributeCollection()
    {
        $collection = $this->attributeColFactory->create();
        $collection->addFieldToFilter('attribute_code', array('neq' => 'quantity_and_stock_status'));
        $collection->setOrder('frontend_label', \Magento\Framework\Data\Collection::SORT_ORDER_ASC);

        return $collection;
    }

    //########################################

    public function isAttributeInputTypePrice($attributeCode)
    {
        $attributes = $this->filterByInputTypes(
            [['code' => $attributeCode]], ['price']
        );

        return count($attributes) > 0;
    }

    public function convertAttributeTypePriceFromStoreToMarketplace(\Ess\M2ePro\Model\Magento\Product $magentoProduct,
                                                                    $attributeCode, $currencyCode, $store)
    {
        $attributeValue = $magentoProduct->getAttributeValue($attributeCode);

        if (empty($attributeValue)) {
            return $attributeValue;
        }

        $isPriceConvertEnabled = (int)$this->getHelper('Module')->getConfig()->getGroupValue(
            '/magento/attribute/', 'price_type_converting'
        );

        if ($isPriceConvertEnabled && $this->isAttributeInputTypePrice($attributeCode)) {

            $attributeValue = $this->modelFactory->getObject('Currency')->convertPrice(
                $attributeValue,
                $currencyCode,
                $store
            );
        }

        return $attributeValue;
    }

    //########################################
}