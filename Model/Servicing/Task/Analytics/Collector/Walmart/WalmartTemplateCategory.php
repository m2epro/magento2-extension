<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Servicing\Task\Analytics\Collector\Walmart;

use Ess\M2ePro\Model\ResourceModel\Walmart\Template\Category\CollectionFactory as TemplateCategoryCollectionFactory;

class WalmartTemplateCategory implements \Ess\M2ePro\Model\Servicing\Task\Analytics\CollectorInterface
{
    /** @var TemplateCategoryCollectionFactory */
    private $walmartTemplateCategoryEntityCollectionFactory;
    /** @var \Ess\M2ePro\Model\ResourceModel\Walmart\Template\Category\Collection|null  */
    private $entityCollection;

    public function __construct(
        TemplateCategoryCollectionFactory $walmartTemplateCategoryEntityCollectionFactory
    ) {
        $this->walmartTemplateCategoryEntityCollectionFactory = $walmartTemplateCategoryEntityCollectionFactory;
    }

    public function getComponent(): string
    {
        return \Ess\M2ePro\Helper\Component\Walmart::NICK;
    }

    public function getEntityName(): string
    {
        return 'Walmart_Template_Category';
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

        /** @var \Ess\M2ePro\Model\Walmart\Template\Category $item */
        foreach ($collection->getItems() as $item) {
            $preparedData = [
                'title' => $item->getData('title'),
                'marketplace_id' => $item->getData('marketplace_id'),
                'product_data_nick' => $item->getData('product_data_nick'),
                'category_path' => $item->getData('category_path'),
                'browsenode_id' => $item->getData('browsenode_id'),
                'update_date' => $item->getData('update_date'),
                'create_date' => $item->getData('create_date'),
            ];

            yield new \Ess\M2ePro\Model\Servicing\Task\Analytics\Row($item->getData('id'), $preparedData);
        }
    }

    private function createCollection(): \Ess\M2ePro\Model\ResourceModel\Walmart\Template\Category\Collection
    {
        if (!$this->entityCollection) {
            $this->entityCollection = $this->walmartTemplateCategoryEntityCollectionFactory->create();

            $productsTableName = $this->entityCollection->getResource()->getTable('m2epro_walmart_listing_product');
            $this->entityCollection->getSelect()->join(
                ['list_product' => $productsTableName],
                'list_product.template_category_id = main_table.id',
                []
            )->group('main_table.id');
        }

        return $this->entityCollection;
    }
}
