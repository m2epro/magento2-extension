<?php

namespace Ess\M2ePro\Model\Servicing\Task\Analytics\Collector\Ebay;

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
        return \Ess\M2ePro\Helper\Component\Ebay::NICK;
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
            /** @var \Ess\M2ePro\Model\Ebay\Template\Description $childItem */
            $childItem = $item->getChildObject();
            $preparedData = [
                'title' => $item->getData('title'),
                'update_date' => $item->getData('update_date'),
                'create_date' => $item->getData('create_date'),
                'is_custom_template' => $childItem->getData('is_custom_template'),
                'title_mode' => $childItem->getData('title_mode'),
                'title_template' => $childItem->getData('title_template'),
                'subtitle_mode' => $childItem->getData('subtitle_mode'),
                'subtitle_template' => $childItem->getData('subtitle_template'),
                'description_mode' => $childItem->getData('description_mode'),
                'condition_mode' => $childItem->getData('condition_mode'),
                'condition_value' => $childItem->getData('condition_value'),
                'condition_attribute' => $childItem->getData('condition_attribute'),
                'condition_note_mode' => $childItem->getData('condition_note_mode'),
                'condition_note_template' => $childItem->getData('condition_note_template'),
                'product_details' => $childItem->getData('product_details'),
                'cut_long_titles' => $childItem->getData('cut_long_titles'),
                'editor_type' => $childItem->getData('editor_type'),
                'enhancement' => $childItem->getData('enhancement'),
                'gallery_type' => $childItem->getData('gallery_type'),
                'image_main_mode' => $childItem->getData('image_main_mode'),
                'image_main_attribute' => $childItem->getData('image_main_attribute'),
                'gallery_images_mode' => $childItem->getData('gallery_images_mode'),
                'gallery_images_limit' => $childItem->getData('gallery_images_limit'),
                'gallery_images_attribute' => $childItem->getData('gallery_images_attribute'),
                'variation_images_mode' => $childItem->getData('variation_images_mode'),
                'variation_images_limit' => $childItem->getData('variation_images_limit'),
                'variation_images_attribute' => $childItem->getData('variation_images_attribute'),
                'default_image_url' => $childItem->getData('default_image_url'),
                'variation_configurable_images' => $childItem->getData('variation_configurable_images'),
                'use_supersize_images' => $childItem->getData('use_supersize_images'),
                'watermark_mode' => $childItem->getData('watermark_mode'),
                'watermark_settings' => $childItem->getData('watermark_settings'),

            ];

            yield new \Ess\M2ePro\Model\Servicing\Task\Analytics\Row($item->getData('id'), $preparedData);
        }
    }

    private function createCollection(): \Ess\M2ePro\Model\ResourceModel\Template\Description\Collection
    {
        if (!$this->entityCollection) {
            $this->entityCollection = $this->descriptionEntityCollectionFactory->createWithEbayChildMode();
        }

        return $this->entityCollection;
    }
}
