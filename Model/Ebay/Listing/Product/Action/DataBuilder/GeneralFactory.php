<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Listing\Product\Action\DataBuilder;

class GeneralFactory
{
    /** @var \Magento\Framework\ObjectManagerInterface */
    private $objectManager;

    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function create(\Ess\M2ePro\Model\Listing\Product $listingProduct): General
    {
        /** @var General $dataBuilder */
        $dataBuilder = $this->objectManager->create(General::class);
        $dataBuilder->setListingProduct($listingProduct);

        return $dataBuilder;
    }
}
