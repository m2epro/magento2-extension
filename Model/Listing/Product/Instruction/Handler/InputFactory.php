<?php

namespace Ess\M2ePro\Model\Listing\Product\Instruction\Handler;

class InputFactory
{
    /** @var \Magento\Framework\ObjectManagerInterface */
    private $objectManager;

    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function create(): Input
    {
        return $this->objectManager->create(Input::class);
    }
}
