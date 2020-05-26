<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Template\Synchronization;

/**
 * Class \Ess\M2ePro\Model\Walmart\Template\Synchronization\SnapshotBuilder
 */
class SnapshotBuilder extends \Ess\M2ePro\Model\Template\SnapshotBuilder\AbstractModel
{
    //########################################

    public function getSnapshot()
    {
        $data = $this->model->getData();

        if ($this->model->getChildObject() !== null) {
            $data = array_merge($data, $this->model->getChildObject()->getData());
        }

        foreach ($data as &$value) {
            $value !== null && !is_array($value) && $value = (string)$value;
        }

        return $data;
    }

    //########################################
}
