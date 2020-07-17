<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ActiveRecord;

/**
 * Class \Ess\M2ePro\Model\ActiveRecord\SnapshotBuilder
 */
class SnapshotBuilder extends \Ess\M2ePro\Model\AbstractModel
{
    /** @var ActiveRecordAbstract */
    protected $model;

    //########################################

    /**
     * @param $model ActiveRecordAbstract|AbstractModel
     * @return $this
     */
    public function setModel($model)
    {
        $this->model = $model;
        return $this;
    }

    public function getModel()
    {
        return $this->model;
    }

    //########################################

    public function getSnapshot()
    {
        $data = $this->getModel()->getData();

        if (($this->getModel() instanceof \Ess\M2ePro\Model\ActiveRecord\Relation\ParentAbstract ||
             $this->getModel() instanceof \Ess\M2ePro\Model\ActiveRecord\Component\Parent\AbstractModel) &&
            $this->getModel()->getChildObject() !== null
        ) {
            $data = array_merge($data, $this->getModel()->getChildObject()->getData());
        }

        foreach ($data as &$value) {
            (null !== $value && !is_array($value)) && $value = (string)$value;
        }

        return $data;
    }

    //########################################

    protected function sanitizeData(array &$snapshot)
    {
        unset($snapshot['id'], $snapshot['create_date'], $snapshot['update_date']);
    }

    //########################################
}
