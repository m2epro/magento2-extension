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
    /** @var \Ess\M2ePro\Model\ActiveRecord\Factory */
    protected $activeRecordFactory = null;
    /** @var \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory */
    protected $parentFactory = null;

    protected $params = [];

    //####################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    ) {
        $this->parentFactory = $parentFactory;
        $this->activeRecordFactory = $activeRecordFactory;
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

        $processingObject->setData(
            'model',
            str_replace('Ess\M2ePro\Model\\', '', $this->getHelper('Client')->getClassName($this))
        );

        $processingObject->setSettings('params', $this->getParams());

        $processingObject->setData('expiration_date', $this->helperFactory->getObject('Data')->getDate(
            $this->helperFactory->getObject('Data')->getCurrentGmtDate(true)+static::MAX_LIFETIME
        ));

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
