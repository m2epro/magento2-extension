<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ActiveRecord;

/**
 * Class \Ess\M2ePro\Model\ActiveRecord\AbstractBuilder
 */
abstract class AbstractBuilder extends \Ess\M2ePro\Model\AbstractModel
{
    /** @var ActiveRecordAbstract|AbstractModel */
    protected $model;

    /** @var array */
    protected $rawData;

    //########################################

    /**
     * @param ActiveRecordAbstract|AbstractModel $model
     * @param array $rawData
     *
     * @return ActiveRecordAbstract|AbstractModel
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function build($model, array $rawData)
    {
        if (empty($rawData)) {
            return $model;
        }

        $this->model   = $model;
        $this->rawData = $rawData;

        $preparedData = $this->prepareData();
        $this->model->addData($preparedData);

        if ($this->model instanceof \Ess\M2ePro\Model\ActiveRecord\Component\Parent\AbstractModel &&
            $this->model->hasChildObjectLoaded()
        ) {
            $this->model->getChildObject()->addData($preparedData);
        }

        $this->model->save();

        return $this->model;
    }

    //########################################

    /**
     * @return array
     */
    abstract protected function prepareData();

    /**
     * @return array
     */
    abstract public function getDefaultData();

    //########################################

    public function getModel()
    {
        return $this->model;
    }

    //########################################
}
