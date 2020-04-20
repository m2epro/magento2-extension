<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Account\Repricing;

/**
 * Class \Ess\M2ePro\Model\Amazon\Account\Repricing\SnapshotBuilder
 */
class SnapshotBuilder extends \Ess\M2ePro\Model\Template\SnapshotBuilder\AbstractModel
{
    //########################################

    public function getSnapshot()
    {
        $data = $this->model->getData();

        foreach ($data as &$value) {
            if ($value !== null && !is_array($value)) {
                $value = (string)$value;
            }
        }

        return $data;
    }

    //########################################
}
