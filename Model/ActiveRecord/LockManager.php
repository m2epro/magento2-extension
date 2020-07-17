<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ActiveRecord;

/**
 * Class \Ess\M2ePro\Model\ActiveRecord\LockManager
 */
class LockManager extends \Ess\M2ePro\Model\AbstractModel
{
    /** @var ActiveRecordAbstract */
    protected $model;

    /** @var \Ess\M2ePro\Model\ActiveRecord\Factory */
    protected $activeRecordFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        array $data = []
    ) {
        $this->activeRecordFactory = $activeRecordFactory;
        parent::__construct($helperFactory, $modelFactory, $data);
    }

    //########################################

    public function setModel(ActiveRecordAbstract $model)
    {
        $this->model = $model;
        return $this;
    }

    //########################################

    public function addProcessingLock($tag = null, $processingId = null)
    {
        if (null === $this->model) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Model was not set');
        }

        if (null === $this->model->getId()) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Method require loaded instance first');
        }

        if ($this->isSetProcessingLock($tag)) {
            return $this->model;
        }

        $model = $this->activeRecordFactory->getObject('Processing_Lock');
        $model->setData(
            [
                'processing_id' => $processingId,
                'model_name'    => $this->model->getResourceName(),
                'object_id'     => $this->model->getId(),
                'tag'           => $tag,
            ]
        );
        $model->save();

        return $this->model;
    }

    public function deleteProcessingLocks($tag = false, $processingId = false)
    {
        if (null === $this->model) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Model was not set');
        }

        if (null === $this->model->getId()) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Method require loaded instance first');
        }

        foreach ($this->getProcessingLocks($tag, $processingId) as $lock) {
            $lock->delete();
        }

        return $this->model;
    }

    public function isSetProcessingLock($tag = false, $processingId = false)
    {
        if (null === $this->model) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Model was not set');
        }

        if (null === $this->model->getId()) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Method require loaded instance first');
        }

        return count($this->getProcessingLocks($tag, $processingId)) > 0;
    }

    /**
     * @param bool|false $tag
     * @param bool|false $processingId
     *
     * @return \Ess\M2ePro\Model\Processing\Lock[]
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getProcessingLocks($tag = false, $processingId = false)
    {
        if (null === $this->model) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Model was not set');
        }

        if (null === $this->model->getId()) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Method require loaded instance first');
        }

        $collection = $this->activeRecordFactory->getObject('Processing_Lock')->getCollection();
        $collection->addFieldToFilter('model_name', $this->model->getResourceName());
        $collection->addFieldToFilter('object_id', $this->model->getId());

        $tag === null && $tag = ['null' => true];
        $tag !== false && $collection->addFieldToFilter('tag', $tag);
        $processingId !== false && $collection->addFieldToFilter('processing_id', $processingId);

        return $collection->getItems();
    }

    //########################################
}
