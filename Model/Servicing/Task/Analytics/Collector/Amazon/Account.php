<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Servicing\Task\Analytics\Collector\Amazon;

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
        return \Ess\M2ePro\Helper\Component\Amazon::NICK;
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
            ['gt' => $fromId, 'lte' => $toId]
        );
        $collection->setOrder('id', \Magento\Framework\DB\Select::SQL_ASC);
        $collection->setPageSize(self::LIMIT);

        /** @var \Ess\M2ePro\Model\Account $item */
        foreach ($collection->getItems() as $item) {
            /** @var \Ess\M2ePro\Model\Amazon\Account $childItem */
            $childItem = $item->getChildObject();
            $preparedData = [
                'title' => $item->getData('title'),
                'additional_data' => $item->getData('additional_data'),
                'update_date' => $item->getData('update_date'),
                'create_date' => $item->getData('create_date'),
                'marketplace_id' => $childItem->getData('marketplace_id'),
                'merchant_id' => $childItem->getData('merchant_id'),
                'related_store_id' => $childItem->getData('related_store_id'),
                'other_listings_synchronization' => $childItem->getData('other_listings_synchronization'),
                'other_listings_mapping_mode' => $childItem->getData('other_listings_mapping_mode'),
                'other_listings_mapping_settings' => $childItem->getData('other_listings_mapping_settings'),
                'inventory_last_synchronization' => $childItem->getData('inventory_last_synchronization'),
                'magento_orders_settings' => $childItem->getData('magento_orders_settings'),
                'auto_invoicing' => $childItem->getData('auto_invoicing'),
                'invoice_generation' => $childItem->getData('invoice_generation'),
                'create_magento_invoice' => $childItem->getData('create_magento_invoice'),
                'create_magento_shipment' => $childItem->getData('create_magento_shipment'),
                'remote_fulfillment_program_mode' => $childItem->getData('remote_fulfillment_program_mode'),
                'info' => $childItem->getData('info'),
            ];

            yield new \Ess\M2ePro\Model\Servicing\Task\Analytics\Row($item->getData('id'), $preparedData);
        }
    }

    private function createCollection(): \Ess\M2ePro\Model\ResourceModel\Account\Collection
    {
        if (!$this->entityCollection) {
            $this->entityCollection = $this->accountEntityCollectionFactory->createWithAmazonChildMode();
        }

        return $this->entityCollection;
    }
}
