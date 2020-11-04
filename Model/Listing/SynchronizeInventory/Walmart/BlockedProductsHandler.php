<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Listing\SynchronizeInventory\Walmart;

use Ess\M2ePro\Model\Listing\SynchronizeInventory\AbstractBlockedHandler;

/**
 * Class \Ess\M2ePro\Model\Listing\SynchronizeInventory\Walmart\BlockedProductsHandler
 */
class BlockedProductsHandler extends AbstractBlockedHandler
{
    //########################################

    /**
     * @return \Zend_Db_Statement_Interface
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Exception
     */
    protected function getPdoStatementNotReceivedListingProducts()
    {
        /**
         * Wait for 24 hours before the newly listed item can be marked as inactive blocked
         */
        $borderDate = new \DateTime('now', new \DateTimeZone('UTC'));
        $borderDate->modify('- 24 hours');

        /** @var $collection \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection */
        $collection = $this->parentFactory->getObject($this->getComponentMode(), 'Listing\Product')->getCollection();
        $collection->joinListingTable();
        $collection->getSelect()->joinLeft(
            ['wiw' => $this->getComponentInventoryTable()],
            'second_table.wpid = wiw.wpid AND l.account_id = wiw.account_id',
            []
        );

        $collection->addFieldToFilter('l.account_id', (int)$this->getAccount()->getId());
        $collection->addFieldToFilter(
            'status', ['nin' => [
                \Ess\M2ePro\Model\Listing\Product::STATUS_BLOCKED,
                \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED
            ]]
        );
        $collection->addFieldToFilter('is_variation_parent', ['neq' => 1]);
        $collection->addFieldToFilter('is_missed_on_channel', ['neq' => 1]);
        $collection->addFieldToFilter(
            new \Zend_Db_Expr('list_date IS NULL OR list_date'), ['lt' => $borderDate->format('Y-m-d H:i:s')]
        );
        $collection->getSelect()->where('wiw.wpid IS NULL');


        $collection->getSelect()->reset(\Zend_Db_Select::COLUMNS)->columns(
            [
                'main_table.id',
                'main_table.status',
                'main_table.listing_id',
                'main_table.product_id',
                'second_table.wpid',
                'second_table.is_variation_product',
                'second_table.variation_parent_id'
            ]
        );

        return $this->resourceConnection->getConnection()->query($collection->getSelect()->__toString());
    }

    /**
     * @param array $listingProductIds
     */
    protected function updateListingProductStatuses(array $listingProductIds)
    {
        parent::updateListingProductStatuses($listingProductIds);

        $this->resourceConnection->getConnection()->update(
            $this->listingProductChildTable,
            ['is_missed_on_channel' => 1],
            '`listing_product_id` IN ('.implode(',', $listingProductIds).')'
        );
    }

    /**
     * @return string
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function getComponentInventoryTable()
    {
        return $this->helperFactory
            ->getObject('Module_Database_Structure')
            ->getTableNameWithPrefix('m2epro_walmart_inventory_wpid');
    }

    /**
     * @return string
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function getComponentOtherListingTable()
    {
        return$this->helperFactory
            ->getObject('Module_Database_Structure')
            ->getTableNameWithPrefix("m2epro_walmart_listing_other");
    }

    /**
     * @return string
     */
    protected function getInventoryIdentifier()
    {
        return 'wpid';
    }

    /**
     * @return string
     */
    protected function getComponentMode()
    {
        return \Ess\M2ePro\Helper\Component\Walmart::NICK;
    }

    //########################################
}
