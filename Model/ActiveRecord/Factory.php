<?php

/**
 * Model factory
 */
namespace Ess\M2ePro\Model\ActiveRecord;

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
    )
    {
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
    public function getObjectLoaded($modelName, $value, $field = NULL, $throwException = true)
    {
        if ($throwException) {
            return $this->getObject($modelName)->load($value, $field);
        }

        try {
            return $this->getObject($modelName)->load($value, $field);
        } catch (\Ess\M2ePro\Model\Exception\Logic $e) {
            return NULL;
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
    public function getCachedObjectLoaded($modelName, $value, $field = NULL, $throwException = true)
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

        /** @var \Ess\M2ePro\Model\ActiveRecord\Cache $cacheObj */
        $cacheObj = $this->helperFactory->getObject('Data\Cache\Permanent')->getValue($cacheKey);

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

        $tags = $model->getCacheGroupTags();
        $tags[] = $model->getCacheInstancesTag();

        $this->helperFactory->getObject('Data\Cache\Permanent')->setValue(
            $cacheKey,
            $cacheObj,
            $tags,
            $model->getCacheLifetime()
        );

        return $model;
    }

    //########################################
}
