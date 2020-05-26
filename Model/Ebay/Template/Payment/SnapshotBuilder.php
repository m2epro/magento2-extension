<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Template\Payment;

/**
 * Class \Ess\M2ePro\Model\Ebay\Template\Payment\SnapshotBuilder
 */
class SnapshotBuilder extends \Ess\M2ePro\Model\Template\SnapshotBuilder\AbstractModel
{
    //########################################

    public function getSnapshot()
    {
        $data = $this->model->getData();
        if (empty($data)) {
            return [];
        }

        $data['services'] = $this->model->getServices();

        $ignoredKeys = [
            'id', 'template_payment_id',
        ];

        foreach ($data['services'] as &$serviceData) {
            foreach ($serviceData as $key => &$value) {
                if (in_array($key, $ignoredKeys)) {
                    unset($serviceData[$key]);
                    continue;
                }

                $value !== null && !is_array($value) && $value = (string)$value;
            }
        }

        return $data;
    }

    //########################################
}
