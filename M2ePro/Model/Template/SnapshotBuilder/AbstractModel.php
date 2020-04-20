<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Template\SnapshotBuilder;

/**
 * Class \Ess\M2ePro\Model\Template\SnapshotBuilder\AbstractModel
 */
abstract class AbstractModel extends \Ess\M2ePro\Model\AbstractModel
{
    /** @var \Ess\M2ePro\Model\ActiveRecord\AbstractModel */
    protected $model = null;

    //########################################

    public function setModel(\Ess\M2ePro\Model\ActiveRecord\AbstractModel $model)
    {
        $this->model = $model;
        return $this;
    }

    //########################################

    abstract public function getSnapshot();

    //########################################
}
