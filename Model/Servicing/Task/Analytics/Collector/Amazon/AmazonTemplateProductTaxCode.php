<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Servicing\Task\Analytics\Collector\Amazon;

use Ess\M2ePro\Model\ResourceModel\Amazon\Template\ProductTaxCode\CollectionFactory as ProductTaxCodeCollectionFactory;

class AmazonTemplateProductTaxCode implements \Ess\M2ePro\Model\Servicing\Task\Analytics\CollectorInterface
{
    /** @var ProductTaxCodeCollectionFactory */
    private $amazonTemplateProductTaxCodeCollectionFactory;
    /** @var \Ess\M2ePro\Model\ResourceModel\Amazon\Template\ProductTaxCode\Collection|null  */
    private $entityCollection;

    public function __construct(
        ProductTaxCodeCollectionFactory $amazonTemplateProductTaxCodeCollectionFactory
    ) {
        $this->amazonTemplateProductTaxCodeCollectionFactory = $amazonTemplateProductTaxCodeCollectionFactory;
    }

    public function getComponent(): string
    {
        return \Ess\M2ePro\Helper\Component\Amazon::NICK;
    }

    public function getEntityName(): string
    {
        return 'Amazon_Template_ProductTaxCode';
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

        /** @var \Ess\M2ePro\Model\Amazon\Template\ProductTaxCode $item */
        foreach ($collection->getItems() as $item) {
            $preparedData = [
                'title' => $item->getData('title'),
                'product_tax_code_mode' => $item->getData('product_tax_code_mode'),
                'product_tax_code_attribute' => $item->getData('product_tax_code_attribute'),
                'update_date' => $item->getData('update_date'),
                'create_date' => $item->getData('create_date'),
            ];

            yield new \Ess\M2ePro\Model\Servicing\Task\Analytics\Row($item->getData('id'), $preparedData);
        }
    }

    private function createCollection(): \Ess\M2ePro\Model\ResourceModel\Amazon\Template\ProductTaxCode\Collection
    {
        if (!$this->entityCollection) {
            $this->entityCollection = $this->amazonTemplateProductTaxCodeCollectionFactory->create();
        }

        return $this->entityCollection;
    }
}
