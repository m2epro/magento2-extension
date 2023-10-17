<?php

namespace Ess\M2ePro\Model\Ebay;

use Magento\Framework\ObjectManagerInterface;

class ListingFactory
{
    /** @var \Magento\Framework\ObjectManagerInterface */
    private $objectManager;

    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * @return \Ess\M2ePro\Model\Ebay\Listing
     */
    public function create(): Listing
    {
        return $this->objectManager->create(Listing::class);
    }
}
