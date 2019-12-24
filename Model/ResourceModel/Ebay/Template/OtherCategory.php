<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Ebay\Template;

/**
 * Class \Ess\M2ePro\Model\ResourceModel\Ebay\Template\OtherCategory
 */
class OtherCategory extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\AbstractModel
{
    //########################################

    public function _construct()
    {
        $this->_init('m2epro_ebay_template_other_category', 'id');
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

        $templates = ['otherCategoryTemplate'];

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
            'title', 'create_date', 'update_date'
        ];

        $dataConversions = [
            ['field' => 'store_category_main_id', 'type' => 'float'],
            ['field' => 'store_category_secondary_id', 'type' => 'float'],
        ];

        foreach ($dataConversions as $data) {
            $type = $data['type'] . 'val';

            array_key_exists($data['field'], $newData) && $newData[$data['field']] = $type($newData[$data['field']]);
            array_key_exists($data['field'], $oldData) && $oldData[$data['field']] = $type($oldData[$data['field']]);
        }

        foreach ($ignoreFields as $ignoreField) {
            unset($newData[$ignoreField], $oldData[$ignoreField]);
        }

        ksort($newData);
        ksort($oldData);

        $encodedNewData = $this->getHelper('Data')->jsonEncode($newData);
        $encodedOldData = $this->getHelper('Data')->jsonEncode($oldData);

        return sha1($encodedNewData) !== sha1($encodedOldData);
    }

    //########################################
}
