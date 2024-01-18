<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Amazon\Listing;

use Magento\Framework\ObjectManagerInterface;

class LogFactory
{
    /** @var \Magento\Framework\ObjectManagerInterface */
    protected $objectManager;

    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function create(array $data = []): Log
    {
        return $this->objectManager->create(Log::class, $data);
    }
}
