<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Servicing\Task\Analytics\Collector\Walmart;

use Ess\M2ePro\Model\ResourceModel\Template\SellingFormat\CollectionFactory as TemplateSellingFormatCollectionFactory;
use Ess\M2ePro\Model\ResourceModel\Walmart\Template\SellingFormat\Promotion\CollectionFactory
    as PromotionCollectionFactory;
use Ess\M2ePro\Model\ResourceModel\Walmart\Template\SellingFormat\ShippingOverride\CollectionFactory
    as ShippingOverrideCollectionFactory;

class TemplateSellingFormat implements \Ess\M2ePro\Model\Servicing\Task\Analytics\CollectorInterface
{
    /** @var TemplateSellingFormatCollectionFactory */
    private $templateSellingFormatEntityCollectionFactory;
    /** @var \Ess\M2ePro\Model\ResourceModel\Template\SellingFormat\Collection|null  */
    private $entityCollection;
    /** @var ShippingOverrideCollectionFactory */
    private $shippingOverrideCollectionFactory;
    /** @var PromotionCollectionFactory */
    private $promotionCollectionFactory;

    public function __construct(
        TemplateSellingFormatCollectionFactory $templateSellingFormatEntityCollectionFactory,
        ShippingOverrideCollectionFactory $shippingOverrideCollectionFactory,
        PromotionCollectionFactory $promotionCollectionFactory
    ) {
        $this->templateSellingFormatEntityCollectionFactory = $templateSellingFormatEntityCollectionFactory;
        $this->shippingOverrideCollectionFactory = $shippingOverrideCollectionFactory;
        $this->promotionCollectionFactory = $promotionCollectionFactory;
    }

    public function getComponent(): string
    {
        return \Ess\M2ePro\Helper\Component\Walmart::NICK;
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
            ['from' => $fromId, 'to' => $toId]
        );
        $collection->setOrder('id', \Magento\Framework\DB\Select::SQL_ASC);
        $collection->setPageSize(self::LIMIT);

        /** @var \Ess\M2ePro\Model\Template\SellingFormat $item */
        foreach ($collection->getItems() as $item) {
            /** @var \Ess\M2ePro\Model\Walmart\Template\SellingFormat $childItem */
            $childItem = $item->getChildObject();
            $preparedData = [
                'title' => $item->getData('title'),
                'update_date' => $item->getData('update_date'),
                'create_date' => $item->getData('create_date'),
                'marketplace_id' => $childItem->getData('marketplace_id'),
                'qty_mode' => $childItem->getData('qty_mode'),
                'qty_custom_value' => $childItem->getData('qty_custom_value'),
                'qty_custom_attribute' => $childItem->getData('qty_custom_attribute'),
                'qty_percentage' => $childItem->getData('qty_percentage'),
                'qty_modification_mode' => $childItem->getData('qty_modification_mode'),
                'qty_min_posted_value' => $childItem->getData('qty_min_posted_value'),
                'qty_max_posted_value' => $childItem->getData('qty_max_posted_value'),
                'price_mode' => $childItem->getData('price_mode'),
                'price_custom_attribute' => $childItem->getData('price_custom_attribute'),
                'price_modifier' => $childItem->getData('price_modifier'),
                'price_rounding_option' => $childItem->getData('price_rounding_option'),
                'price_variation_mode' => $childItem->getData('price_variation_mode'),
                'price_vat_percent' => $childItem->getData('price_vat_percent'),
                'promotions_mode' => $childItem->getData('promotions_mode'),
                'lag_time_mode' => $childItem->getData('lag_time_mode'),
                'lag_time_value' => $childItem->getData('lag_time_value'),
                'lag_time_custom_attribute' => $childItem->getData('lag_time_custom_attribute'),
                'item_weight_mode' => $childItem->getData('item_weight_mode'),
                'item_weight_custom_value' => $childItem->getData('item_weight_custom_value'),
                'item_weight_custom_attribute' => $childItem->getData('item_weight_custom_attribute'),
                'must_ship_alone_mode' => $childItem->getData('must_ship_alone_mode'),
                'must_ship_alone_value' => $childItem->getData('must_ship_alone_value'),
                'must_ship_alone_custom_attribute' => $childItem->getData('must_ship_alone_custom_attribute'),
                'ships_in_original_packaging_mode' => $childItem->getData('ships_in_original_packaging_mode'),
                'ships_in_original_packaging_value' => $childItem->getData('ships_in_original_packaging_value'),
                'ships_in_original_packaging_custom_attribute' =>
                    $childItem->getData('ships_in_original_packaging_custom_attribute'),
                'shipping_override_rule_mode' => $childItem->getData('shipping_override_rule_mode'),
                'sale_time_start_date_mode' => $childItem->getData('sale_time_start_date_mode'),
                'sale_time_start_date_value' => $childItem->getData('sale_time_start_date_value'),
                'sale_time_start_date_custom_attribute' => $childItem->getData('sale_time_start_date_custom_attribute'),
                'sale_time_end_date_mode' => $childItem->getData('sale_time_end_date_mode'),
                'sale_time_end_date_value' => $childItem->getData('sale_time_end_date_value'),
                'sale_time_end_date_custom_attribute' => $childItem->getData('sale_time_end_date_custom_attribute'),
                'attributes_mode' => $childItem->getData('attributes_mode'),
                'attributes' => $childItem->getData('attributes'),
                'shipping_overrides' => $this->addShippingOverridesData($item->getData('id')),
                'promotions' => $this->addPromotionData($item->getData('id')),
            ];

            yield new \Ess\M2ePro\Model\Servicing\Task\Analytics\Row($item->getData('id'), $preparedData);
        }
    }

    private function createCollection(): \Ess\M2ePro\Model\ResourceModel\Template\SellingFormat\Collection
    {
        if (!$this->entityCollection) {
            $this->entityCollection = $this->templateSellingFormatEntityCollectionFactory->createWithWalmartChildMode();
        }

        return $this->entityCollection;
    }

    private function addShippingOverridesData(int $id): array
    {
        $shippingOverrides = $this->shippingOverrideCollectionFactory
            ->create()
            ->addFieldToFilter('template_selling_format_id', $id)
            ->toArray();

        return $this->unsetDataInRelatedItems($shippingOverrides['items']);
    }

    private function addPromotionData(int $id): array
    {
        $promotions = $this->promotionCollectionFactory
            ->create()
            ->addFieldToFilter('template_selling_format_id', $id)
            ->toArray();

        return $this->unsetDataInRelatedItems($promotions['items']);
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
