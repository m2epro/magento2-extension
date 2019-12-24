<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Ebay\Template;

/**
 * Class \Ess\M2ePro\Model\ResourceModel\Ebay\Template\Shipping
 */
class Shipping extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\AbstractModel
{
    //########################################

    public function _construct()
    {
        $this->_init('m2epro_ebay_template_shipping', 'id');
    }

    //########################################

    public function setSynchStatusNeed($newData, $oldData, $listingsProducts)
    {
        $listingsProductsIds = [];
        foreach ($listingsProducts as $listingProduct) {
            $listingsProductsIds[] = (int)$listingProduct['id'];
        }

        if (empty($listingsProductsIds)) {
            return;
        }

        if (!$this->isDifferent($newData, $oldData)) {
            return;
        }

        $templates = ['shippingTemplate'];

        $lpTable = $this->activeRecordFactory->getObject('Listing\Product')->getResource()->getMainTable();

        $this->getConnection()->update(
            $lpTable,
            [
                'synch_status' => \Ess\M2ePro\Model\Listing\Product::SYNCH_STATUS_NEED,
                'synch_reasons' => new \Zend_Db_Expr(
                    "IF(synch_reasons IS NULL,
                        '".implode(',', $templates)."',
                        CONCAT(synch_reasons,'".','.implode(',', $templates)."')
                    )"
                )
            ],
            ['id IN ('.implode(',', $listingsProductsIds).')']
        );
    }

    // ---------------------------------------

    public function isDifferent($newData, $oldData)
    {
        $ignoreFields = [
            $this->getIdFieldName(),
            'title', 'is_custom_template',
            'create_date', 'update_date'
        ];

        foreach ($ignoreFields as $ignoreField) {
            unset($newData[$ignoreField], $oldData[$ignoreField]);
        }

        !isset($newData['services']) && $newData['services'] = [];
        !isset($oldData['services']) && $oldData['services'] = [];

        foreach ($newData['services'] as $key => $newService) {
            unset($newData['services'][$key]['id'], $newData['services'][$key]['template_shipping_id']);
        }
        foreach ($oldData['services'] as $key => $oldService) {
            unset($oldData['services'][$key]['id'], $oldData['services'][$key]['template_shipping_id']);
        }

        !isset($newData['calculated_shipping']) && $newData['calculated_shipping'] = [];
        !isset($oldData['calculated_shipping']) && $oldData['calculated_shipping'] = [];

        unset(
            $newData['calculated_shipping']['template_shipping_id'],
            $oldData['calculated_shipping']['template_shipping_id']
        );

        $dataConversions = [
            ['field' => 'vat_percent', 'type' => 'float'],
            ['field' => 'local_shipping_discount_profile_id', 'type' => 'str'],
            ['field' => 'international_shipping_discount_profile_id', 'type' => 'str'],
        ];

        foreach ($dataConversions as $data) {
            $type = $data['type'] . 'val';

            array_key_exists($data['field'], $newData) && $newData[$data['field']] = $type($newData[$data['field']]);
            array_key_exists($data['field'], $oldData) && $oldData[$data['field']] = $type($oldData[$data['field']]);
        }

        ksort($newData);
        ksort($oldData);
        ksort($newData['calculated_shipping']);
        ksort($oldData['calculated_shipping']);
        array_walk($newData['services'], 'ksort');
        array_walk($oldData['services'], 'ksort');

        $encodedNewData = $this->getHelper('Data')->jsonEncode($newData);
        $encodedOldData = $this->getHelper('Data')->jsonEncode($oldData);

        return sha1($encodedNewData) !== sha1($encodedOldData);
    }

    //########################################
}
