<?php

namespace Ess\M2ePro\Model\Servicing\Task\Analytics\Collector\Walmart;

class TemplateDescription implements \Ess\M2ePro\Model\Servicing\Task\Analytics\CollectorInterface
{
    /** @var \Ess\M2ePro\Model\ResourceModel\Template\Description\CollectionFactory */
    private $descriptionEntityCollectionFactory;
    /** @var \Ess\M2ePro\Model\ResourceModel\Template\Description\Collection|null  */
    private $entityCollection;

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Template\Description\CollectionFactory $descriptionEntityCollectionFactory
    ) {
        $this->descriptionEntityCollectionFactory = $descriptionEntityCollectionFactory;
    }

    public function getComponent(): string
    {
        return \Ess\M2ePro\Helper\Component\Walmart::NICK;
    }

    public function getEntityName(): string
    {
        return 'Template_Description';
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

        /** @var \Ess\M2ePro\Model\Template\Description $item */
        foreach ($collection->getItems() as $item) {
            /** @var \Ess\M2ePro\Model\Walmart\Template\Description $childItem */
            $childItem = $item->getChildObject();
            $preparedData = [
                'title' => $item->getData('title'),
                'update_date' => $item->getData('update_date'),
                'create_date' => $item->getData('create_date'),
                'title_mode' => $childItem->getData('title_mode'),
                'title_template' => $childItem->getData('title_template'),
                'brand_mode' => $childItem->getData('brand_mode'),
                'brand_custom_value' => $childItem->getData('brand_custom_value'),
                'brand_custom_attribute' => $childItem->getData('brand_custom_attribute'),
                'manufacturer_mode' => $childItem->getData('manufacturer_mode'),
                'manufacturer_custom_value' => $childItem->getData('manufacturer_custom_value'),
                'manufacturer_custom_attribute' => $childItem->getData('manufacturer_custom_attribute'),
                'manufacturer_part_number_mode' => $childItem->getData('manufacturer_part_number_mode'),
                'manufacturer_part_number_custom_value' =>
                    $childItem->getData('manufacturer_part_number_custom_value'),
                'manufacturer_part_number_custom_attribute' =>
                    $childItem->getData('manufacturer_part_number_custom_attribute'),
                'model_number_mode' => $childItem->getData('model_number_mode'),
                'model_number_custom_value' => $childItem->getData('model_number_custom_value'),
                'model_number_custom_attribute' => $childItem->getData('model_number_custom_attribute'),
                'msrp_rrp_mode' => $childItem->getData('msrp_rrp_mode'),
                'msrp_rrp_custom_attribute' => $childItem->getData('msrp_rrp_custom_attribute'),
                'image_main_mode' => $childItem->getData('image_main_mode'),
                'image_main_attribute' => $childItem->getData('image_main_attribute'),
                'image_variation_difference_mode' => $childItem->getData('image_variation_difference_mode'),
                'image_variation_difference_attribute' =>
                    $childItem->getData('image_variation_difference_attribute'),
                'gallery_images_mode' => $childItem->getData('gallery_images_mode'),
                'gallery_images_limit' => $childItem->getData('gallery_images_limit'),
                'gallery_images_attribute' => $childItem->getData('gallery_images_attribute'),
                'description_mode' => $childItem->getData('description_mode'),
                'multipack_quantity_mode' => $childItem->getData('multipack_quantity_mode'),
                'multipack_quantity_custom_value' => $childItem->getData('multipack_quantity_custom_value'),
                'multipack_quantity_custom_attribute' =>
                    $childItem->getData('multipack_quantity_custom_attribute'),
                'count_per_pack_mode' => $childItem->getData('count_per_pack_mode'),
                'count_per_pack_custom_value' => $childItem->getData('count_per_pack_custom_value'),
                'count_per_pack_custom_attribute' => $childItem->getData('count_per_pack_custom_attribute'),
                'total_count_mode' => $childItem->getData('total_count_mode'),
                'total_count_custom_value' => $childItem->getData('total_count_custom_value'),
                'total_count_custom_attribute' => $childItem->getData('total_count_custom_attribute'),
                'key_features_mode' => $childItem->getData('key_features_mode'),
                'key_features' => $childItem->getData('key_features'),
                'other_features_mode' => $childItem->getData('other_features_mode'),
                'other_features' => $childItem->getData('other_features'),
            ];

            yield new \Ess\M2ePro\Model\Servicing\Task\Analytics\Row($item->getData('id'), $preparedData);
        }
    }

    private function createCollection(): \Ess\M2ePro\Model\ResourceModel\Template\Description\Collection
    {
        if (!$this->entityCollection) {
            $this->entityCollection = $this->descriptionEntityCollectionFactory->createWithWalmartChildMode();
        }

        return $this->entityCollection;
    }
}
