<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Processing;

/**
 * Class \Ess\M2ePro\Model\Processing\Runner
 */
abstract class Runner extends \Ess\M2ePro\Model\AbstractModel
{
    const MAX_LIFETIME = 86400;

    /** @var \Ess\M2ePro\Model\Processing $processingObject */
    protected $processingObject = null;

    protected $params = [];

    protected $parentFactory = null;
    protected $activeRecordFactory = null;

    /** @var \Ess\M2ePro\Helper\Data */
    protected $helperData;

    //####################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Data $helperData,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    ) {
        $this->parentFactory = $parentFactory;
        $this->activeRecordFactory = $activeRecordFactory;
        $this->helperData = $helperData;
        parent::__construct($helperFactory, $modelFactory);
    }

    public function setProcessingObject(\Ess\M2ePro\Model\Processing $processingObject)
    {
        $this->processingObject = $processingObject;
        $this->setParams($processingObject->getParams());

        return $this;
    }

    public function getProcessingObject()
    {
        return $this->processingObject;
    }

    public function setParams(array $params)
    {
        $this->params = $params;
        return $this;
    }

    public function getParams()
    {
        return $this->params;
    }

    abstract public function getType();

    //####################################

    public function start()
    {
        $this->setProcessingObject($this->buildProcessingObject());

        $this->eventBefore();
        $this->setLocks();
    }

    abstract public function processSuccess();

    abstract public function processExpired();

    public function complete()
    {
        $this->unsetLocks();
        $this->eventAfter();

        $this->getProcessingObject()->delete();
    }

    //####################################

    protected function eventBefore()
    {
        return null;
    }

    protected function setLocks()
    {
        return null;
    }

    protected function unsetLocks()
    {
        return null;
    }

    protected function eventAfter()
    {
        return null;
    }

    //####################################

    protected function buildProcessingObject()
    {
        $processingObject = $this->activeRecordFactory->getObject('Processing');

        $modelName = str_replace('Ess\M2ePro\Model\\', '', $this->getHelper('Client')->getClassName($this));

        $processingObject->setData('model', $modelName);
        $processingObject->setData('type', $this->getType());
        $processingObject->setSettings('params', $this->getParams());

        $processingObject->setData(
            'expiration_date',
            gmdate(
                'Y-m-d H:i:s',
                $this->helperData->getCurrentGmtDate(true) + static::MAX_LIFETIME
            )
        );

        $processingObject->save();

        return $processingObject;
    }

    //####################################

    protected function getExpiredErrorMessage()
    {
        return $this->helperFactory->getObject('Module\Translation')->__('Processing was expired.');
    }

    //####################################
}
