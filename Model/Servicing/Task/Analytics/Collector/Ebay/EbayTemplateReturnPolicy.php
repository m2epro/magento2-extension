<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Servicing\Task\Analytics\Collector\Ebay;

use Ess\M2ePro\Model\ResourceModel\Ebay\Template\ReturnPolicy\CollectionFactory as ReturnPolicyCollectionFactory;

class EbayTemplateReturnPolicy implements \Ess\M2ePro\Model\Servicing\Task\Analytics\CollectorInterface
{
    /** @var ReturnPolicyCollectionFactory */
    private $ebayTemplateReturnPolicyEntityCollectionFactory;
    /** @var \Ess\M2ePro\Model\ResourceModel\Ebay\Template\ReturnPolicy\Collection|null  */
    private $entityCollection;

    public function __construct(
        ReturnPolicyCollectionFactory $ebayTemplateReturnPolicyEntityCollectionFactory
    ) {
        $this->ebayTemplateReturnPolicyEntityCollectionFactory = $ebayTemplateReturnPolicyEntityCollectionFactory;
    }

    public function getComponent(): string
    {
        return \Ess\M2ePro\Helper\Component\Ebay::NICK;
    }

    public function getEntityName(): string
    {
        return 'Ebay_Template_ReturnPolicy';
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

        /** @var \Ess\M2ePro\Model\Ebay\Template\ReturnPolicy $item */
        foreach ($collection->getItems() as $item) {
            $preparedData = [
                'marketplace_id' => $item->getData('marketplace_id'),
                'title' => $item->getData('title'),
                'is_custom_template' => $item->getData('is_custom_template'),
                'accepted' => $item->getData('accepted'),
                'option' => $item->getData('option'),
                'within' => $item->getData('within'),
                'shipping_cost' => $item->getData('shipping_cost'),
                'international_accepted' => $item->getData('international_accepted'),
                'international_option' => $item->getData('international_option'),
                'international_within' => $item->getData('international_within'),
                'international_shipping_cost' => $item->getData('international_shipping_cost'),
                'description' => $item->getData('description'),
                'update_date' => $item->getData('update_date'),
                'create_date' => $item->getData('create_date'),
            ];

            yield new \Ess\M2ePro\Model\Servicing\Task\Analytics\Row($item->getData('id'), $preparedData);
        }
    }

    private function createCollection(): \Ess\M2ePro\Model\ResourceModel\Ebay\Template\ReturnPolicy\Collection
    {
        if (!$this->entityCollection) {
            $this->entityCollection = $this->ebayTemplateReturnPolicyEntityCollectionFactory->create();
        }

        return $this->entityCollection;
    }
}
