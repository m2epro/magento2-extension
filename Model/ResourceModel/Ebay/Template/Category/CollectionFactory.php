<?php

namespace Ess\M2ePro\Model\ResourceModel\Ebay\Template\Category;

use Ess\M2ePro\Model\ResourceModel\Ebay\Template\Category\Collection as EbayTemplateCategoryCollection;

class CollectionFactory
{
    /** @var \Magento\Framework\ObjectManagerInterface */
    private $objectManager;

    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function create(array $data = []): EbayTemplateCategoryCollection
    {
        return $this->objectManager->create(EbayTemplateCategoryCollection::class, $data);
    }
}
