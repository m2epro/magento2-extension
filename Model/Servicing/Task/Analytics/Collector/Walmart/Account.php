<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Servicing\Task\Analytics\Collector\Walmart;

class Account implements \Ess\M2ePro\Model\Servicing\Task\Analytics\CollectorInterface
{
    /** @var \Ess\M2ePro\Model\ResourceModel\Account\CollectionFactory */
    private $accountEntityCollectionFactory;
    /** @var \Ess\M2ePro\Model\ResourceModel\Account\Collection|null  */
    private $entityCollection;

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Account\CollectionFactory $accountEntityCollectionFactory
    ) {
        $this->accountEntityCollectionFactory = $accountEntityCollectionFactory;
    }

    public function getComponent(): string
    {
        return \Ess\M2ePro\Helper\Component\Walmart::NICK;
    }

    public function getEntityName(): string
    {
        return 'Account';
    }

    public function getLastEntityId(): int
    {
        $collection = clone $this->createCollection();
        $collection->setOrder('id', \Magento\Framework\DB\Select::SQL_DESC)
                   ->setPageSize(1);

        return (int)$collection->getFirstItem()->getId();
    }

    public function getRows(int $fromId, int $toId): iterable
    {
        $collection = $this->createCollection();
        $collection->addFieldToFilter(
            'id',
            ['from' => $fromId, 'to' => $toId]
        );
        $collection->setOrder('id', \Magento\Framework\DB\Select::SQL_ASC);
        $collection->setPageSize(self::LIMIT);

        /** @var \Ess\M2ePro\Model\Account $item */
        foreach ($collection->getItems() as $item) {
            /** @var \Ess\M2ePro\Model\Walmart\Account $childItem */
            $childItem = $item->getChildObject();
            $preparedData = [
                'title' => $item->getData('title'),
                'additional_data' => $item->getData('additional_data'),
                'update_date' => $item->getData('update_date'),
                'create_date' => $item->getData('create_date'),
                'marketplace_id' => $childItem->getData('marketplace_id'),
                'consumer_id' => $childItem->getData('consumer_id'),
                'related_store_id' => $childItem->getData('related_store_id'),
                'other_listings_synchronization' => $childItem->getData('other_listings_synchronization'),
                'other_listings_mapping_mode' => $childItem->getData('other_listings_mapping_mode'),
                'other_listings_mapping_settings' => $childItem->getData('other_listings_mapping_settings'),
                'magento_orders_settings' => $childItem->getData('magento_orders_settings'),
                'create_magento_invoice' => $childItem->getData('create_magento_invoice'),
                'create_magento_shipment' => $childItem->getData('create_magento_shipment'),
                'other_carriers' => $childItem->getData('other_carriers'),
                'orders_last_synchronization' => $childItem->getData('orders_last_synchronization'),
                'inventory_last_synchronization' => $childItem->getData('inventory_last_synchronization'),
                'info' => $childItem->getData('info'),
            ];

            yield new \Ess\M2ePro\Model\Servicing\Task\Analytics\Row($item->getData('id'), $preparedData);
        }
    }

    private function createCollection(): \Ess\M2ePro\Model\ResourceModel\Account\Collection
    {
        if (!$this->entityCollection) {
            $this->entityCollection = $this->accountEntityCollectionFactory->createWithWalmartChildMode();
        }

        return $this->entityCollection;
    }
}
