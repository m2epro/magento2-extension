<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ActiveRecord\Component\Parent;

abstract class AbstractModel extends \Ess\M2ePro\Model\ActiveRecord\Component\AbstractModel
{
    protected $childMode = NULL;

    /**
     * @var \Ess\M2ePro\Model\ActiveRecord\Component\Child\AbstractModel
     */
    protected $childObject = NULL;

    protected $parentFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    )
    {
        $this->parentFactory = $parentFactory;
        parent::__construct(
            $modelFactory,
            $activeRecordFactory,
            $helperFactory,
            $context,
            $registry,
            $resource,
            $resourceCollection,
            $data
        );
    }

    //########################################

    /**
     * @param string $mode
     * @return $this
     */
    public function setChildMode($mode)
    {
        $mode = strtolower((string)$mode);
        $mode && $this->childMode = $mode;
        return $this;
    }

    public function getChildMode()
    {
        return $this->childMode;
    }

    // ---------------------------------------

    public function hasChildObjectLoaded()
    {
        return !is_null($this->childObject);
    }

    public function setChildObject(\Ess\M2ePro\Model\ActiveRecord\Component\Child\AbstractModel $object)
    {
        if (is_null($object->getId())) {
            return;
        }

        $this->childObject = $object;
    }

    /**
     * @return \Ess\M2ePro\Model\ActiveRecord\Component\Child\AbstractModel
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getChildObject()
    {
        if (is_null($this->getId())) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Method require loaded instance first');
        }

        if (!is_null($this->childObject)) {
            return $this->childObject;
        }

        $tempMode = NULL;

        if (!is_null($this->childMode)) {
            $tempMode = $this->childMode;
        } else {
            $tempMode = $this->getComponentMode();
        }

        if (!$tempMode) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Component Mode is not defined.');
        }

        $modelName = str_replace('Ess\M2ePro\Model\ResourceModel',ucwords($tempMode),$this->_resourceName);

        if ($this->isCacheLoading()) {
            $this->childObject = $this->activeRecordFactory->getCachedObjectLoaded($modelName, $this->getId());
        } else {
            $this->childObject = $this->activeRecordFactory->getObjectLoaded($modelName, $this->getId());
        }

        $this->childObject->setParentObject($this);

        return $this->childObject;
    }

    //########################################

    public function getComponentMode()
    {
        if (is_null($this->getId())) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Method require loaded instance first');
        }

        return $this->getData('component_mode');
    }

    // ---------------------------------------

    public function isComponentModeEbay()
    {
        return $this->getComponentMode() == \Ess\M2ePro\Helper\Component\Ebay::NICK;
    }

    public function isComponentModeAmazon()
    {
        return $this->getComponentMode() == \Ess\M2ePro\Helper\Component\Amazon::NICK;
    }

    // ---------------------------------------

    public function getComponentTitle()
    {
        if ($this->isComponentModeEbay()) {
            return $this->getHelper('Component')->getEbayComponentHelper()->getTitle();
        }

        if ($this->isComponentModeAmazon()) {
            return $this->getHelper('Component')->getAmazonComponentHelper()->getTitle();
        }

        return '';
    }

    //########################################

    public function isLocked()
    {
        if (is_null($this->getId())) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Method require loaded instance first');
        }

        if (parent::isLocked()) {
            return true;
        }

        $childObject = $this->getChildObject();

        if (is_null($childObject)) {
            return false;
        }

        if ($childObject->isLocked()) {
            return true;
        }

        return false;
    }

    // ---------------------------------------

    public function save($reloadOnCreate = false)
    {
        $isObjectNew = $this->isObjectNew();

        if (!is_null($this->childMode) && is_null($this->getData('component_mode'))) {
            $this->setData('component_mode',$this->childMode);
        }

        if ($reloadOnCreate) {
            $this->setData('reload_on_create', true);
        }

        $temp = parent::save();

        if (is_null($this->childObject)) {
            return $temp;
        }

        // The Child object is already saved in Resource Model on _afterSave()
        if (!$isObjectNew) {
            $this->getChildObject()->save();
        }

        return $temp;
    }

    public function delete()
    {
        if (is_null($this->getId())) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Method require loaded instance first');
        }

        if ($this->isLocked()) {
            return false;
        }

        $this->deleteChildInstance();
        $temp = parent::delete();

        $this->childMode = NULL;
        $this->childObject = NULL;

        return $temp;
    }

    protected function deleteChildInstance()
    {
        if (is_null($this->getId())) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Method require loaded instance first');
        }

        $childObject = $this->getChildObject();

        if (is_null($childObject) || !($childObject instanceof \Ess\M2ePro\Model\ActiveRecord\AbstractModel)) {
            return;
        }

        $childObject->delete();
    }

    //########################################

    /**
     * @param string $modelName
     * @param string $fieldName
     * @param bool $asObjects
     * @param array $filters
     * @param array $sort
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function getRelatedComponentItems($modelName, $fieldName, $asObjects = false,
                                                array $filters = array(), array $sort = array())
    {
        if (is_null($this->getId())) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Method require loaded instance first');
        }

        $tempMode = NULL;

        if (!is_null($this->childMode)) {
            $tempMode = $this->childMode;
        } else {
            $tempMode = $this->getComponentMode();
        }

        $tempModel = $this->parentFactory->getObject($tempMode,$modelName);

        if (is_null($tempModel) || !($tempModel instanceof \Ess\M2ePro\Model\ActiveRecord\AbstractModel)) {
            return array();
        }

        return $this->getRelatedItems($tempModel,$fieldName,$asObjects,$filters,$sort);
    }

    //########################################

    public function getResourceCollection()
    {
        if (empty($this->_resourceCollection) && empty($this->_collectionName)) {
            throw new \Magento\Framework\Exception\LocalizedException(
                new \Magento\Framework\Phrase('Model collection resource name is not defined.')
            );
        }

        $resourceCollection = $this->_resourceCollection ? clone $this
            ->_resourceCollection : \Magento\Framework\App\ObjectManager::getInstance()
            ->create(
                $this->_collectionName,
                ['childMode' => $this->childMode]
            );

        return $resourceCollection;
    }

    //########################################
}