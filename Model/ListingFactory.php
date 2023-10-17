<?php

namespace Ess\M2ePro\Model;

class ListingFactory
{
    /** @var \Magento\Framework\ObjectManagerInterface */
    private $objectManager;

    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function create(): Listing
    {
        return $this->objectManager->create(Listing::class);
    }
}
