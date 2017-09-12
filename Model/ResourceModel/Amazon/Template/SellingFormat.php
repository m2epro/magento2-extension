<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Amazon\Template;

class SellingFormat extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Component\Child\AbstractModel
{
    protected $_isPkAutoIncrement = false;

    //########################################

    public function _construct()
    {
        $this->_init('m2epro_amazon_template_selling_format', 'template_selling_format_id');
        $this->_isPkAutoIncrement = false;
    }

    //########################################

    public function setSynchStatusNeed($newData, $oldData, $listingsProducts)
    {
        $listingsProductsIds = array();
        foreach ($listingsProducts as $listingProduct) {
            $listingsProductsIds[] = (int)$listingProduct['id'];
        }

        if (empty($listingsProductsIds)) {
            return;
        }

        if (!$this->isDifferent($newData,$oldData)) {
            return;
        }

        $templates = array('sellingFormatTemplate');

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
            'id', 'title',
            'component_mode',
            'create_date', 'update_date'
        );

        foreach ($ignoreFields as $ignoreField) {
            unset($newData[$ignoreField],$oldData[$ignoreField]);
        }

        !isset($newData['business_price_qty_discounts']) && $newData['business_price_qty_discounts'] = array();
        !isset($oldData['business_price_qty_discounts']) && $oldData['business_price_qty_discounts'] = array();

        foreach ($newData['business_price_qty_discounts'] as $key => $newBusinessPriceQtyDiscount) {
            unset($newData['business_price_qty_discounts'][$key]['id'],
                  $newData['business_price_qty_discounts'][$key]['template_selling_format_id']);
        }
        foreach ($oldData['business_price_qty_discounts'] as $key => $oldBusinessPriceQtyDiscount) {
            unset($oldData['business_price_qty_discounts'][$key]['id'],
                  $oldData['business_price_qty_discounts'][$key]['template_selling_format_id']);
        }

        ksort($newData);
        ksort($oldData);
        array_walk($newData['business_price_qty_discounts'],'ksort');
        array_walk($oldData['business_price_qty_discounts'],'ksort');

        $helper = $this->getHelper('Data');
        return md5($helper->jsonEncode($newData)) !== md5($helper->jsonEncode($oldData));
    }

    //########################################
}