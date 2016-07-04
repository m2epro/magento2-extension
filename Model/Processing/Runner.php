<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Processing;

abstract class Runner extends \Ess\M2ePro\Model\AbstractModel
{
    const MAX_LIFETIME = 86400;

    /** @var \Ess\M2ePro\Model\Processing $processingObject */
    protected $processingObject = NULL;
    /** @var \Ess\M2ePro\Model\ActiveRecord\Factory */
    protected $activeRecordFactory = NULL;
    /** @var \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory */
    protected $parentFactory = NULL;

    protected $params = [];

    //####################################

    function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    )
    {
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

    protected function eventBefore() {}

    protected function setLocks() {}

    protected function unsetLocks() {}

    protected function eventAfter() {}

    //####################################

    protected function buildProcessingObject()
    {
        $processingObject = $this->activeRecordFactory->getObject('Processing');

        $processingObject->setData('model', str_replace('Ess\M2ePro\Model\\', '', get_class($this)));
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