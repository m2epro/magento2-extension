<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Walmart\Template;

class Description extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Component\Child\AbstractModel
{
    const SYNCH_REASON = 'descriptionTemplate';

    protected $_isPkAutoIncrement = false;

    //########################################

    public function _construct()
    {
        $this->_init('m2epro_walmart_template_description', 'template_description_id');
        $this->_isPkAutoIncrement = false;
    }

    //########################################

    public function setSynchStatusNeed($newData, $oldData, $listingsProducts)
    {
        if (empty($listingsProducts)) {
            return;
        }

        $listingsProductsIds = array();
        foreach ($listingsProducts as $listingProduct) {
            $listingsProductsIds[] = $listingProduct['id'];
        }

        if (!$this->isDifferent($newData,$oldData)) {
            return;
        }

        $templates = array(self::SYNCH_REASON);

        $lpTable = $this->activeRecordFactory->getObject('Listing\Product')->getResource()->getMainTable();

        $this->getConnection()->update(
            $lpTable,
            array(
                'synch_status' => \Ess\M2ePro\Model\Listing\Product::SYNCH_STATUS_NEED,
                'synch_reasons' => new \Zend_Db_Expr(
                    "IF(synch_reasons IS NULL,
                        '".implode(',',$templates)."',
                        CONCAT(synch_reasons,'".','.implode(',',$templates)."')
                    )"
                )
            ),
            array('id IN ('.implode(',', $listingsProductsIds).')')
        );
    }

    // ---------------------------------------

    public function isDifferent($newData, $oldData)
    {
        $ignoreFields = array(
            $this->getIdFieldName(),
            'id', 'title', 'component_mode',
            'create_date', 'update_date',
        );

        foreach ($ignoreFields as $ignoreField) {
            unset($newData[$ignoreField],$oldData[$ignoreField]);
        }

        $keyFeatures    = isset($newData['key_features']) ? $newData['key_features'] : array();
        $keyFeaturesOld = isset($oldData['key_features']) ? $oldData['key_features'] : array();
        unset($newData['key_features'], $oldData['key_features']);

        $otherFeatures    = isset($newData['other_features']) ? $newData['other_features'] : array();
        $otherFeaturesOld = isset($oldData['other_features']) ? $oldData['other_features'] : array();
        unset($newData['other_features'], $oldData['other_features']);

        $attributes    = isset($newData['attributes']) ? $newData['attributes'] : array();
        $attributesOld = isset($oldData['attributes']) ? $oldData['attributes'] : array();
        unset($newData['attributes'], $oldData['attributes']);

        if (count(array_diff_assoc($newData, $oldData))) {
            return true;
        }

        if (count(array_diff_assoc($keyFeatures, $keyFeaturesOld))) {
            return true;
        }

        if (count(array_diff_assoc($otherFeatures, $otherFeaturesOld))) {
            return true;
        }

        $encodedAttributes = $this->getHelper('Data')->jsonEncode($attributes);
        $encodedAttributesOld = $this->getHelper('Data')->jsonEncode($attributesOld);

        return md5($encodedAttributes) !== md5($encodedAttributesOld);
    }

    //########################################
}