<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ActiveRecord;

/**
 * @method \Ess\M2ePro\Model\ActiveRecord\Relation load($id, $field=null)
 * @method \Ess\M2ePro\Model\ActiveRecord\Relation save()
 * @method \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Relation getResource()
 * @method \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Relation\Collection getCollection()
 */
class Relation extends ActiveRecordAbstract
{
    /** @var \Ess\M2ePro\Model\ActiveRecord\Relation\ParentAbstract */
    protected $parentObject;

    /** @var \Ess\M2ePro\Model\ActiveRecord\Relation\ChildAbstract */
    protected $childObject;

    /** @var string */
    protected $relationKey;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\ActiveRecord\Serializer $serializer,
        \Ess\M2ePro\Model\ActiveRecord\LockManager $lockManager,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Ess\M2ePro\Model\ActiveRecord\Relation\ParentAbstract $parentModel,
        \Ess\M2ePro\Model\ActiveRecord\Relation\ChildAbstract $childModel,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {

        $this->parentObject = $parentModel;
        $this->childObject = $childModel;

        $this->parentObject->setRelation($this);
        $this->childObject->setRelation($this);

        parent::__construct(
            $modelFactory,
            $activeRecordFactory,
            $helperFactory,
            $serializer,
            $lockManager,
            $context,
            $registry,
            $resource,
            $resourceCollection,
            $data
        );
    }

    public function _construct()
    {
        parent::_construct();
        $this->_init('M2ePro/ActiveRecord_Relation');
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\ActiveRecord\Relation\ParentAbstract
     */
    public function getParentObject()
    {
        return $this->parentObject;
    }

    /**
     * @return \Ess\M2ePro\Model\ActiveRecord\Relation\ChildAbstract
     */
    public function getChildObject()
    {
        return $this->childObject;
    }

    /**
     * @return string
     */
    public function getRelationKey()
    {
        if (null === $this->relationKey) {
            $this->relationKey = str_replace(
                'm2epro_',
                '',
                $this->getParentObject()->getResource()->getMainTable() . '_id'
            );
        }

        return $this->relationKey;
    }

    //########################################

    /**
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function isLocked()
    {
        return $this->getParentObject()->isLocked() || $this->getChildObject()->isLocked();
    }

    /**
     * @param null $tag
     * @return $this|ActiveRecordAbstract
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function lock($tag = null)
    {
        $this->getParentObject()->lock($tag);
        $this->getChildObject()->lock($tag);
        return $this;
    }

    /**
     * @param bool $tag
     * @return $this|ActiveRecordAbstract
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function unlock($tag = false)
    {
        $this->getParentObject()->unlock($tag);
        $this->getParentObject()->unlock($tag);
        return $this;
    }

    //########################################

    /**
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function delete()
    {
        $this->getParentObject()->delete();
        $this->getChildObject()->delete();
        return true;
    }

    //########################################

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->getParentObject()->getId();
    }

    /**
     * @param array|string $key
     * @param null $value
     * @return $this
     */
    public function setData($key, $value = null)
    {
        $this->_hasDataChanges = true;

        if (is_array($key)) {
            $parentData = [];
            $childData = $key;
            foreach ($key as $field => $v) {
                if ($this->getResource()->isModelContainField($this->getParentObject(), $field)) {
                    $parentData[$field] = $v;
                    unset($childData[$field]);
                }
            }

            $this->getParentObject()->setData($parentData);
            $this->getChildObject()->setData($childData);
            return $this;
        }

        if ($this->getResource()->isModelContainField($this->getParentObject(), $key)) {
            $this->getParentObject()->setData($key, $value);
            return $this;
        }

        $this->getChildObject()->setData($key, $value);
        return $this;
    }

    /**
     * @param string $key
     * @param null $index
     * @return array|mixed
     */
    public function getData($key = '', $index = null)
    {
        if (empty($key)) {
            return array_merge(
                $this->getParentObject()->getData(),
                $this->getChildObject()->getData()
            );
        }

        if ($this->getResource()->isModelContainField($this->getParentObject(), $key)) {
            return $this->getParentObject()->getData($key, $index);
        }

        return $this->getChildObject()->getData($key, $index);
    }

    /**
     * @param null $key
     * @return $this
     */
    public function unsetData($key = null)
    {
        if (null === $key) {
            $this->getParentObject()->unsetData();
            $this->getChildObject()->unsetData();
            return $this;
        }

        if ($this->getResource()->isModelContainField($this->getParentObject(), $key)) {
            $this->getParentObject()->unsetData($key);
            return $this;
        }

        $this->getChildObject()->unsetData($key);
        return $this;
    }

    /**
     * @param null $key
     * @param null $data
     * @return $this
     */
    public function setOrigData($key = null, $data = null)
    {
        if (null === $key) {
            $this->getParentObject()->setOrigData();
            $this->getChildObject()->setOrigData();
            return $this;
        }

        if ($this->getResource()->isModelContainField($this->getParentObject(), $key)) {
            $this->getParentObject()->setOrigData($key, $data);
            return $this;
        }

        $this->getChildObject()->setOrigData($key, $data);
        return $this;
    }

    /**
     * @param null $key
     * @return array|mixed
     */
    public function getOrigData($key = null)
    {
        if (null === $key) {
            return array_merge(
                $this->getParentObject()->getOrigData(),
                $this->getChildObject()->getOrigData()
            );
        }

        if ($this->getResource()->isModelContainField($this->getParentObject(), $key)) {
            return $this->getParentObject()->getOrigData($key);
        }

        return $this->getChildObject()->getOrigData($key);
    }

    public function toArray(array $arrAttributes = [])
    {
        $this->_data = $this->getData();
        return parent::toArray($arrAttributes);
    }

    //########################################

    protected function _getResource()
    {
        if (empty($this->_resourceName) && empty($this->_resource)) {
            throw new \Magento\Framework\Exception\LocalizedException(
                new \Magento\Framework\Phrase('The resource isn\'t set.')
            );
        }

        if ($this->_resource) {
            $this->_resource;
        }

        $resource = \Magento\Framework\App\ObjectManager::getInstance()->get($this->_resourceName);
        $resource->setRelationModel($this);

        return $resource;
    }

    public function getResourceCollection()
    {
        if (empty($this->_resourceCollection) && empty($this->_collectionName)) {
            throw new \Magento\Framework\Exception\LocalizedException(
                new \Magento\Framework\Phrase('Model collection resource name is not defined.')
            );
        }

        return $this->_resourceCollection
            ? clone $this->_resourceCollection
            : \Magento\Framework\App\ObjectManager::getInstance()->create(
                $this->_collectionName,
                ['relationModel' => $this]
            );
    }

    //########################################
}
