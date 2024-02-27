<?php

declare(strict_types=1);

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Template\ProductType;

class IsUniqueTitle extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Template\ProductType
{
    /** @var \Ess\M2ePro\Model\ResourceModel\Amazon\Template\ProductType\CollectionFactory */
    private $productTypeCollectionFactory;
    /** @var \Ess\M2ePro\Model\ResourceModel\Amazon\Dictionary\ProductType */
    private $dictionaryProductTypeResource;

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Amazon\Template\ProductType\CollectionFactory $productTypeCollectionFactory,
        \Ess\M2ePro\Model\ResourceModel\Amazon\Dictionary\ProductType $dictionaryProductTypeResource,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($amazonFactory, $context);
        $this->productTypeCollectionFactory = $productTypeCollectionFactory;
        $this->dictionaryProductTypeResource = $dictionaryProductTypeResource;
    }

    public function execute(): \Magento\Framework\Controller\ResultInterface
    {
        $title = $this->getRequest()->getParam('title');
        $marketplaceId = $this->getRequest()->getParam('marketplace_id');

        if (empty($title) || empty($marketplaceId)) {
            throw new \Ess\M2ePro\Model\Exception\Logic('You should provide correct parameters.');
        }

        $this->setJsonContent([
            'result' => $this->isUniqueTitle($title, (int)$marketplaceId)
        ]);

        return $this->getResult();
    }

    private function isUniqueTitle(string $title, int $marketplaceId): bool
    {
        $collection = $this->productTypeCollectionFactory->create();
        $collection->joinInner(
            ['dictionary' => $this->dictionaryProductTypeResource->getMainTable()],
            'dictionary.id = dictionary_product_type_id',
            ['marketplace_id' => 'marketplace_id']
        );

        $collection->addFieldToFilter('main_table.title', ['eq' => $title]);
        $collection->addFieldToFilter('dictionary.marketplace_id', ['eq' => $marketplaceId]);

        return $collection->getSize() === 0;
    }
}
