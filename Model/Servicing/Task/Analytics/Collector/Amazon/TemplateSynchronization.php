<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Servicing\Task\Analytics\Collector\Amazon;

use Ess\M2ePro\Model\ResourceModel\Template\Synchronization\CollectionFactory as SynchronizationCollectionFactory;

class TemplateSynchronization implements \Ess\M2ePro\Model\Servicing\Task\Analytics\CollectorInterface
{
    /** @var SynchronizationCollectionFactory */
    private $templateSynchronizationEntityCollectionFactory;
    /** @var \Ess\M2ePro\Model\ResourceModel\Template\Synchronization\Collection|null  */
    private $entityCollection;

    public function __construct(SynchronizationCollectionFactory $templateSynchronizationEntityCollectionFactory)
    {
        $this->templateSynchronizationEntityCollectionFactory = $templateSynchronizationEntityCollectionFactory;
    }

    public function getComponent(): string
    {
        return \Ess\M2ePro\Helper\Component\Amazon::NICK;
    }

    public function getEntityName(): string
    {
        return 'Template_Synchronization';
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

        /** @var \Ess\M2ePro\Model\Template\Synchronization $item */
        foreach ($collection->getItems() as $item) {
            /** @var \Ess\M2ePro\Model\Amazon\Template\Synchronization $childItem */
            $childItem = $item->getChildObject();
            $preparedData = [
                'title' => $item->getData('title'),
                'update_date' => $item->getData('update_date'),
                'create_date' => $item->getData('create_date'),
                'list_mode' => $childItem->getData('list_mode'),
                'list_status_enabled' => $childItem->getData('list_status_enabled'),
                'list_is_in_stock' => $childItem->getData('list_is_in_stock'),
                'list_qty_calculated' => $childItem->getData('list_qty_calculated'),
                'list_qty_calculated_value' => $childItem->getData('list_qty_calculated_value'),
                'list_advanced_rules_mode' => $childItem->getData('list_advanced_rules_mode'),
                'list_advanced_rules_filters' => $childItem->getData('list_advanced_rules_filters'),
                'revise_update_qty' => $childItem->getData('revise_update_qty'),
                'revise_update_qty_max_applied_value_mode' =>
                    $childItem->getData('revise_update_qty_max_applied_value_mode'),
                'revise_update_qty_max_applied_value' =>
                    $childItem->getData('revise_update_qty_max_applied_value'),
                'revise_update_price' => $childItem->getData('revise_update_price'),
                'revise_update_details' => $childItem->getData('revise_update_details'),
                'revise_update_images' => $childItem->getData('revise_update_images'),
                'relist_mode' => $childItem->getData('relist_mode'),
                'relist_filter_user_lock' => $childItem->getData('relist_filter_user_lock'),
                'relist_status_enabled' => $childItem->getData('relist_status_enabled'),
                'relist_is_in_stock' => $childItem->getData('relist_is_in_stock'),
                'relist_qty_calculated' => $childItem->getData('relist_qty_calculated'),
                'relist_qty_calculated_value' => $childItem->getData('relist_qty_calculated_value'),
                'relist_advanced_rules_mode' => $childItem->getData('relist_advanced_rules_mode'),
                'relist_advanced_rules_filters' => $childItem->getData('relist_advanced_rules_filters'),
                'stop_mode' => $childItem->getData('stop_mode'),
                'stop_status_disabled' => $childItem->getData('stop_status_disabled'),
                'stop_out_off_stock' => $childItem->getData('stop_out_off_stock'),
                'stop_qty_calculated' => $childItem->getData('stop_qty_calculated'),
                'stop_qty_calculated_value' => $childItem->getData('stop_qty_calculated_value'),
                'stop_advanced_rules_mode' => $childItem->getData('stop_advanced_rules_mode'),
                'stop_advanced_rules_filters' => $childItem->getData('stop_advanced_rules_filters'),
            ];

            yield new \Ess\M2ePro\Model\Servicing\Task\Analytics\Row($item->getData('id'), $preparedData);
        }
    }

    private function createCollection(): \Ess\M2ePro\Model\ResourceModel\Template\Synchronization\Collection
    {
        if (!$this->entityCollection) {
            $this->entityCollection = $this->templateSynchronizationEntityCollectionFactory
                ->createWithAmazonChildMode();
        }

        return $this->entityCollection;
    }
}
