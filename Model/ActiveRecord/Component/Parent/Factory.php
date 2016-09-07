<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ActiveRecord\Component\Parent;

class Factory
{
    protected $helperFactory;
    protected $activeRecordFactory;

    //########################################

    /**
     * Construct
     *
     * @param \Ess\M2ePro\Helper\Factory $helperFactory
     * @param \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory
     */
    public function __construct(
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory
    )
    {
        $this->helperFactory = $helperFactory;
        $this->activeRecordFactory = $activeRecordFactory;
    }

    //########################################

    /**
     * @param string $component
     * @param string $modelName
     * @return \Ess\M2ePro\Model\ActiveRecord\Component\Parent\AbstractModel
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getObject($component, $modelName)
    {
        $model = $this->activeRecordFactory->getObject($modelName);

        if (!$model instanceof \Ess\M2ePro\Model\ActiveRecord\Component\Parent\AbstractModel) {
            throw new \Ess\M2ePro\Model\Exception\Logic(
                __('%1 doesn\'t extends \Ess\M2ePro\Model\ActiveRecord\Component\Parent\AbstractModel', $modelName)
            );
        }

        $model->setChildMode($component);

        return $model;
    }

    /**
     * @param string $component
     * @param string $modelName
     * @param mixed $value
     * @param null|string $field
     * @param boolean $throwException
     * @return \Ess\M2ePro\Model\ActiveRecord\Component\Parent\AbstractModel|NULL
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getObjectLoaded($component, $modelName, $value, $field = NULL, $throwException = true)
    {
        if ($throwException) {
            return $this->getObject($component, $modelName)->load($value, $field);
        }

        try {
            return $this->getObject($component, $modelName)->load($value, $field);
        } catch (\Ess\M2ePro\Model\Exception\Logic $e) {
            return NULL;
        }
    }

    /**
     * @param string $component
     * @param string $modelName
     * @param mixed $value
     * @param null|string $field
     * @param boolean $throwException
     * @return \Ess\M2ePro\Model\ActiveRecord\AbstractModel|NULL
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getCachedObjectLoaded($component, $modelName, $value, $field = NULL, $throwException = true)
    {
        if ($this->helperFactory->getObject('Module')->isDevelopmentEnvironment()) {
            return $this->getObjectLoaded($component, $modelName, $value, $field, $throwException);
        }

        $model = $this->getObject($component, $modelName);

        if (!$model->isCacheEnabled()) {
            throw new \Ess\M2ePro\Model\Exception\Logic(
                __('%1 can\'t be cached', $modelName)
            );
        }

        $model->setCacheLoading(true);

        $parentKey = strtoupper($modelName.'_data_'.$field.'_'.$value);

        /** @var \Ess\M2ePro\Model\ActiveRecord\Cache $cacheObj */
        $cacheObj = $this->helperFactory->getObject('Data\Cache\Permanent')->getValue($parentKey);

        if ($cacheObj !== NULL) {
            $model->setData($cacheObj->getData());
            $model->setOrigData();
            return $model;
        }

        if ($throwException) {
            $model->load($value, $field);
        } else {
            try {
                $model->load($value, $field);
            } catch (\Ess\M2ePro\Model\Exception\Logic $e) {
                return NULL;
            }
        }

        $cacheObj = new \Ess\M2ePro\Model\ActiveRecord\Cache($model->getData());

        $parentTags = $model->getCacheGroupTags();
        $parentTags[] = $model->getCacheInstancesTag();

        $this->helperFactory->getObject('Data\Cache\Permanent')->setValue(
            $parentKey,
            $cacheObj,
            $parentTags,
            $model->getCacheLifetime()
        );

        $cacheObj = new \Ess\M2ePro\Model\ActiveRecord\Cache($model->getChildObject()->getData());
        $childKey = strtoupper($component.'\\'.$modelName.'_data_'.$field.'_'.$value);

        $childTags = $model->getChildObject()->getCacheGroupTags();
        $childTags[] = $model->getChildObject()->getCacheInstancesTag();

        $this->helperFactory->getObject('Data\Cache\Permanent')->setValue(
            $childKey,
            $cacheObj,
            $childTags,
            $model->getChildObject()->getCacheLifetime()
        );

        return $model;
    }

    //########################################
}