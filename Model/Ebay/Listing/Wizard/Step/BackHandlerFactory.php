<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Listing\Wizard\Step;

use Ess\M2ePro\Model\Ebay\Listing\Wizard\StepDeclaration;

class BackHandlerFactory
{
    private \Magento\Framework\ObjectManagerInterface $objectManager;

    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function create(StepDeclaration $step): BackHandlerInterface
    {
        $handler = $this->objectManager->create($step->getBackHandlerClass());
        if (!$handler instanceof BackHandlerInterface) {
            throw new \LogicException('Back handler is not valid.');
        }

        return $handler;
    }
}
