<?php

namespace Ess\M2ePro\Model\Ebay\Category\Specific\Validation;

class ResultFactory
{
    /** @var \Magento\Framework\ObjectManagerInterface */
    private $objectManager;

    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function create(array $data = []): Result
    {
        return $this->objectManager->create(Result::class, $data);
    }
}
