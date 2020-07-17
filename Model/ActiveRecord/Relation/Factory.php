<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ActiveRecord\Relation;

/**
 * Class \Ess\M2ePro\Model\ActiveRecord\Relation\Factory
 */
class Factory
{
    /** @var \Ess\M2ePro\Helper\Factory */
    protected $helperFactory;

    /** @var \Ess\M2ePro\Model\ActiveRecord\Factory */
    protected $activeRecordFactory;

    /** @var \Magento\Framework\ObjectManagerInterface */
    protected $objectManager;

    //########################################

    public function __construct(
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        $this->helperFactory = $helperFactory;
        $this->activeRecordFactory = $activeRecordFactory;
        $this->objectManager = $objectManager;
    }

    //########################################

    /**
     * @param $component
     * @param $parentModelName
     * @return false|\Ess\M2ePro\Model\ActiveRecord\Relation
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getObject($component, $parentModelName)
    {
        if (!in_array($component, $this->helperFactory->getObject('Component')->getComponents(), true)) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Unknown component nick ' . $component);
        }

        $parentModel = $this->activeRecordFactory->getObject($parentModelName);
        $parentModel->setData('component_mode', $component);

        return $this->objectManager->create(
            \Ess\M2ePro\Model\ActiveRecord\Relation::class,
            [
                'parentObject' => $parentModel,
                'childObject'  => $this->activeRecordFactory->getObject(ucfirst($component) .'\\'. $parentModelName)
            ]
        );
    }

    /**
     * @param $component
     * @param $modelName
     * @return \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Relation\Collection
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getObjectCollection($component, $modelName)
    {
        return $this->getObject($component, $modelName)->getCollection();
    }

    /**
     * @param $component
     * @param $modelName
     * @param $value
     * @param null $field
     * @param bool $throwException
     * @return \Ess\M2ePro\Model\ActiveRecord\Relation|null
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getObjectLoaded($component, $modelName, $value, $field = null, $throwException = true)
    {
        try {
            return $this->getObject($component, $modelName)->load($value, $field);
        } catch (\Ess\M2ePro\Model\Exception\Logic $e) {
            if ($throwException) {
                throw $e;
            }

            return null;
        }
    }

    /**
     * @param $component
     * @param $modelName
     * @param $value
     * @param null $field
     * @param bool $throwException
     * @return \Ess\M2ePro\Model\ActiveRecord\Relation|null
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getCachedObjectLoaded($component, $modelName, $value, $field = null, $throwException = true)
    {
        if ($this->helperFactory->getObject('Module')->isDevelopmentEnvironment()) {
            return $this->getObjectLoaded($modelName, $value, $field, $throwException);
        }

        $parentModel = $this->activeRecordFactory->getCachedObjectLoaded(
            $modelName,
            $value,
            $field,
            $throwException
        );

        $childModel = $this->activeRecordFactory->getCachedObjectLoaded(
            ucfirst($component) . '_' . $modelName,
            $parentModel->getId(),
            null,
            $throwException
        );

        return $this->objectManager->create(
            \Ess\M2ePro\Model\ActiveRecord\Relation::class,
            [
                'parentObject' => $parentModel,
                'childObject'  => $childModel
            ]
        );
    }

    //########################################

    /**
     * @param \Ess\M2ePro\Model\ActiveRecord\Relation\ParentAbstract $parent
     * @return \Ess\M2ePro\Model\ActiveRecord\Relation
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getByParent(\Ess\M2ePro\Model\ActiveRecord\Relation\ParentAbstract $parent)
    {
        if (null === $parent->getComponentMode()) {
            throw new \Ess\M2ePro\Model\Exception\Logic(
                'Relation object require `component_mode` from ' . $parent->getObjectModelName()
            );
        }

        /** @var \Ess\M2ePro\Model\ActiveRecord\Relation\ChildAbstract $child */
        $child = $this->activeRecordFactory->getObject(
            ucfirst($parent->getComponentMode()) .'\\'. $parent->getObjectModelName()
        );

        if (null !== $parent->getId()) {
            $child->load($parent->getId());
        }

        return $this->objectManager->create(
            \Ess\M2ePro\Model\ActiveRecord\Relation::class,
            [
                'parentObject' => $parent,
                'childObject'  => $child
            ]
        );
    }

    /**
     * @param \Ess\M2ePro\Model\ActiveRecord\Relation\ChildAbstract $child
     * @return \Ess\M2ePro\Model\ActiveRecord\Relation
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getByChild(\Ess\M2ePro\Model\ActiveRecord\Relation\ChildAbstract $child)
    {
        if (null === $child->getComponentMode()) {
            throw new \Ess\M2ePro\Model\Exception\Logic(
                'Relation object require `component_mode` from ' . $child->getObjectModelName()
            );
        }


        /** @var \Ess\M2ePro\Model\ActiveRecord\Relation\ParentAbstract $parent */
        $parent = $this->activeRecordFactory->getObject(
            str_replace($child->getComponentMode(), '', $child->getObjectModelName())
        );

        if (null === $child->getId()) {
            $parent->load($child->getId());
        }

        return $this->objectManager->create(
            \Ess\M2ePro\Model\ActiveRecord\Relation::class,
            [
                'parentObject' => $parent,
                'childObject'  => $child
            ]
        );
    }

    //########################################
}
