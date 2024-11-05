<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\ResourceModel\Ebay\Template\Category;

use Ess\M2ePro\Model\ResourceModel\Ebay\Template\Category\Collection as EbayTemplateCategoryCollection;

class CollectionFactory
{
    private \Magento\Framework\ObjectManagerInterface $objectManager;

    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function create(): EbayTemplateCategoryCollection
    {
        return $this->objectManager->create(EbayTemplateCategoryCollection::class);
    }
}
