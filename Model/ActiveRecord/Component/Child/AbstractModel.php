<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ActiveRecord\Component\Child;

abstract class AbstractModel extends \Ess\M2ePro\Model\ActiveRecord\Component\AbstractModel
{
    /** @var \Ess\M2ePro\Model\ActiveRecord\Component\Parent\AbstractModel */
    protected $parentObject = NULL;

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

    abstract protected function getComponentMode();

    //########################################

    /**
     * @param \Ess\M2ePro\Model\ActiveRecord\Component\Parent\AbstractModel $object
     */
    public function setParentObject(\Ess\M2ePro\Model\ActiveRecord\Component\Parent\AbstractModel $object)
    {
        if (is_null($object->getId())) {
            return;
        }

        $this->parentObject = $object;
    }

    /**
     * @return \Ess\M2ePro\Model\ActiveRecord\Component\Parent\AbstractModel
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getParentObject()
    {
        if (is_null($this->getId())) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Method require loaded instance first');
        }

        if (!is_null($this->parentObject)) {
            return $this->parentObject;
        }

        $tempMode = $this->getComponentMode();

        if (is_null($tempMode)) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Set Component Mode first');
        }

        $modelName = str_replace('Ess\M2ePro\Model\ResourceModel\\'.ucwords($tempMode).'\\','',$this->_resourceName);

        if ($this->isCacheLoading()) {
            $this->parentObject = $this->activeRecordFactory->getCachedObjectLoaded($modelName, $this->getId());
        } else {
            $this->parentObject = $this->activeRecordFactory->getObjectLoaded($modelName, $this->getId());
        }

        $this->parentObject->setChildMode($tempMode);
        $this->parentObject->setChildObject($this);

        return $this->parentObject;
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

        $tempMode = $this->getComponentMode();

        if (is_null($tempMode)) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Set Component Mode first');
        }

        $tempModel = $this->parentFactory->getObject($tempMode,$modelName);

        if (is_null($tempModel) || !($tempModel instanceof \Ess\M2ePro\Model\ActiveRecord\AbstractModel)) {
            return array();
        }

        return $this->getRelatedItems($tempModel,$fieldName,$asObjects,$filters,$sort);
    }

    //########################################
}