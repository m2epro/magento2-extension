<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Walmart;

use Magento\Framework\DB\Select;

/**
 * Class \Ess\M2ePro\Model\ResourceModel\Walmart\Listing
 */
class Listing extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Component\Child\AbstractModel
{
    protected $_isPkAutoIncrement = false;

    //########################################

    public function _construct()
    {
        $this->_init('m2epro_walmart_listing', 'listing_id');
        $this->_isPkAutoIncrement = false;
    }

    //########################################

    public function updateStatisticColumns()
    {
        $this->updateStatisticCountColumns();

        $listingTable = $this->activeRecordFactory
            ->getObject('Listing')->getResource()->getMainTable();
        $listingProductTable = $this->activeRecordFactory
            ->getObject('Listing\Product')->getResource()->getMainTable();
        $walmartListingProductTable = $this->activeRecordFactory
            ->getObject('Walmart_Listing_Product')->getResource()->getMainTable();

        $select = $this->getConnection()
            ->select()
            ->from(
                ['lp' => $listingProductTable],
                new \Zend_Db_Expr('SUM(`online_qty`)')
            )
            ->join(
                ['wlp' => $walmartListingProductTable],
                'lp.id = wlp.listing_product_id',
                []
            )
            ->where("`listing_id` = `{$listingTable}`.`id`")
            ->where("`status` = ?", (int)\Ess\M2ePro\Model\Listing\Product::STATUS_LISTED);

        $query = "UPDATE `{$listingTable}`
                  SET `items_active_count` =  IFNULL((" . $select->__toString() . "),0)
                  WHERE `component_mode` = '" . \Ess\M2ePro\Helper\Component\Walmart::NICK . "'";

        $this->getConnection()->query($query);
    }

    private function updateStatisticCountColumns()
    {
        $listingTable = $this->activeRecordFactory
            ->getObject('Listing')->getResource()->getMainTable();
        $listingProductTable = $this->activeRecordFactory
            ->getObject('Listing\Product')->getResource()->getMainTable();
        $walmartListingProductTable = $this->activeRecordFactory
            ->getObject('Walmart_Listing_Product')->getResource()->getMainTable();

        $statisticsData = [];
        $statusListed = \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED;

        $totalCountSelect = $this->getConnection()
            ->select()
            ->from(
                ['lp' => $listingProductTable],
                [
                    'listing_id' => 'listing_id',
                    'count'      => new \Zend_Db_Expr('COUNT(*)')
                ]
            )
            ->join(
                ['wlp' => $walmartListingProductTable],
                'lp.id = wlp.listing_product_id',
                []
            )
            ->where("`variation_parent_id` IS NULL")
            ->group('listing_id')
            ->query();

        while ($row = $totalCountSelect->fetch()) {
            if (empty($row['listing_id'])) {
                continue;
            }

            $statisticsData[$row['listing_id']] = [
                'total'    => (int)$row['count'],
                'active'   => 0,
                'inactive' => 0
            ];
        }

        $activeCountSelect = $this->getConnection()
            ->select()
            ->from(
                ['lp' => $listingProductTable],
                [
                    'listing_id' => 'listing_id',
                    'count'      => new \Zend_Db_Expr('COUNT(*)')
                ]
            )
            ->join(
                ['wlp' => $walmartListingProductTable],
                'lp.id = wlp.listing_product_id',
                []
            )
            ->where("`variation_parent_id` IS NULL")
            ->where("lp.status = {$statusListed} OR
                    (wlp.is_variation_parent = 1 AND wlp.variation_child_statuses REGEXP '\"{$statusListed}\":[^0]')")
            ->group('listing_id')
            ->query();

        while ($row = $activeCountSelect->fetch()) {
            if (empty($row['listing_id'])) {
                continue;
            }

            $total = $statisticsData[$row['listing_id']]['total'];

            $statisticsData[$row['listing_id']]['active'] = (int)$row['count'];
            $statisticsData[$row['listing_id']]['inactive'] = $total - (int)$row['count'];
        }

        $existedListings = $this->getConnection()
            ->select()
            ->from(
                ['l' => $listingTable],
                ['id' => 'id']
            )
            ->where('component_mode = ?', \Ess\M2ePro\Helper\Component\Walmart::NICK)
            ->query();

        while ($listingId = $existedListings->fetchColumn()) {
            $totalCount = isset($statisticsData[$listingId]) ? $statisticsData[$listingId]['total'] : 0;
            $activeCount = isset($statisticsData[$listingId]) ? $statisticsData[$listingId]['active'] : 0;
            $inactiveCount = isset($statisticsData[$listingId]) ? $statisticsData[$listingId]['inactive'] : 0;

            if ($inactiveCount == 0 && $activeCount == 0) {
                $inactiveCount = $totalCount;
            }

            $query = "UPDATE `{$listingTable}`
                      SET `products_total_count` = {$totalCount},
                          `products_active_count` = {$activeCount},
                          `products_inactive_count` = {$inactiveCount}
                      WHERE `id` = {$listingId}";

            $this->getConnection()->query($query);
        }
    }

    //########################################

    public function setSynchStatusNeed($newData, $oldData, $listingProducts)
    {
        $this->setSynchStatusNeedBySellingFormatTemplate($newData, $oldData, $listingProducts);
        $this->setSynchStatusNeedByDescriptionTemplate($newData, $oldData, $listingProducts);
    }

    // ---------------------------------------

    public function setSynchStatusNeedBySellingFormatTemplate($newData, $oldData, $listingsProducts)
    {
        $newSellingFormatTemplate = $this->parentFactory->getCachedObjectLoaded(
            \Ess\M2ePro\Helper\Component\Walmart::NICK,
            'Template\SellingFormat',
            $newData['template_selling_format_id']
        );

        $oldSellingFormatTemplate = $this->parentFactory->getCachedObjectLoaded(
            \Ess\M2ePro\Helper\Component\Walmart::NICK,
            'Template\SellingFormat',
            $oldData['template_selling_format_id']
        );

        $this->activeRecordFactory->getObject('Walmart_Template_SellingFormat')->getResource()->setSynchStatusNeed(
            $newSellingFormatTemplate->getDataSnapshot(),
            $oldSellingFormatTemplate->getDataSnapshot(),
            $listingsProducts
        );
    }

    public function setSynchStatusNeedByDescriptionTemplate($newData, $oldData, $listingsProducts)
    {
        $newSellingFormatTemplate = $this->parentFactory->getCachedObjectLoaded(
            \Ess\M2ePro\Helper\Component\Walmart::NICK,
            'Template\Description',
            $newData['template_description_id']
        );

        $oldSellingFormatTemplate = $this->parentFactory->getCachedObjectLoaded(
            \Ess\M2ePro\Helper\Component\Walmart::NICK,
            'Template\Description',
            $oldData['template_description_id']
        );

        $this->activeRecordFactory->getObject('Walmart_Template_Description')->getResource()->setSynchStatusNeed(
            $newSellingFormatTemplate->getDataSnapshot(),
            $oldSellingFormatTemplate->getDataSnapshot(),
            $listingsProducts
        );
    }

    // ---------------------------------------

    public function isDifferent($newData, $oldData)
    {
        $ignoreFields = [
            $this->getIdFieldName(),
            'id', 'title',
            'component_mode',
            'create_date', 'update_date'
        ];

        foreach ($ignoreFields as $ignoreField) {
            unset($newData[$ignoreField], $oldData[$ignoreField]);
        }

        return !empty(array_diff_assoc($newData, $oldData));
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
}
