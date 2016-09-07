<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Amazon;

use Ess\M2ePro\Helper\Component\Amazon;
use Ess\M2ePro\Model\Listing\Product;

class Listing extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Component\Child\AbstractModel
{
    protected $_isPkAutoIncrement = false;

    //########################################

    public function _construct()
    {
        $this->_init('m2epro_amazon_listing', 'listing_id');
        $this->_isPkAutoIncrement = false;
    }

    //########################################

    public function updateStatisticColumns()
    {
        $this->updateStatisticCountColumns();

        $lTable = $this->activeRecordFactory->getObject('Listing')->getResource()->getMainTable();
        $lpTable = $this->activeRecordFactory->getObject('Listing\Product')->getResource()->getMainTable();
        $alpTable = $this->activeRecordFactory->getObject('Amazon\Listing\Product')->getResource()->getMainTable();

        $select = $this->getConnection()
            ->select()
            ->from(
                array('lp' => $lpTable),
                new \Zend_Db_Expr('SUM(`online_qty`)')
            )
            ->join(
                array('alp' => $alpTable),
                'lp.id = alp.listing_product_id',
                array()
            )
            ->where("`listing_id` = `{$lTable}`.`id`")
            ->where("`status` = ?",(int)\Ess\M2ePro\Model\Listing\Product::STATUS_LISTED);

        $query = "UPDATE `{$lTable}`
                  SET `items_active_count` =  IFNULL((".$select->__toString()."),0)
                  WHERE `component_mode` = '".\Ess\M2ePro\Helper\Component\Amazon::NICK."'";

        $this->getConnection()->query($query);
    }

    private function updateStatisticCountColumns()
    {
        $listingTable = $this->getTable('m2epro_listing');
        $listingProductTable = $this->getTable('m2epro_listing_product');
        $amazonListingProductTable = $this->getTable('m2epro_amazon_listing_product');

        $statisticsData = array();
        $statusListed = Product::STATUS_LISTED;

        $totalCountSelect = $this->getConnection()
            ->select()
            ->from(
                array('lp' => $listingProductTable),
                array(
                    'listing_id' => 'listing_id',
                    'count'      => new \Zend_Db_Expr('COUNT(*)')
                )
            )
            ->join(
                array('alp' => $amazonListingProductTable),
                'lp.id = alp.listing_product_id',
                array()
            )
            ->where("`variation_parent_id` IS NULL")
            ->group('listing_id')
            ->query();

        while ($row = $totalCountSelect->fetch()) {

            if (empty($row['listing_id'])) {
                continue;
            }

            $statisticsData[$row['listing_id']] = array(
                'total'    => (int)$row['count'],
                'active'   => 0,
                'inactive' => 0
            );
        }

        $activeCountSelect = $this->getConnection()
            ->select()
            ->from(
                array('lp' => $listingProductTable),
                array(
                    'listing_id' => 'listing_id',
                    'count'      => new \Zend_Db_Expr('COUNT(*)')
                )
            )
            ->join(
                array('alp' => $amazonListingProductTable),
                'lp.id = alp.listing_product_id',
                array()
            )
            ->where("`variation_parent_id` IS NULL")
            ->where("lp.status = {$statusListed} OR
                    (alp.is_variation_parent = 1 AND alp.variation_child_statuses REGEXP '\"{$statusListed}\":[^0]')")
            ->group('listing_id')
            ->query();

        while ($row = $activeCountSelect->fetch()) {

            if (empty($row['listing_id'])) {
                continue;
            }

            $total = $statisticsData[$row['listing_id']]['total'];

            $statisticsData[$row['listing_id']]['active']   = (int)$row['count'];
            $statisticsData[$row['listing_id']]['inactive'] = $total - (int)$row['count'];
        }

        $existedListings = $this->getConnection()
            ->select()
            ->from(
                array('l' => $listingTable),
                array('id' => 'id')
            )
            ->where('component_mode = ?', Amazon::NICK)
            ->query();

        while ($listingId = $existedListings->fetchColumn()) {

            $totalCount    = isset($statisticsData[$listingId]) ? $statisticsData[$listingId]['total'] : 0;
            $activeCount   = isset($statisticsData[$listingId]) ? $statisticsData[$listingId]['active'] : 0;
            $inactiveCount = isset($statisticsData[$listingId]) ? $statisticsData[$listingId]['inactive'] : 0;

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
        $this->setSynchStatusNeedByListing($newData,$oldData,$listingProducts);
        $this->setSynchStatusNeedBySellingFormatTemplate($newData,$oldData,$listingProducts);
        $this->setSynchStatusNeedBySynchronizationTemplate($newData,$oldData,$listingProducts);
    }

    // ---------------------------------------

    public function setSynchStatusNeedByListing($newData, $oldData, $listingsProducts)
    {
        $listingsProductsIds = array();
        foreach ($listingsProducts as $listingProduct) {
            $listingsProductsIds[] = (int)$listingProduct['id'];
        }

        if (empty($listingsProductsIds)) {
            return;
        }

        unset(
            $newData['template_selling_format_id'], $oldData['template_selling_format_id'],
            $newData['template_synchronization_id'], $oldData['template_synchronization_id']
        );

        if (!$this->isDifferent($newData,$oldData)) {
            return;
        }

        $templates = array('listing');

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

    public function setSynchStatusNeedBySellingFormatTemplate($newData, $oldData, $listingsProducts)
    {
        $newSellingFormatTemplate = $this->parentFactory->getCachedObjectLoaded(
            \Ess\M2ePro\Helper\Component\Amazon::NICK, 'Template\SellingFormat', $newData['template_selling_format_id']
        );

        $oldSellingFormatTemplate = $this->parentFactory->getCachedObjectLoaded(
            \Ess\M2ePro\Helper\Component\Amazon::NICK, 'Template\SellingFormat', $oldData['template_selling_format_id']
        );

        $this->activeRecordFactory->getObject('Amazon\Template\SellingFormat')->getResource()->setSynchStatusNeed(
            $newSellingFormatTemplate->getDataSnapshot(),
            $oldSellingFormatTemplate->getDataSnapshot(),
            $listingsProducts
        );
    }

    public function setSynchStatusNeedBySynchronizationTemplate($newData, $oldData, $listingsProducts)
    {
        $newSynchTemplate = $this->parentFactory->getCachedObjectLoaded(
            \Ess\M2ePro\Helper\Component\Amazon::NICK,
            'Template\Synchronization',
            $newData['template_synchronization_id']
        );

        $oldSynchTemplate = $this->parentFactory->getCachedObjectLoaded(
            \Ess\M2ePro\Helper\Component\Amazon::NICK,
            'Template\Synchronization',
            $oldData['template_synchronization_id']
        );

        $this->activeRecordFactory->getObject('Amazon\Template\Synchronization')->getResource()->setSynchStatusNeed(
            $newSynchTemplate->getDataSnapshot(),
            $oldSynchTemplate->getDataSnapshot(),
            $listingsProducts
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

        return (count(array_diff_assoc($newData,$oldData)) > 0);
    }

    //########################################
}