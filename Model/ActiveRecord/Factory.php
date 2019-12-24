<?php

/**
 * Model factory
 */
namespace Ess\M2ePro\Model\ActiveRecord;

/**
 * Class \Ess\M2ePro\Model\ActiveRecord\Factory
 */
class Factory
{
    protected $helperFactory;
    protected $objectManager;

    //########################################

    /**
     * Construct
     *
     * @param \Ess\M2ePro\Helper\Factory $helperFactory
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     */
    public function __construct(
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        $this->helperFactory = $helperFactory;
        $this->objectManager = $objectManager;
    }

    //########################################

    /**
     * @param string $modelName
     * @return \Ess\M2ePro\Model\ActiveRecord\AbstractModel
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getObject($modelName)
    {
        // fix for Magento2 sniffs that forcing to use ::class
        $modelName = str_replace('_', '\\', $modelName);

        $model = $this->objectManager->create('\Ess\M2ePro\Model\\'.$modelName);

        if (!$model instanceof \Ess\M2ePro\Model\ActiveRecord\AbstractModel) {
            throw new \Ess\M2ePro\Model\Exception\Logic(
                __('%1 doesn\'t extends \Ess\M2ePro\Model\ActiveRecord\AbstractModel', $modelName)
            );
        }

        return $model;
    }

    /**
     * @param string $modelName
     * @param mixed $value
     * @param null|string $field
     * @param boolean $throwException
     * @return \Ess\M2ePro\Model\ActiveRecord\AbstractModel|NULL
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getObjectLoaded($modelName, $value, $field = null, $throwException = true)
    {
        if ($throwException) {
            return $this->getObject($modelName)->load($value, $field);
        }

        try {
            return $this->getObject($modelName)->load($value, $field);
        } catch (\Ess\M2ePro\Model\Exception\Logic $e) {
            return null;
        }
    }

    /**
     * @param mixed $modelName
     * @param mixed $value
     * @param null|string $field
     * @param boolean $throwException
     * @return \Ess\M2ePro\Model\ActiveRecord\AbstractModel
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getCachedObjectLoaded($modelName, $value, $field = null, $throwException = true)
    {
        if ($this->helperFactory->getObject('Module')->isDevelopmentEnvironment()) {
            return $this->getObjectLoaded($modelName, $value, $field);
        }

        $model = $this->getObject($modelName);

        if (!$model->isCacheEnabled()) {
            throw new \Ess\M2ePro\Model\Exception\Logic(
                __('%1 can\'t be cached', $modelName)
            );
        }

        $model->setCacheLoading(true);

        $cacheKey = strtoupper($modelName.'_data_'.$field.'_'.$value);

        $cacheData = $this->helperFactory->getObject('Data_Cache_Permanent')->getValue($cacheKey);
        if (!empty($cacheData) && is_array($cacheData)) {
            $model->setData($cacheData);
            $model->setOrigData();

            return $model;
        }

        if ($throwException) {
            $model->load($value, $field);
        } else {
            try {
                $model->load($value, $field);
            } catch (\Ess\M2ePro\Model\Exception\Logic $e) {
                return null;
            }
        }

        $tags = $model->getCacheGroupTags();
        $tags[] = $model->getCacheInstancesTag();

        $this->helperFactory->getObject('Data_Cache_Permanent')->setValue(
            $cacheKey,
            $model->getData(),
            $tags,
            $model->getCacheLifetime()
        );

        return $model;
    }

    //########################################
}
