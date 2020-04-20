<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Magento\AttributeSet;

/**
 * Class \Ess\M2ePro\Model\Magento\AttributeSet\Group
 */
class Group extends \Ess\M2ePro\Model\AbstractModel
{
    protected $entityAttributeSetFactory;
    protected $entityAttributeGroupFactory;

    /** @var \Magento\Eav\Model\Entity\Attribute\Group */
    protected $groupObj = null;

    /** @var \Magento\Eav\Model\Entity\Attribute\Set */
    protected $attributeSetObj = null;

    protected $name;
    protected $attributeSetId;

    protected $params = [];

    //########################################

    public function __construct(
        \Magento\Eav\Model\Entity\Attribute\SetFactory $entityAttributeSetFactory,
        \Magento\Eav\Model\Entity\Attribute\GroupFactory $entityAttributeGroupFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    ) {
        $this->entityAttributeSetFactory = $entityAttributeSetFactory;
        $this->entityAttributeGroupFactory = $entityAttributeGroupFactory;
        parent::__construct($helperFactory, $modelFactory);
    }

    //########################################

    public function save()
    {
        $this->init();
        return $this->saveGroup();
    }

    private function init()
    {
        if (!($this->attributeSetObj instanceof \Magento\Eav\Model\Entity\Attribute\Set)) {
            $attributeSet = $this->entityAttributeSetFactory->create()->load($this->attributeSetId);
            $attributeSet->getId() && $this->attributeSetObj = $attributeSet;
        }

        $tempCollection = $this->entityAttributeGroupFactory->create()->getCollection()
              ->addFieldToFilter('attribute_group_name', $this->name)
              ->addFieldToFilter('attribute_set_id', $this->attributeSetId);

        $tempCollection->getSelect()->limit(1);
        $this->groupObj = $tempCollection->getFirstItem();
    }

    // ---------------------------------------

    private function saveGroup()
    {
        if ($this->groupObj->getId()) {
            return ['result' => true];
        }

        if (!$this->attributeSetObj) {
            return ['result' => false, 'error' => "Attribute Set '{$this->attributeSetId}' is not found."];
        }

        $this->groupObj->setAttributeGroupName($this->name);
        $this->groupObj->setAttributeSetId($this->attributeSetId);

        try {
            $this->groupObj->save();
        } catch (\Exception $e) {
            return ['result' => false, 'error' => $e->getMessage()];
        }

        return ['result' => true, 'obj' => $this->groupObj];
    }

    //########################################

    public function setGroupName($value)
    {
        $this->name = $value;
        return $this;
    }

    public function setAttributeSetId($value)
    {
        $this->attributeSetId = $value;
        return $this;
    }

    public function setParams(array $value = [])
    {
        $this->params = $value;
        return $this;
    }

    // ---------------------------------------

    /**
     * @param \Magento\Eav\Model\Entity\Attribute\Set $obj
     * @return $this
     */
    public function setAttributeSetObj(\Magento\Eav\Model\Entity\Attribute\Set $obj)
    {
        $this->attributeSetObj = $obj;
        $this->attributeSetId = $obj->getId();

        return $this;
    }

    //########################################
}
