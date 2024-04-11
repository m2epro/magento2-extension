<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Magento\Product;

class ImageFactory
{
    /** @var \Magento\Framework\ObjectManagerInterface */
    private $objectManager;

    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function create(): Image
    {
        return $this->objectManager->create(Image::class);
    }
}
