<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Servicing\Task\Analytics\Collector\Ebay;

use Ess\M2ePro\Model\ResourceModel\Template\SellingFormat\CollectionFactory as TemplateSellingFormatCollectionFactory;

class TemplateSellingFormat implements \Ess\M2ePro\Model\Servicing\Task\Analytics\CollectorInterface
{
    /** @var TemplateSellingFormatCollectionFactory */
    private $templateSellingFormatEntityCollectionFactory;
    /** @var \Ess\M2ePro\Model\ResourceModel\Template\SellingFormat\Collection|null  */
    private $entityCollection;

    public function __construct(
        TemplateSellingFormatCollectionFactory $templateSellingFormatEntityCollectionFactory
    ) {
        $this->templateSellingFormatEntityCollectionFactory = $templateSellingFormatEntityCollectionFactory;
    }

    public function getComponent(): string
    {
        return \Ess\M2ePro\Helper\Component\Ebay::NICK;
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
            /** @var \Ess\M2ePro\Model\Ebay\Template\SellingFormat $childItem */
            $childItem = $item->getChildObject();
            $preparedData = [
                'title' => $item->getData('title'),
                'update_date' => $item->getData('update_date'),
                'create_date' => $item->getData('create_date'),
                'is_custom_template' => $childItem->getData('is_custom_template'),
                'listing_type' => $childItem->getData('listing_type'),
                'listing_type_attribute' => $childItem->getData('listing_type_attribute'),
                'listing_is_private' => $childItem->getData('listing_is_private'),
                'restricted_to_business' => $childItem->getData('restricted_to_business'),
                'duration_mode' => $childItem->getData('duration_mode'),
                'duration_attribute' => $childItem->getData('duration_attribute'),
                'qty_mode' => $childItem->getData('qty_mode'),
                'qty_custom_value' => $childItem->getData('qty_custom_value'),
                'qty_custom_attribute' => $childItem->getData('qty_custom_attribute'),
                'qty_percentage' => $childItem->getData('qty_percentage'),
                'qty_modification_mode' => $childItem->getData('qty_modification_mode'),
                'qty_min_posted_value' => $childItem->getData('qty_min_posted_value'),
                'qty_max_posted_value' => $childItem->getData('qty_max_posted_value'),
                'lot_size_mode' => $childItem->getData('lot_size_mode'),
                'lot_size_custom_value' => $childItem->getData('lot_size_custom_value'),
                'lot_size_attribute' => $childItem->getData('lot_size_attribute'),
                'vat_mode' => $childItem->getData('vat_mode'),
                'vat_percent' => $childItem->getData('vat_percent'),
                'tax_table_mode' => $childItem->getData('tax_table_mode'),
                'tax_category_mode' => $childItem->getData('tax_category_mode'),
                'tax_category_value' => $childItem->getData('tax_category_value'),
                'tax_category_attribute' => $childItem->getData('tax_category_attribute'),
                'price_variation_mode' => $childItem->getData('price_variation_mode'),
                'fixed_price_mode' => $childItem->getData('fixed_price_mode'),
                'fixed_price_modifier' => $childItem->getData('fixed_price_modifier'),
                'fixed_price_custom_attribute' => $childItem->getData('fixed_price_custom_attribute'),
                'fixed_price_rounding_option' => $childItem->getData('fixed_price_rounding_option'),
                'start_price_mode' => $childItem->getData('start_price_mode'),
                'start_price_coefficient' => $childItem->getData('start_price_coefficient'),
                'start_price_custom_attribute' => $childItem->getData('start_price_custom_attribute'),
                'start_price_rounding_option' => $childItem->getData('start_price_rounding_option'),
                'reserve_price_mode' => $childItem->getData('reserve_price_mode'),
                'reserve_price_coefficient' => $childItem->getData('reserve_price_coefficient'),
                'reserve_price_custom_attribute' => $childItem->getData('reserve_price_custom_attribute'),
                'reserve_price_rounding_option' => $childItem->getData('reserve_price_rounding_option'),
                'buyitnow_price_mode' => $childItem->getData('buyitnow_price_mode'),
                'buyitnow_price_coefficient' => $childItem->getData('buyitnow_price_coefficient'),
                'buyitnow_price_custom_attribute' => $childItem->getData('buyitnow_price_custom_attribute'),
                'buyitnow_price_rounding_option' => $childItem->getData('buyitnow_price_rounding_option'),
                'price_discount_stp_mode' => $childItem->getData('price_discount_stp_mode'),
                'price_discount_stp_attribute' => $childItem->getData('price_discount_stp_attribute'),
                'price_discount_stp_type' => $childItem->getData('price_discount_stp_type'),
                'price_discount_map_mode' => $childItem->getData('price_discount_map_mode'),
                'price_discount_map_attribute' => $childItem->getData('price_discount_map_attribute'),
                'price_discount_map_exposure_type' => $childItem->getData('price_discount_map_exposure_type'),
                'best_offer_mode' => $childItem->getData('best_offer_mode'),
                'best_offer_accept_mode' => $childItem->getData('best_offer_accept_mode'),
                'best_offer_accept_value' => $childItem->getData('best_offer_accept_value'),
                'best_offer_accept_attribute' => $childItem->getData('best_offer_accept_attribute'),
                'best_offer_reject_mode' => $childItem->getData('best_offer_reject_mode'),
                'best_offer_reject_value' => $childItem->getData('best_offer_reject_value'),
                'best_offer_reject_attribute' => $childItem->getData('best_offer_reject_attribute'),
                'charity' => $childItem->getData('charity'),
                'ignore_variations' => $childItem->getData('ignore_variations'),
                'paypal_immediate_payment' => $childItem->getData('paypal_immediate_payment'),
            ];

            yield new \Ess\M2ePro\Model\Servicing\Task\Analytics\Row($item->getData('id'), $preparedData);
        }
    }

    private function createCollection(): \Ess\M2ePro\Model\ResourceModel\Template\SellingFormat\Collection
    {
        if (!$this->entityCollection) {
            $this->entityCollection = $this->templateSellingFormatEntityCollectionFactory->createWithEbayChildMode();
        }

        return $this->entityCollection;
    }
}
