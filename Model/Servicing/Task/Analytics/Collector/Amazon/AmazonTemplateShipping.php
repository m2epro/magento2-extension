<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Servicing\Task\Analytics\Collector\Amazon;

use Ess\M2ePro\Model\ResourceModel\Amazon\Template\Shipping\CollectionFactory as TemplateShippingCollectionFactory;

class AmazonTemplateShipping implements \Ess\M2ePro\Model\Servicing\Task\Analytics\CollectorInterface
{
    /** @var TemplateShippingCollectionFactory */
    private $amazonTemplateShippingEntityCollectionFactory;
    /** @var \Ess\M2ePro\Model\ResourceModel\Amazon\Template\Shipping\Collection|null  */
    private $entityCollection;

    public function __construct(
        TemplateShippingCollectionFactory $amazonTemplateShippingEntityCollectionFactory
    ) {
        $this->amazonTemplateShippingEntityCollectionFactory = $amazonTemplateShippingEntityCollectionFactory;
    }

    public function getComponent(): string
    {
        return \Ess\M2ePro\Helper\Component\Amazon::NICK;
    }

    public function getEntityName(): string
    {
        return 'Amazon_Template_Shipping';
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

        /** @var \Ess\M2ePro\Model\Amazon\Template\Shipping $item */
        foreach ($collection->getItems() as $item) {
            $itemData = $item->getData();
            $preparedData = [
                'title' => $item->getData('title'),
                'account_id' => $item->getData('account_id'),
                'marketplace_id' => $item->getData('marketplace_id'),
                'template_id' => $item->getData('template_id'),
                'update_date' => $item->getData('update_date'),
                'create_date' => $item->getData('create_date'),
            ];

            yield new \Ess\M2ePro\Model\Servicing\Task\Analytics\Row($item->getData('id'), $preparedData);
        }
    }

    private function createCollection(): \Ess\M2ePro\Model\ResourceModel\Amazon\Template\Shipping\Collection
    {
        if (!$this->entityCollection) {
            $this->entityCollection = $this->amazonTemplateShippingEntityCollectionFactory->create();
        }

        return $this->entityCollection;
    }
}
