<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Amazon\Template\SellingFormat;

class Repository
{
    private \Ess\M2ePro\Model\ResourceModel\Template\SellingFormat\CollectionFactory $collectionFactory;

    public function __construct(\Ess\M2ePro\Model\ResourceModel\Template\SellingFormat\CollectionFactory $collectionFactory)
    {
        $this->collectionFactory = $collectionFactory;
    }

    public function get(int $id): \Ess\M2ePro\Model\Template\SellingFormat
    {
        $value = $this->find($id);
        if ($value === null) {
            throw new \Ess\M2ePro\Model\Exception\EntityNotFound("Not found Selling Policy with ID $id.");
        }

        return $value;
    }

    public function find(int $id): ?\Ess\M2ePro\Model\Template\SellingFormat
    {
        $collection = $this->collectionFactory->createWithAmazonChildMode();
        $collection->addFieldToFilter('id', ['eq' => $id]);

        /** @var \Ess\M2ePro\Model\Template\SellingFormat $value */
        $value = $collection->getFirstItem();
        if ($value->isObjectNew()) {
            return null;
        }

        return $value;
    }
}
