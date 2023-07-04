<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Servicing\Task\Analytics\Collector\Ebay;

use Ess\M2ePro\Model\ResourceModel\Ebay\Template\Shipping\CollectionFactory as TemplateShippingCollectionFactory;
use Ess\M2ePro\Model\ResourceModel\Ebay\Template\Shipping\Service\CollectionFactory as ShippingServiceCollectionFactory;

class EbayTemplateShipping implements \Ess\M2ePro\Model\Servicing\Task\Analytics\CollectorInterface
{
    /** @var TemplateShippingCollectionFactory */
    private $ebayTemplateShippingEntityCollectionFactory;
    /** @var \Ess\M2ePro\Model\ResourceModel\Ebay\Template\Shipping\Collection|null  */
    private $entityCollection;
    /** @var \Ess\M2ePro\Model\Ebay\Template\Shipping\CalculatedFactory */
    private $calculatedFactory;
    /** @var \Ess\M2ePro\Model\ResourceModel\Ebay\Template\Shipping\Calculated */
    private $calculatedResource;
    /** @var ShippingServiceCollectionFactory */
    private $ebayTemplateShippingServiceCollectionFactory;

    public function __construct(
        TemplateShippingCollectionFactory $ebayTemplateShippingEntityCollectionFactory,
        \Ess\M2ePro\Model\Ebay\Template\Shipping\CalculatedFactory $calculatedFactory,
        \Ess\M2ePro\Model\ResourceModel\Ebay\Template\Shipping\Calculated $calculatedResource,
        ShippingServiceCollectionFactory $ebayTemplateShippingServiceCollectionFactory
    ) {
        $this->ebayTemplateShippingEntityCollectionFactory = $ebayTemplateShippingEntityCollectionFactory;
        $this->calculatedFactory = $calculatedFactory;
        $this->calculatedResource = $calculatedResource;
        $this->ebayTemplateShippingServiceCollectionFactory = $ebayTemplateShippingServiceCollectionFactory;
    }

    public function getComponent(): string
    {
        return \Ess\M2ePro\Helper\Component\Ebay::NICK;
    }

    public function getEntityName(): string
    {
        return 'Ebay_Template_Shipping';
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

        /** @var \Ess\M2ePro\Model\Ebay\Template\Shipping $item */
        foreach ($collection->getItems() as $item) {
            $preparedData = [
                'marketplace_id' => $item->getData('marketplace_id'),
                'title' => $item->getData('title'),
                'is_custom_template' => $item->getData('is_custom_template'),
                'country_mode' => $item->getData('country_mode'),
                'country_custom_value' => $item->getData('country_custom_value'),
                'country_custom_attribute' => $item->getData('country_custom_attribute'),
                'postal_code_mode' => $item->getData('postal_code_mode'),
                'postal_code_custom_value' => $item->getData('postal_code_custom_value'),
                'postal_code_custom_attribute' => $item->getData('postal_code_custom_attribute'),
                'address_mode' => $item->getData('address_mode'),
                'address_custom_value' => $item->getData('address_custom_value'),
                'address_custom_attribute' => $item->getData('address_custom_attribute'),
                'dispatch_time_mode' => $item->getData('dispatch_time_mode'),
                'dispatch_time_value' => $item->getData('dispatch_time_value'),
                'dispatch_time_attribute' => $item->getData('dispatch_time_attribute'),
                'local_shipping_rate_table' => $item->getData('local_shipping_rate_table'),
                'international_shipping_rate_table' => $item->getData('international_shipping_rate_table'),
                'local_shipping_mode' => $item->getData('local_shipping_mode'),
                'local_shipping_discount_promotional_mode' =>
                    $item->getData('local_shipping_discount_promotional_mode'),
                'local_shipping_discount_combined_profile_id' =>
                    $item->getData('local_shipping_discount_combined_profile_id'),
                'cash_on_delivery_cost' => $item->getData('cash_on_delivery_cost'),
                'international_shipping_mode' => $item->getData('international_shipping_mode'),
                'international_shipping_discount_promotional_mode' =>
                    $item->getData('international_shipping_discount_promotional_mode'),
                'international_shipping_discount_combined_profile_id' =>
                    $item->getData('international_shipping_discount_combined_profile_id'),
                'excluded_locations' => $item->getData('excluded_locations'),
                'cross_border_trade' => $item->getData('cross_border_trade'),
                'global_shipping_program' => $item->getData('global_shipping_program'),
                'update_date' => $item->getData('update_date'),
                'create_date' => $item->getData('create_date'),
                'calculated' => $this->addTemplateShippingCalculatedData((int)$item->getData('id')),
                'services' => $this->addServicesData((int)$item->getData('id')),
            ];

            yield new \Ess\M2ePro\Model\Servicing\Task\Analytics\Row($item->getData('id'), $preparedData);
        }
    }

    private function createCollection(): \Ess\M2ePro\Model\ResourceModel\Ebay\Template\Shipping\Collection
    {
        if (!$this->entityCollection) {
            $this->entityCollection = $this->ebayTemplateShippingEntityCollectionFactory->create();
        }

        return $this->entityCollection;
    }

    private function addTemplateShippingCalculatedData(int $id): ?array
    {
        $calculatedModel = $this->calculatedFactory->create();
        $this->calculatedResource->load($calculatedModel, $id);

        return $calculatedModel->getData();
    }

    private function addServicesData(int $id): array
    {
        $servicesEntities = $this->ebayTemplateShippingServiceCollectionFactory
            ->create()
            ->addFieldToFilter('template_shipping_id', $id)
            ->setOrder('priority', \Magento\Framework\Data\Collection::SORT_ORDER_ASC)
            ->toArray();

        return $this->unsetDataInRelatedItems($servicesEntities['items']);
    }

    private function unsetDataInRelatedItems(array $items): array
    {
        return array_map(
            static function ($el) {
                unset($el['template_shipping_id']);

                return $el;
            },
            $items
        );
    }
}
