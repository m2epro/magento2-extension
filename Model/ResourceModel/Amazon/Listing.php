<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Amazon;

use Ess\M2ePro\Helper\Component\Amazon;
use Ess\M2ePro\Model\Listing\Product;
use Magento\Framework\DB\Select;

/**
 * Class \Ess\M2ePro\Model\ResourceModel\Amazon\Listing
 */
class Listing extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Component\Child\AbstractModel
{
    protected $_isPkAutoIncrement = false;
    protected $_statisticDataCount = null;

    //########################################

    public function _construct()
    {
        $this->_init('m2epro_amazon_listing', 'listing_id');
    }

    //########################################

    public function getUsedProductsIds($listingId)
    {
        $collection = $this->activeRecordFactory->getObject('Listing\Product')->getCollection();
        $collection->addFieldToFilter('listing_id', $listingId);

        $collection->distinct(true);

        $collection->getSelect()->reset(Select::COLUMNS);
        $collection->getSelect()->columns(['product_id']);

        return $collection->getColumnValues('product_id');
    }

    //########################################

    public function getStatisticTotalCount($listingId)
    {
        $statisticData = $this->getStatisticData();
        if (!isset($statisticData[$listingId]['total'])) {
            return 0;
        }

        return (int)$statisticData[$listingId]['total'];
    }

    //########################################

    public function getStatisticActiveCount($listingId)
    {
        $statisticData = $this->getStatisticData();
        if (!isset($statisticData[$listingId]['active'])) {
            return 0;
        }

        return (int)$statisticData[$listingId]['active'];
    }

    //########################################

    public function getStatisticInactiveCount($listingId)
    {
        $statisticData = $this->getStatisticData();
        if (!isset($statisticData[$listingId]['inactive'])) {
            return 0;
        }

        return (int)$statisticData[$listingId]['inactive'];
    }

    //########################################

    protected function getStatisticData()
    {
        if ($this->_statisticDataCount) {
            return $this->_statisticDataCount;
        }

        $structureHelper = $this->getHelper('Module_Database_Structure');

        $m2eproListing = $structureHelper->getTableNameWithPrefix('m2epro_listing');
        $m2eproAmazonListing = $structureHelper->getTableNameWithPrefix('m2epro_amazon_listing');
        $m2eproListingProduct = $structureHelper->getTableNameWithPrefix('m2epro_listing_product');

        $sql = "SELECT
                    l.id                                           AS listing_id,
                    COUNT(lp.id)                                   AS total,
                    COUNT(CASE WHEN lp.status = 2 THEN lp.id END)  AS active,
                    COUNT(CASE WHEN lp.status != 2 THEN lp.id END) AS inactive
                FROM `{$m2eproListing}` AS `l`
                    INNER JOIN `{$m2eproAmazonListing}` AS `al` ON l.id = al.listing_id
                    LEFT JOIN `{$m2eproListingProduct}` AS `lp` ON l.id = lp.listing_id
                GROUP BY listing_id;";

        $result = $this->getConnection()->query($sql);

        $data = [];
        foreach($result as $value){
            $data[$value['listing_id']] = $value;
        }

        return $this->_statisticDataCount = $data;
    }
}
