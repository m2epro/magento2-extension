<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Template\SellingFormat;

/**
 * Class \Ess\M2ePro\Model\Walmart\Template\SellingFormat\SnapshotBuilder
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

        /** @var \Ess\M2ePro\Model\Walmart\Template\SellingFormat $childModel */
        $childModel = $this->model->getChildObject();

        $ignoredKeys = [
            'id',
            'template_selling_format_id',
        ];

        // ---------------------------------------
        $data['shipping_overrides'] = $childModel->getShippingOverrides();

        if ($data['shipping_overrides'] !== null) {
            foreach ($data['shipping_overrides'] as &$shippingOverride) {
                foreach ($shippingOverride as $key => &$value) {
                    if (in_array($key, $ignoredKeys)) {
                        unset($shippingOverride[$key]);
                        continue;
                    }

                    $value !== null && !is_array($value) && $value = (string)$value;
                }

                unset($value);
            }

            unset($shippingOverride);
        }

        // ---------------------------------------

        // ---------------------------------------
        $data['promotions'] = $childModel->getPromotions();

        if ($data['promotions'] !== null) {
            foreach ($data['promotions'] as &$promotion) {
                foreach ($promotion as $key => &$value) {
                    if (in_array($key, $ignoredKeys)) {
                        unset($promotion[$key]);
                        continue;
                    }

                    $value !== null && !is_array($value) && $value = (string)$value;
                }

                unset($value);
            }

            unset($promotion);
        }

        // ---------------------------------------

        return $data;
    }

    //########################################
}
