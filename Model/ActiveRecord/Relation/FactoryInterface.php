<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ActiveRecord\Relation;

/**
 * Interface \Ess\M2ePro\Model\ActiveRecord\Relation\FactoryInterface
 */
interface FactoryInterface
{
    //########################################

    /**
     * @param $modelName
     * @return mixed
     */
    public function getObject($modelName);

    /**
     * @param $modelName
     * @return mixed
     */
    public function getObjectCollection($modelName);

    /**
     * @param $modelName
     * @param $value
     * @param null $field
     * @param bool $throwException
     * @return mixed
     */
    public function getObjectLoaded($modelName, $value, $field = null, $throwException = true);

    /**
     * @param $modelName
     * @param $value
     * @param null $field
     * @param bool $throwException
     * @return mixed
     */
    public function getCachedObjectLoaded($modelName, $value, $field = null, $throwException = true);

    //########################################
}
