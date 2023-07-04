<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Servicing\Task\Analytics\Collector\Ebay;

use Ess\M2ePro\Model\ResourceModel\Ebay\Template\Category\CollectionFactory as EbayTemplateCategoryCollectionFactory;

class EbayTemplateCategory implements \Ess\M2ePro\Model\Servicing\Task\Analytics\CollectorInterface
{
    /** @var EbayTemplateCategoryCollectionFactory */
    private $ebayTemplateCategoryEntityCollectionFactory;
    /** @var \Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Product */
    private $ebayListingProductResource;
    /** @var \Ess\M2ePro\Model\ResourceModel\Ebay\Template\Category\Collection|null  */
    private $entityCollection;

    public function __construct(
        EbayTemplateCategoryCollectionFactory $ebayTemplateCategoryEntityCollectionFactory,
        \Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Product $ebayListingProductResource
    ) {
        $this->ebayTemplateCategoryEntityCollectionFactory = $ebayTemplateCategoryEntityCollectionFactory;
        $this->ebayListingProductResource = $ebayListingProductResource;
    }

    public function getComponent(): string
    {
        return \Ess\M2ePro\Helper\Component\Ebay::NICK;
    }

    public function getEntityName(): string
    {
        return 'Ebay_Template_Category';
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

        /** @var \Ess\M2ePro\Model\Ebay\Template\Category $item */
        foreach ($collection->getItems() as $item) {
            $preparedData = [
                'marketplace_id' => $item->getData('marketplace_id'),
                'is_custom_template' => $item->getData('is_custom_template'),
                'category_id' => $item->getData('category_id'),
                'category_path' => $item->getData('category_path'),
                'category_mode' => $item->getData('category_mode'),
                'category_attribute' => $item->getData('category_attribute'),
                'update_date' => $item->getData('update_date'),
                'create_date' => $item->getData('create_date'),

            ];

            yield new \Ess\M2ePro\Model\Servicing\Task\Analytics\Row($item->getData('id'), $preparedData);
        }
    }

    private function createCollection(): \Ess\M2ePro\Model\ResourceModel\Ebay\Template\Category\Collection
    {
        if (!$this->entityCollection) {
            $this->entityCollection = $this->ebayTemplateCategoryEntityCollectionFactory->create();

            $productsTableName = $this->ebayListingProductResource->getMainTable();
            $joinCondition = [
                'list_product.template_category_id = main_table.id',
                'list_product.template_category_secondary_id = main_table.id',
            ];

            $joinCondition = implode(' OR ', $joinCondition);
            $this->entityCollection->getSelect()->joinInner(
                ['list_product' => $productsTableName],
                $joinCondition,
                []
            )->group('main_table.id');
        }

        return $this->entityCollection;
    }
}
