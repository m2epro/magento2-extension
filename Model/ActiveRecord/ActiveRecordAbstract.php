<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ActiveRecord;

/**
 * Class \Ess\M2ePro\Model\ActiveRecord\ActiveRecordAbstract
 */
abstract class ActiveRecordAbstract extends \Magento\Framework\Model\AbstractModel
{
    //########################################

    /** @var \Ess\M2ePro\Model\Factory */
    protected $modelFactory;

    /** @var \Ess\M2ePro\Helper\Factory */
    protected $helperFactory;

    /** @var \Ess\M2ePro\Model\ActiveRecord\Factory */
    protected $activeRecordFactory;

    /** @var \Ess\M2ePro\Model\ActiveRecord\Serializer */
    protected $serializer;

    /** @var \Ess\M2ePro\Model\ActiveRecord\LockManager */
    protected $lockManager;

    protected $isCacheEnabled = false;
    protected $cacheLifetime  = 86400;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\ActiveRecord\Serializer $serializer,
        \Ess\M2ePro\Model\ActiveRecord\LockManager $lockManager,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->modelFactory = $modelFactory;
        $this->activeRecordFactory = $activeRecordFactory;
        $this->helperFactory = $helperFactory;
        $this->serializer = $serializer;
        $this->lockManager = $lockManager;

        parent::__construct(
            $context,
            $registry,
            $resource,
            $resourceCollection,
            $data
        );
    }

    //########################################

    public function getObjectModelName()
    {
        $className = $this->getHelper('Client')->getClassName($this);
        return str_replace(['Ess\M2ePro\Model\\','\\'], ['','_'], $className);
    }

    //########################################

    /**
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function isLocked()
    {
        return $this->getLockManager()->isSetProcessingLock();
    }

    /**
     * @param mixed $tag
     * @return $this
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function lock($tag = null)
    {
        !is_array($tag) && $tag = [$tag];
        foreach ($tag as $value) {
            $this->getLockManager()->addProcessingLock($value);
        }

        return $this;
    }

    /**
     * @param mixed $tag
     * @return $this
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function unlock($tag = false)
    {
        !is_array($tag) && $tag = [$tag];
        foreach ($tag as $value) {
            $this->getLockManager()->deleteProcessingLocks($value);
        }

        return $this;
    }

    //########################################

    /**
     * @param int $id
     * @param null|string $field
     * @return ActiveRecordAbstract
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function load($id, $field = null)
    {
        parent::load($id, $field);

        if ($this->getId() === null) {
            throw new \Ess\M2ePro\Model\Exception\Logic(
                'Instance does not exist.',
                [
                    'id'    => $id,
                    'field' => $field,
                    'model' => $this->_resourceName
                ]
            );
        }

        return $this;
    }

    /**
     * @return ActiveRecordAbstract
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function delete()
    {
        if ($this->getId() === null) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Method require loaded instance first');
        }

        if ($this->isLocked()) {
            return $this;
        }

        parent::delete();
        return $this;
    }

    //########################################

    //todo active record
    public function deleteProcessings()
    {
        $processingIds = [];
        foreach ($this->getProcessingLocks() as $processingLock) {
            $processingIds[] = $processingLock->getProcessingId();
        }

        /** @var $collection \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Collection\AbstractModel */
        $collection = $this->activeRecordFactory->getObject('Processing')->getCollection();
        $collection->addFieldToFilter('id', ['in'=>array_unique($processingIds)]);

        foreach ($collection->getItems() as $processing) {
            /** @var $processing \Ess\M2ePro\Model\Processing */

            /** @var \Ess\M2ePro\Model\Processing\Runner $processingRunner */
            $processingRunner = $this->modelFactory->getObject($processing->getModel());
            $processingRunner->setProcessingObject($processing);

            $processingRunner->complete();
        }
    }

    //########################################

    /**
     * @deprecated use $this->getLockManager()->addProcessingLock()
     */
    public function addProcessingLock($tag = null, $processingId = null)
    {
        return $this->getLockManager()->addProcessingLock($tag, $processingId);
    }

    /**
     * @deprecated use $this->getLockManager()->deleteProcessingLocks()
     */
    public function deleteProcessingLocks($tag = false, $processingId = false)
    {
        return $this->getLockManager()->deleteProcessingLocks($tag, $processingId);
    }

    /**
     * @deprecated use $this->getLockManager()->isSetProcessingLock()
     */
    public function isSetProcessingLock($tag = false, $processingId = false)
    {
        return $this->getLockManager()->isSetProcessingLock($tag, $processingId);
    }

    /**
     * @deprecated use $this->getLockManager()->getProcessingLocks()
     */
    public function getProcessingLocks($tag = false, $processingId = false)
    {
        return $this->getLockManager()->getProcessingLocks($tag, $processingId);
    }

    //########################################

    /**
     * @deprecated use $this->getSerializer()->getSettings()
     */
    public function getSettings(
        $fieldName,
        $encodeType = Serializer::SETTING_FIELD_TYPE_JSON
    ) {
        return $this->getSerializer()->getSettings($fieldName, $encodeType);
    }

    /**
     * @deprecated use $this->getSerializer()->getSetting()
     */
    public function getSetting(
        $fieldName,
        $settingNamePath,
        $defaultValue = null,
        $encodeType = Serializer::SETTING_FIELD_TYPE_JSON
    ) {
       return $this->getSerializer()->getSetting($fieldName, $settingNamePath, $defaultValue, $encodeType);
    }

    /**
     * @deprecated use $this->getSerializer()->setSettings()
     */
    public function setSettings(
        $fieldName,
        array $settings = [],
        $encodeType = Serializer::SETTING_FIELD_TYPE_JSON
    ) {
        $this->getSerializer()->setSettings($fieldName, $settings, $encodeType);
        return $this;
    }

    /**
     * @deprecated use $this->getSerializer()->setSetting()
     */
    public function setSetting(
        $fieldName,
        $settingNamePath,
        $settingValue,
        $encodeType = Serializer::SETTING_FIELD_TYPE_JSON
    ) {
        $this->getSerializer()->setSetting($fieldName, $settingNamePath, $settingValue, $encodeType);
        return $this;
    }

    //########################################

    public function afterSave()
    {
        if (null !== $this->getId() && $this->isCacheEnabled()) {
            $this->getHelper('Data_Cache_Permanent')->removeTagValues($this->getMainCacheTag());
        }

        return parent::afterSave();
    }

    public function beforeDelete()
    {
        if (null !== $this->getId()) {
            if ($this->isCacheEnabled()) {
                $this->getHelper('Data_Cache_Permanent')->removeTagValues($this->getMainCacheTag());
            }

            $this->unlock();
        }

        return parent::beforeDelete();
    }

    //########################################

    /**
     * @return Serializer
     */
    public function getSerializer()
    {
        return $this->serializer->setModel($this);
    }

    /**
     * @return LockManager
     */
    public function getLockManager()
    {
        return $this->lockManager->setModel($this);
    }

    /**
     * @param $helperName
     * @param array $arguments
     * @return \Magento\Framework\App\Helper\AbstractHelper
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function getHelper($helperName, array $arguments = [])
    {
        return $this->helperFactory->getObject($helperName, $arguments);
    }

    //########################################

    public function isCacheEnabled()
    {
        return $this->isCacheEnabled;
    }

    public function getCacheLifetime()
    {
        return $this->cacheLifetime;
    }

    public function getMainCacheTag()
    {
        if ($this->getId() === null) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Method require loaded instance first');
        }

        return strtolower($this->getResourceName() . '_' . $this->getId());
    }

    public function getInstanceCacheTags()
    {
        $modelName = str_replace('Ess\M2ePro\Model\\', '', $this->getHelper('Client')->getClassName($this));

        $tags = [$modelName];
        if (strpos($modelName, '\\') !== false) {
            $allComponents = $this->getHelper('Component')->getComponents();
            $modelNameComponent = substr($modelName, 0, strpos($modelName, '\\'));

            if (in_array(strtolower($modelNameComponent), array_map('strtolower', $allComponents))) {
                $modelNameOnlyModel = substr($modelName, strpos($modelName, '\\')+1);
                $tags[] = $modelNameComponent;
                $tags[] = $modelNameOnlyModel;
            }
        }

        $tags = array_unique($tags);
        $tags = array_map('strtolower', $tags);

        return array_unique($tags);
    }

    //########################################
}
