<?php

namespace Ess\M2ePro\Model\ActiveRecord;

/**
 * Class \Ess\M2ePro\Model\ActiveRecord\Factory
 */
class Factory
{
    /** @var \Ess\M2ePro\Helper\Factory */
    protected $helperFactory;

    /** @var \Magento\Framework\ObjectManagerInterface */
    protected $objectManager;

    //########################################

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

        if (!$model instanceof \Ess\M2ePro\Model\ActiveRecord\AbstractModel &&
            !$model instanceof \Ess\M2ePro\Model\ActiveRecord\ActiveRecordAbstract
        ) {
            throw new \Ess\M2ePro\Model\Exception\Logic(
                __('%1 doesn\'t extends \Ess\M2ePro\Model\ActiveRecord\ActiveRecordAbstract', $modelName)
            );
        }

        return $model;
    }

    /**
     * @param string $modelName
     *
     * @return  \Ess\M2ePro\Model\ResourceModel\ActiveRecord\AbstractModel
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getObjectCollection($modelName)
    {
        return $this->getObject($modelName)->getCollection();
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
        try {
            return $this->getObject($modelName)->load($value, $field);
        } catch (\Ess\M2ePro\Model\Exception\Logic $e) {
            if ($throwException) {
                throw $e;
            }

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
            return $this->getObjectLoaded($modelName, $value, $field, $throwException);
        }

        $model = $this->getObject($modelName);

        if (!$model->isCacheEnabled()) {
            throw new \Ess\M2ePro\Model\Exception\Logic(
                __('%1 can\'t be cached', $modelName)
            );
        }

        $cacheKey = strtoupper($modelName.'_data_'.$field.'_'.$value);

        $cacheData = $this->helperFactory->getObject('Data_Cache_Permanent')->getValue($cacheKey);
        if (!empty($cacheData) && is_array($cacheData)) {
            $model->setData($cacheData);
            $model->setOrigData();

            return $model;
        }

        try {
            $model->load($value, $field);
        } catch (\Ess\M2ePro\Model\Exception\Logic $e) {
            if ($throwException) {
                throw $e;
            }

            return null;
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
