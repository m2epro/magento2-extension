<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Amazon\Account;

class Repricing extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\AbstractModel
{
    protected $_isPkAutoIncrement = false;

    //########################################

    public function _construct()
    {
        $this->_init('m2epro_amazon_account_repricing', 'account_id');
        $this->_isPkAutoIncrement = false;
    }

    //########################################

    public function setProcessRequired($newData, $oldData, $listingsProducts)
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

        $this->getConnection()->update(
            $this->getTable('m2epro_amazon_listing_product_repricing'),
            array('is_process_required' => 1),
            array('listing_product_id IN ('.implode(',', $listingsProductsIds).')')
        );
    }

    // ---------------------------------------

    public function isDifferent($newData, $oldData)
    {
        $ignoreFields = array(
            $this->getIdFieldName(),
            'account_id', 'email', 'token',
            'total_products', 'create_date', 'update_date',
        );

        foreach ($ignoreFields as $ignoreField) {
            unset($newData[$ignoreField], $oldData[$ignoreField]);
        }

        return (count(array_diff_assoc($newData, $oldData)) > 0);
    }

    //########################################
}