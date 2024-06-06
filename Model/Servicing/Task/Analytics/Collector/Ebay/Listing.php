<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Servicing\Task\Analytics\Collector\Ebay;

class Listing implements \Ess\M2ePro\Model\Servicing\Task\Analytics\CollectorInterface
{
    /** @var \Ess\M2ePro\Model\ResourceModel\Listing\CollectionFactory */
    private $listingEntityCollectionFactory;
    /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Collection|null  */
    private $entityCollection;

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Listing\CollectionFactory$listingEntityCollectionFactory
    ) {
        $this->listingEntityCollectionFactory = $listingEntityCollectionFactory;
    }

    public function getComponent(): string
    {
        return \Ess\M2ePro\Helper\Component\Ebay::NICK;
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
            /** @var \Ess\M2ePro\Model\Ebay\Listing $childItem */
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
                'auto_global_adding_template_category_id' =>
                    $childItem->getData('auto_global_adding_template_category_id'),
                'auto_global_adding_template_category_secondary_id' =>
                    $childItem->getData('auto_global_adding_template_category_secondary_id'),
                'auto_global_adding_template_store_category_id' =>
                    $childItem->getData('auto_global_adding_template_store_category_id'),
                'auto_global_adding_template_store_category_secondary_id' =>
                    $childItem->getData('auto_global_adding_template_store_category_secondary_id'),
                'auto_website_adding_template_category_id' =>
                    $childItem->getData('auto_website_adding_template_category_id'),
                'auto_website_adding_template_category_secondary_id' =>
                    $childItem->getData('auto_website_adding_template_category_secondary_id'),
                'auto_website_adding_template_store_category_id' =>
                    $childItem->getData('auto_website_adding_template_store_category_id'),
                'auto_website_adding_template_store_category_secondary_id' =>
                    $childItem->getData('auto_website_adding_template_store_category_secondary_id'),
                'template_shipping_id' => $childItem->getData('template_shipping_id'),
                'template_return_policy_id' => $childItem->getData('template_return_policy_id'),
                'template_description_id' => $childItem->getData('template_description_id'),
                'template_selling_format_id' => $childItem->getData('template_selling_format_id'),
                'template_synchronization_id' => $childItem->getData('template_synchronization_id'),
                'parts_compatibility_mode' => $childItem->getData('parts_compatibility_mode'),
            ];

            yield new \Ess\M2ePro\Model\Servicing\Task\Analytics\Row($item->getData('id'), $preparedData);
        }
    }

    private function createCollection(): \Ess\M2ePro\Model\ResourceModel\Listing\Collection
    {
        if (!$this->entityCollection) {
            $this->entityCollection = $this->listingEntityCollectionFactory->createWithEbayChildMode();
        }

        return $this->entityCollection;
    }
}
