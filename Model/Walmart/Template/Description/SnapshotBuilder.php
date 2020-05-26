<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Template\Description;

/**
 * Class \Ess\M2ePro\Model\Walmart\Template\Description\SnapshotBuilder
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

        if (empty($data)) {
            return [];
        }

        return $data;
    }

    //########################################
}
