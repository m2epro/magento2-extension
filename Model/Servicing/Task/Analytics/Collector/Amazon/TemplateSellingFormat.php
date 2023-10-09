<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Servicing\Task\Analytics\Collector\Amazon;

use Ess\M2ePro\Model\ResourceModel\Template\SellingFormat\CollectionFactory as TemplateSellingFormatCollectionFactory;
use Ess\M2ePro\Model\ResourceModel\Amazon\Template\SellingFormat\BusinessDiscount\CollectionFactory
    as BusinessDiscountCollectionFactory;

class TemplateSellingFormat implements \Ess\M2ePro\Model\Servicing\Task\Analytics\CollectorInterface
{
    /** @var TemplateSellingFormatCollectionFactory */
    private $templateSellingFormatEntityCollectionFactory;
    /** @var \Ess\M2ePro\Model\ResourceModel\Template\SellingFormat\Collection|null  */
    private $entityCollection;
    /** @var BusinessDiscountCollectionFactory */
    private $businessDiscountCollectionFactory;

    public function __construct(
        TemplateSellingFormatCollectionFactory $templateSellingFormatEntityCollectionFactory,
        BusinessDiscountCollectionFactory $businessDiscountCollectionFactory
    ) {
        $this->templateSellingFormatEntityCollectionFactory = $templateSellingFormatEntityCollectionFactory;
        $this->businessDiscountCollectionFactory = $businessDiscountCollectionFactory;
    }

    public function getComponent(): string
    {
        return \Ess\M2ePro\Helper\Component\Amazon::NICK;
    }

    public function getEntityName(): string
    {
        return 'Template_SellingFormat';
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

        /** @var \Ess\M2ePro\Model\Template\SellingFormat $item */
        foreach ($collection->getItems() as $item) {
            /** @var \Ess\M2ePro\Model\Amazon\Template\SellingFormat $childItem */
            $childItem = $item->getChildObject();
            $preparedData = [
                'title' => $item->getData('title'),
                'update_date' => $item->getData('update_date'),
                'create_date' => $item->getData('create_date'),
                'qty_mode' => $childItem->getData('qty_mode'),
                'qty_custom_value' => $childItem->getData('qty_custom_value'),
                'qty_custom_attribute' => $childItem->getData('qty_custom_attribute'),
                'qty_percentage' => $childItem->getData('qty_percentage'),
                'qty_modification_mode' => $childItem->getData('qty_modification_mode'),
                'qty_min_posted_value' => $childItem->getData('qty_min_posted_value'),
                'qty_max_posted_value' => $childItem->getData('qty_max_posted_value'),
                'is_regular_customer_allowed' => $childItem->getData('is_regular_customer_allowed'),
                'is_business_customer_allowed' => $childItem->getData('is_business_customer_allowed'),
                'regular_price_mode' => $childItem->getData('regular_price_mode'),
                'regular_price_custom_attribute' => $childItem->getData('regular_price_custom_attribute'),
                'regular_price_modifier' => $childItem->getData('regular_price_modifier'),
                'price_rounding_option' => $childItem->getData('price_rounding_option'),
                'regular_map_price_mode' => $childItem->getData('regular_map_price_mode'),
                'regular_map_price_custom_attribute' => $childItem->getData('regular_map_price_custom_attribute'),
                'regular_sale_price_mode' => $childItem->getData('regular_sale_price_mode'),
                'regular_sale_price_custom_attribute' =>
                    $childItem->getData('regular_sale_price_custom_attribute'),
                'regular_sale_price_modifier' => $childItem->getData('regular_sale_price_modifier'),
                'regular_price_variation_mode' => $childItem->getData('regular_price_variation_mode'),
                'regular_sale_price_start_date_mode' => $childItem->getData('regular_sale_price_start_date_mode'),
                'regular_sale_price_start_date_value' =>
                    $childItem->getData('regular_sale_price_start_date_value'),
                'regular_sale_price_start_date_custom_attribute' =>
                    $childItem->getData('regular_sale_price_start_date_custom_attribute'),
                'regular_sale_price_end_date_mode' => $childItem->getData('regular_sale_price_end_date_mode'),
                'regular_sale_price_end_date_value' => $childItem->getData('regular_sale_price_end_date_value'),
                'regular_sale_price_end_date_custom_attribute' =>
                    $childItem->getData('regular_sale_price_end_date_custom_attribute'),
                'regular_price_vat_percent' => $childItem->getData('regular_price_vat_percent'),
                'business_price_mode' => $childItem->getData('business_price_mode'),
                'business_price_custom_attribute' => $childItem->getData('business_price_custom_attribute'),
                'business_price_modifier' => $childItem->getData('business_price_modifier'),
                'business_price_variation_mode' => $childItem->getData('business_price_variation_mode'),
                'business_price_vat_percent' => $childItem->getData('business_price_vat_percent'),
                'business_discounts_mode' => $childItem->getData('business_discounts_mode'),
                'business_discounts_tier_modifier' => $childItem->getData('business_discounts_tier_modifier'),
                'business_discounts_tier_customer_group_id' =>
                    $childItem->getData('business_discounts_tier_customer_group_id'),
                'business_discounts' => $this->addBusinessDiscountsData($item->getData('id')),
            ];

            yield new \Ess\M2ePro\Model\Servicing\Task\Analytics\Row($item->getData('id'), $preparedData);
        }
    }

    private function createCollection(): \Ess\M2ePro\Model\ResourceModel\Template\SellingFormat\Collection
    {
        if (!$this->entityCollection) {
            $this->entityCollection = $this->templateSellingFormatEntityCollectionFactory->createWithAmazonChildMode();
        }

        return $this->entityCollection;
    }

    private function addBusinessDiscountsData(int $id): array
    {
        $businessDiscounts = $this->businessDiscountCollectionFactory
            ->create()
            ->addFieldToFilter('template_selling_format_id', $id)
            ->toArray();

        return $this->unsetDataInRelatedItems($businessDiscounts['items']);
    }

    private function unsetDataInRelatedItems(array $items): array
    {
        return array_map(
            static function ($el) {
                unset($el['template_selling_format_id']);

                return $el;
            },
            $items
        );
    }
}
