<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Ebay\Template;

/**
 * Class \Ess\M2ePro\Model\ResourceModel\Ebay\Template\StoreCategory
 */
class StoreCategory extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\AbstractModel
{
    //########################################

    public function _construct()
    {
        $this->_init('m2epro_ebay_template_store_category', 'id');
    }

    //########################################

    public function loadByCategoryValue(
        \Ess\M2ePro\Model\Ebay\Template\StoreCategory $object,
        $value,
        $mode,
        $accountId
    ) {
        /** @var \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Collection\AbstractModel $collection */
        $collection = $object->getCollection();
        $collection->addFieldToFilter('category_mode', $mode);
        $collection->addFieldToFilter('account_id', $accountId);

        $mode == \Ess\M2ePro\Model\Ebay\Template\Category::CATEGORY_MODE_EBAY
            ? $collection->addFieldToFilter('category_id', $value)
            : $collection->addFieldToFilter('category_attribute', $value);

        if ($firstItem = $collection->getFirstItem()) {
            $object->setData($firstItem->getData());
        }
    }

    //########################################
}
