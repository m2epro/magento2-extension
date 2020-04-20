<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Magento\Attribute;

/**
 * Class \Ess\M2ePro\Model\Magento\Attribute\Relation
 */
class Relation extends \Ess\M2ePro\Model\AbstractModel
{
    protected $productFactory;
    protected $attributeFactory;
    protected $attributeSetFactory;
    protected $attributeGroupFactory;

    /** @var \Magento\Eav\Model\Entity\Attribute */
    protected $attributeObj = null;

    /** @var \Magento\Eav\Model\Entity\Attribute\Set */
    protected $attributeSetObj = null;

    protected $code;

    protected $setId;
    protected $groupName;

    protected $entityTypeId;

    protected $params = [];

    //########################################

    public function __construct(
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Eav\Model\Entity\AttributeFactory $attributeFactory,
        \Magento\Eav\Model\Entity\Attribute\SetFactory $attributeSetFactory,
        \Magento\Eav\Model\Entity\Attribute\GroupFactory $attributeGroupFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    ) {
        $this->productFactory = $productFactory;
        $this->attributeFactory = $attributeFactory;
        $this->attributeSetFactory = $attributeSetFactory;
        $this->attributeGroupFactory = $attributeGroupFactory;
        parent::__construct($helperFactory, $modelFactory);
    }

    //########################################

    public function save()
    {
        $this->init();
        return $this->saveRelation();
    }

    // ---------------------------------------

    private function init()
    {
        if ($this->entityTypeId === null) {
            $this->entityTypeId = $this->productFactory->create()->getResource()->getTypeId();
        }

        if (!($this->attributeObj instanceof \Magento\Eav\Model\Entity\Attribute)) {
            $attribute = $this->attributeFactory->create()->loadByCode($this->entityTypeId, $this->code);
            $attribute->getId() && $this->attributeObj = $attribute;
        }

        if (!($this->attributeSetObj instanceof \Magento\Eav\Model\Entity\Attribute\Set)) {
            $attributeSet = $this->attributeSetFactory->create()->load($this->setId);
            $attributeSet->getId() && $this->attributeSetObj = $attributeSet;
        }
    }

    private function saveRelation()
    {
        if (!$this->attributeObj) {
            return ['result' => false, 'error' => "Attribute '{$this->code}' is not found."];
        }

        if (!$this->attributeSetObj) {
            return ['result' => false, 'error' => "Attribute Set '{$this->setId}' is not found."];
        }

        if ($this->checkIsAlreadyInSet()) {
            return ['result' => true];
        }

        $groupId = $this->getGroupId();
        $sortOrder = !empty($this->params['sorder']) ? $this->params['sorder']
                                                     : $this->getMaxSortOrderByGroup($groupId) + 1;

        !empty($this->params['sorder_ofset']) && $sortOrder += $this->params['sorder_ofset'];

        /** @var $collection \Magento\Eav\Model\ResourceModel\Entity\Attribute */
        $relation = $this->attributeFactory->create();
        $relation->setEntityTypeId($this->attributeSetObj->getEntityTypeId())
                 ->setAttributeSetId($this->attributeSetObj->getId())
                 ->setAttributeGroupId($groupId)
                 ->setAttributeId($this->attributeObj->getId())
                 ->setSortOrder($sortOrder);

        try {
            $relation->save();
        } catch (\Exception $e) {
            return ['result' => false, 'error' => $e->getMessage()];
        }

        return ['result' => true, 'obj' => $relation];
    }

    //########################################

    private function checkIsAlreadyInSet()
    {
        /** @var $collection \Magento\Eav\Model\ResourceModel\Entity\Attribute\Collection */
        $collection = $this->attributeFactory->create()->getResourceCollection()
              ->setAttributeSetFilter($this->setId)
              ->addFieldToFilter('entity_attribute.attribute_id', $this->attributeObj->getId());

        return $collection->getSize() > 0;
    }

    private function getGroupId()
    {
        if (!$this->groupName) {
            return $this->attributeSetObj->getDefaultGroupId();
        }

        /** @var $collection \Magento\Catalog\Model\ResourceModel\Collection\AbstractCollection */
        $collection = $this->attributeGroupFactory->create()->getCollection();
        $collection->addFieldToFilter('attribute_group_name', $this->groupName);
        $collection->addFieldToFilter('attribute_set_id', $this->setId);

        $firstItem = $collection->getFirstItem();

        if ($firstItem && $firstItem->getId()) {
            return $firstItem->getId();
        }

        return $this->attributeSetObj->getDefaultGroupId();
    }

    private function getMaxSortOrderByGroup($groupId)
    {
        /** @var $collection \Magento\Eav\Model\ResourceModel\Entity\Attribute\Collection */
        $collection = $this->attributeFactory->create()->getResourceCollection();
        $collection->setAttributeSetFilter($this->setId);
        $collection->setAttributeGroupFilter($groupId);
        $collection->setOrder('sort_order', 'DESC');

        if ($firstItem = $collection->getFirstItem()) {
            return (int)$firstItem->getData('sort_order');
        }

        return 0;
    }

    //########################################

    public function setCode($value)
    {
        $this->code = $value;
        return $this;
    }

    public function setAttributeSetId($value)
    {
        $this->setId = $value;
        return $this;
    }

    public function setGroupName($value)
    {
        $this->groupName = $value;
        return $this;
    }

    public function setParams(array $value = [])
    {
        $this->params = $value;
        return $this;
    }

    public function setEntityTypeId($value)
    {
        $this->entityTypeId = $value;
        return $this;
    }

    // ---------------------------------------

    public function setAttributeObj(\Magento\Eav\Model\Entity\Attribute $obj)
    {
        $this->attributeObj = $obj;
        $this->code = $obj->getAttributeCode();

        return $this;
    }

    public function setAttributeSetObj(\Magento\Eav\Model\Entity\Attribute\Set $obj)
    {
        $this->attributeSetObj = $obj;
        $this->setId = $obj->getId();

        return $this;
    }

    //########################################
}
