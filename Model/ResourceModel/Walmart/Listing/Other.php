<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Walmart\Listing;

/**
 * Class \Ess\M2ePro\Model\ResourceModel\Walmart\Listing\Other
 */
class Other extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Component\Child\AbstractModel
{
    protected $_isPkAutoIncrement = false;

    protected $walmartFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory,
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        $connectionName = null
    ) {
        $this->walmartFactory = $walmartFactory;

        parent::__construct($helperFactory, $activeRecordFactory, $parentFactory, $context, $connectionName);
    }

    //########################################

    public function _construct()
    {
        $this->_init('m2epro_walmart_listing_other', 'listing_other_id');
        $this->_isPkAutoIncrement = false;
    }

    //########################################

    public function getProductsDataBySkus(
        array $skus = [],
        array $filters = [],
        array $columns = []
    ) {
        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Other\Collection $listingOtherCollection */
        $listingOtherCollection = $this->walmartFactory->getObject('Listing\Other')->getCollection();

        if (!empty($skus)) {
            $skus = array_map(function ($el) {
                return (string)$el;
            }, $skus);
            $listingOtherCollection->addFieldToFilter('sku', ['in' => array_unique($skus)]);
        }

        if (!empty($filters)) {
            foreach ($filters as $columnName => $columnValue) {
                $listingOtherCollection->addFieldToFilter($columnName, $columnValue);
            }
        }

        if (!empty($columns)) {
            $listingOtherCollection->getSelect()->reset(\Zend_Db_Select::COLUMNS);
            $listingOtherCollection->getSelect()->columns($columns);
        }

        return $listingOtherCollection->getData();
    }

    //########################################
}
