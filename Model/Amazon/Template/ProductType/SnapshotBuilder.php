<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Template\ProductType;

/**
 * @method \Ess\M2ePro\Model\Amazon\Template\ProductType getModel()
 */
class SnapshotBuilder extends \Ess\M2ePro\Model\ActiveRecord\SnapshotBuilder
{
    /**
     * @return array
     */
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
