<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Template\Shipping;

/**
 * Class \Ess\M2ePro\Model\Ebay\Template\Shipping\SnapshotBuilder
 * @method \Ess\M2ePro\Model\Ebay\Template\Shipping getModel()
 */
class SnapshotBuilder extends \Ess\M2ePro\Model\ActiveRecord\SnapshotBuilder
{
    //########################################

    public function getSnapshot()
    {
        $data = $this->getModel()->getData();
        if (empty($data)) {
            return [];
        }

        $data['services'] = $this->getModel()->getServices();
        $data['calculated_shipping'] = $this->getModel()->getCalculatedShipping()
            ? $this->getModel()->getCalculatedShipping()->getData()
            : [];

        $ignoredKeys = [
            'id',
            'template_shipping_id',
        ];

        foreach ($data['services'] as &$serviceData) {
            foreach ($serviceData as $key => &$value) {
                if (in_array($key, $ignoredKeys)) {
                    unset($serviceData[$key]);
                    continue;
                }

                $value !== null && !is_array($value) && $value = (string)$value;
            }
            unset($value);
        }
        unset($serviceData);

        foreach ($data['calculated_shipping'] as $key => &$value) {
            if (in_array($key, $ignoredKeys)) {
                unset($data['calculated_shipping'][$key]);
                continue;
            }

            $value !== null && !is_array($value) && $value = (string)$value;
        }
        unset($value);

        return $data;
    }

    //########################################
}
