<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Listing\SynchronizeInventory\Amazon;

use Ess\M2ePro\Model\Listing\SynchronizeInventory\AbstractBlockedHandler;

/**
 * Class \Ess\M2ePro\Model\Listing\SynchronizeInventory\Amazon\BlockedProductsHandler
 */
class BlockedProductsHandler extends AbstractBlockedHandler
{
    //########################################

    /**
     * @return \Zend_Db_Statement_Interface
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function getPdoStatementNotReceivedListingProducts()
    {
        $borderDate = new \DateTime('now', new \DateTimeZone('UTC'));
        $borderDate->modify('- 1 hour');

        /** @var $collection \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection */
        $collection = $this->parentFactory->getObject($this->getComponentMode(), 'Listing\Product')->getCollection();
        $collection->joinListingTable();
        $collection->getSelect()->joinLeft(
            ['ais' => $this->getComponentInventoryTable()],
            'second_table.sku = ais.sku AND l.account_id = ais.account_id',
            []
        );
        $collection->getSelect()->where('l.account_id = ?', (int)$this->getAccount()->getId());
        $collection->getSelect()->where('second_table.is_variation_parent != ?', 1);
        $collection->getSelect()->where(
            'second_table.list_date IS NULL OR second_table.list_date < ?', $borderDate->format('Y-m-d H:i:s')
        );
        $collection->getSelect()->where('ais.sku IS NULL');
        $collection->getSelect()->where(
            '`main_table`.`status` != ?',
            \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED
        );
        $collection->getSelect()->where(
            '`main_table`.`status` != ?',
            \Ess\M2ePro\Model\Listing\Product::STATUS_BLOCKED
        );

        $collection->getSelect()->reset(\Magento\Framework\DB\Select::COLUMNS)->columns(
            [
                'main_table.id',
                'main_table.status',
                'main_table.listing_id',
                'main_table.product_id',
                'main_table.additional_data',
                'second_table.is_variation_product',
                'second_table.variation_parent_id'
            ]
        );

        return $this->resourceConnection->getConnection()->query($collection->getSelect()->__toString());
    }

    /**
     * @return string
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function getComponentInventoryTable()
    {
        return $this->helperFactory
            ->getObject('Module_Database_Structure')
            ->getTableNameWithPrefix('m2epro_amazon_inventory_sku');
    }

    /**
     * @return string
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function getComponentOtherListingTable()
    {
        return $this->helperFactory
            ->getObject('Module_Database_Structure')
            ->getTableNameWithPrefix("m2epro_amazon_listing_other");
    }

    /**
     * @return string
     */
    protected function getInventoryIdentifier()
    {
        return 'sku';
    }

    /**
     * @return string
     */
    protected function getComponentMode()
    {
        return \Ess\M2ePro\Helper\Component\Amazon::NICK;
    }

    //########################################
}
