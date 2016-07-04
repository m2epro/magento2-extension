<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ActiveRecord\Component\Parent;

abstract class AbstractFactory
{
    protected $parentFactory;

    //########################################

    /**
     * Construct
     *
     * @param \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory
     */
    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory
    )
    {
        $this->parentFactory = $parentFactory;
    }

    //########################################

    abstract protected function getComponentMode();

    //########################################

    /**
     * @param string $modelName
     * @return \Ess\M2ePro\Model\ActiveRecord\Component\Parent\AbstractModel
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getObject($modelName)
    {
        return $this->parentFactory->getObject($this->getComponentMode(), $modelName);
    }

    /**
     * @param string $modelName
     * @param mixed $value
     * @param null|string $field
     * @param boolean $throwException
     * @return \Ess\M2ePro\Model\ActiveRecord\Component\Parent\AbstractModel|NULL
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getObjectLoaded($modelName, $value, $field = NULL, $throwException = true)
    {
        return $this->parentFactory->getObjectLoaded(
            $this->getComponentMode(), $modelName, $value, $field, $throwException
        );
    }

    /**
     * @param string $modelName
     * @param mixed $value
     * @param null|string $field
     * @param boolean $throwException
     * @return \Ess\M2ePro\Model\ActiveRecord\AbstractModel
     */
    public function getCachedObjectLoaded($modelName, $value, $field = NULL, $throwException = true)
    {
        return $this->parentFactory->getCachedObjectLoaded(
            $this->getComponentMode(), $modelName, $value, $field, $throwException
        );
    }

    //########################################
}