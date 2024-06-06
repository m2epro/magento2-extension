<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Servicing\Task\Analytics\Collector\Amazon;

class Listing implements \Ess\M2ePro\Model\Servicing\Task\Analytics\CollectorInterface
{
    /** @var \Ess\M2ePro\Model\ResourceModel\Listing\CollectionFactory */
    private $listingEntityCollectionFactory;
    /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Collection|null  */
    private $entityCollection;

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Listing\CollectionFactory $listingEntityCollectionFactory
    ) {
        $this->listingEntityCollectionFactory = $listingEntityCollectionFactory;
    }

    public function getComponent(): string
    {
        return \Ess\M2ePro\Helper\Component\Amazon::NICK;
    }

    public function getEntityName(): string
    {
        return 'Listing';
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

        /** @var \Ess\M2ePro\Model\Listing $item */
        foreach ($collection->getItems() as $item) {
            /** @var \Ess\M2ePro\Model\Amazon\Listing $childItem */
            $childItem = $item->getChildObject();
            $preparedData = [
                'marketplace_id' => $item->getData('marketplace_id'),
                'title' => $item->getData('title'),
                'store_id' => $item->getData('store_id'),
                'source_products' => $item->getData('source_products'),
                'auto_mode' => $item->getData('auto_mode'),
                'auto_global_adding_mode' => $item->getData('auto_global_adding_mode'),
                'auto_global_adding_add_not_visible' => $item->getData('auto_global_adding_add_not_visible'),
                'auto_website_adding_mode' => $item->getData('auto_website_adding_mode'),
                'auto_website_adding_add_not_visible' => $item->getData('auto_website_adding_add_not_visible'),
                'auto_website_deleting_mode' => $item->getData('auto_website_deleting_mode'),
                'update_date' => $item->getData('update_date'),
                'create_date' => $item->getData('create_date'),
                'auto_global_adding_product_type_template_id' =>
                    $childItem->getData('auto_global_adding_product_type_template_id'),
                'auto_website_adding_product_type_template_id' =>
                    $childItem->getData('auto_website_adding_product_type_template_id'),
                'template_selling_format_id' => $childItem->getData('template_selling_format_id'),
                'template_synchronization_id' => $childItem->getData('template_synchronization_id'),
                'template_shipping_id' => $childItem->getData('template_shipping_id'),
                'sku_mode' => $childItem->getData('sku_mode'),
                'sku_custom_attribute' => $childItem->getData('sku_custom_attribute'),
                'sku_modification_mode' => $childItem->getData('sku_modification_mode'),
                'sku_modification_custom_value' => $childItem->getData('sku_modification_custom_value'),
                'generate_sku_mode' => $childItem->getData('generate_sku_mode'),
                'condition_mode' => $childItem->getData('condition_mode'),
                'condition_value' => $childItem->getData('condition_value'),
                'condition_custom_attribute' => $childItem->getData('condition_custom_attribute'),
                'condition_note_mode' => $childItem->getData('condition_note_mode'),
                'condition_note_value' => $childItem->getData('condition_note_value'),
                'gift_wrap_mode' => $childItem->getData('gift_wrap_mode'),
                'gift_wrap_attribute' => $childItem->getData('gift_wrap_attribute'),
                'gift_message_mode' => $childItem->getData('gift_message_mode'),
                'gift_message_attribute' => $childItem->getData('gift_message_attribute'),
                'handling_time_mode' => $childItem->getData('handling_time_mode'),
                'handling_time_value' => $childItem->getData('handling_time_value'),
                'handling_time_custom_attribute' => $childItem->getData('handling_time_custom_attribute'),
                'restock_date_mode' => $childItem->getData('restock_date_mode'),
                'restock_date_value' => $childItem->getData('restock_date_value'),
                'restock_date_custom_attribute' => $childItem->getData('restock_date_custom_attribute'),
                'product_add_ids' => $childItem->getData('product_add_ids'),
            ];

            yield new \Ess\M2ePro\Model\Servicing\Task\Analytics\Row($item->getData('id'), $preparedData);
        }
    }

    private function createCollection(): \Ess\M2ePro\Model\ResourceModel\Listing\Collection
    {
        if (!$this->entityCollection) {
            $this->entityCollection = $this->listingEntityCollectionFactory->createWithAmazonChildMode();
        }

        return $this->entityCollection;
    }
}
