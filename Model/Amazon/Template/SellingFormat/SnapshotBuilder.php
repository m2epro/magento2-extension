<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Template\SellingFormat;

/**
 * Class \Ess\M2ePro\Model\Amazon\Template\SellingFormat\SnapshotBuilder
 * @method \Ess\M2ePro\Model\Template\SellingFormat getModel()
 */
class SnapshotBuilder extends \Ess\M2ePro\Model\ActiveRecord\SnapshotBuilder
{
    //########################################

    public function getSnapshot()
    {
        $data = $this->getModel()->getData();

        if ($this->getModel()->getChildObject() !== null) {
            $data = array_merge($data, $this->getModel()->getChildObject()->getData());
        }

        if (empty($data)) {
            return [];
        }

        $data['business_discounts'] = $this->getModel()->getChildObject()->getBusinessDiscounts();

        foreach ($data['business_discounts'] as &$businessDiscount) {
            foreach ($businessDiscount as &$value) {
                $value !== null && !is_array($value) && $value = (string)$value;
            }
        }

        unset($value);

        return $data;
    }

    //########################################
}
