<?php

namespace Ess\M2ePro\Model\Walmart\ProductType\Builder;

/**
 * @method \Ess\M2ePro\Model\Walmart\ProductType getModel()
 */
class SnapshotBuilder extends \Ess\M2ePro\Model\ActiveRecord\SnapshotBuilder
{
    public function getSnapshot()
    {
        $productTypeModel = $this->getModel();
        $data = $productTypeModel->getData();
        if (empty($data)) {
            return [];
        }

        return $data;
    }
}
