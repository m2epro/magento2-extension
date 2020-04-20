<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Amazon\Account;

/**
 * Class \Ess\M2ePro\Model\ResourceModel\Amazon\Account\Repricing
 */
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

        $listingsProductsIds = [];
        foreach ($listingsProducts as $listingProduct) {
            $listingsProductsIds[] = $listingProduct['id'];
        }

        if (!$this->isDifferent($newData, $oldData)) {
            return;
        }

        /** @var \Ess\M2ePro\Model\ResourceModel\Amazon\Listing\Product\Repricing $resource */
        $resource = $this->activeRecordFactory->getObject('Amazon_Listing_Product_Repricing')->getResource();
        $resource->markAsProcessRequired($listingsProductsIds);
    }

    // ---------------------------------------

    public function isDifferent($newData, $oldData)
    {
        $ignoreFields = [
            $this->getIdFieldName(),
            'account_id', 'email', 'token',
            'total_products', 'create_date', 'update_date',
        ];

        foreach ($ignoreFields as $ignoreField) {
            unset($newData[$ignoreField], $oldData[$ignoreField]);
        }

        return !empty(array_diff_assoc($newData, $oldData));
    }

    //########################################
}
