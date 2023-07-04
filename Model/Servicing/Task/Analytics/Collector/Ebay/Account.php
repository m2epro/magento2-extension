<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Servicing\Task\Analytics\Collector\Ebay;

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
        return \Ess\M2ePro\Helper\Component\Ebay::NICK;
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
            /** @var \Ess\M2ePro\Model\Ebay\Account $childItem */
            $childItem = $item->getChildObject();
            $preparedData = [
                'title' => $item->getData('title'),
                'additional_data' => $item->getData('additional_data'),
                'update_date' => $item->getData('update_date'),
                'create_date' => $item->getData('create_date'),
                'mode' => $childItem->getData('mode'),
                'user_id' => $childItem->getData('user_id'),
                'token_expired_date' => $childItem->getData('token_expired_date'),
                'sell_api_token_expired_date' => $childItem->getData('sell_api_token_expired_date'),
                'marketplaces_data' => $childItem->getData('marketplaces_data'),
                'inventory_last_synchronization' => $childItem->getData('inventory_last_synchronization'),
                'other_listings_synchronization' => $childItem->getData('other_listings_synchronization'),
                'other_listings_mapping_mode' => $childItem->getData('other_listings_mapping_mode'),
                'other_listings_mapping_settings' => $childItem->getData('other_listings_mapping_settings'),
                'other_listings_last_synchronization' => $childItem->getData('other_listings_last_synchronization'),
                'feedbacks_receive' => $childItem->getData('feedbacks_receive'),
                'feedbacks_auto_response' => $childItem->getData('feedbacks_auto_response'),
                'feedbacks_auto_response_only_positive' => $childItem->getData('feedbacks_auto_response_only_positive'),
                'feedbacks_last_used_id' => $childItem->getData('feedbacks_last_used_id'),
                'ebay_store_title' => $childItem->getData('ebay_store_title'),
                'ebay_store_url' => $childItem->getData('ebay_store_url'),
                'ebay_store_subscription_level' => $childItem->getData('ebay_store_subscription_level'),
                'ebay_store_description' => $childItem->getData('ebay_store_description'),
                'rate_tables' => $childItem->getData('rate_tables'),
                'ebay_shipping_discount_profiles' => $childItem->getData('ebay_shipping_discount_profiles'),
                'orders_last_synchronization' => $childItem->getData('orders_last_synchronization'),
                'magento_orders_settings' => $childItem->getData('magento_orders_settings'),
                'create_magento_invoice' => $childItem->getData('create_magento_invoice'),
                'create_magento_shipment' => $childItem->getData('create_magento_shipment'),
                'skip_evtin' => $childItem->getData('skip_evtin'),
                'messages_receive' => $childItem->getData('messages_receive'),
            ];

            yield new \Ess\M2ePro\Model\Servicing\Task\Analytics\Row($item->getData('id'), $preparedData);
        }
    }

    private function createCollection(): \Ess\M2ePro\Model\ResourceModel\Account\Collection
    {
        if (!$this->entityCollection) {
            $this->entityCollection = $this->accountEntityCollectionFactory->createWithEbayChildMode();
        }

        return $this->entityCollection;
    }
}
