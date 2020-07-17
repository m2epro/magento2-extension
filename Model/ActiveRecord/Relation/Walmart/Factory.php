<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ActiveRecord\Relation\Walmart;

/**
 * Class \Ess\M2ePro\Model\ActiveRecord\Relation\Walmart\Factory
 */
class Factory implements \Ess\M2ePro\Model\ActiveRecord\Relation\FactoryInterface
{
    /** @var \Ess\M2ePro\Model\ActiveRecord\Relation\Factory  */
    protected $relationFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Relation\Factory $relationFactory
    ) {
        $this->relationFactory = $relationFactory;
    }

    //########################################

    protected function getComponentMode()
    {
        return \Ess\M2ePro\Helper\Component\Walmart::NICK;
    }

    //########################################

    /**
     * @param $modelName
     * @return false|\Ess\M2ePro\Model\ActiveRecord\Relation
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getObject($modelName)
    {
        return $this->relationFactory->getObject($this->getComponentMode(), $modelName);
    }

    /**
     * @param $modelName
     * @return \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Relation\Collection
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getObjectCollection($modelName)
    {
        return $this->relationFactory->getObjectCollection($this->getComponentMode(), $modelName);
    }

    /**
     * @param $modelName
     * @param $value
     * @param null $field
     * @param bool $throwException
     * @return \Ess\M2ePro\Model\ActiveRecord\Relation|null
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getObjectLoaded($modelName, $value, $field = null, $throwException = true)
    {
        return $this->relationFactory->getObjectLoaded(
            $this->getComponentMode(),
            $modelName,
            $value,
            $field,
            $throwException
        );
    }

    /**
     * @param $modelName
     * @param $value
     * @param null $field
     * @param bool $throwException
     * @return \Ess\M2ePro\Model\ActiveRecord\Relation|null
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getCachedObjectLoaded($modelName, $value, $field = null, $throwException = true)
    {
        return $this->relationFactory->getCachedObjectLoaded(
            $this->getComponentMode(),
            $modelName,
            $value,
            $field,
            $throwException
        );
    }

    //########################################
}
