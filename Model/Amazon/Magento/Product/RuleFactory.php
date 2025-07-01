<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Amazon\Magento\Product;

class RuleFactory
{
    /** @var \Magento\Framework\ObjectManagerInterface */
    private $objectManager;

    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function create(string $prefix, ?int $storeId = null): Rule
    {
        return $this->objectManager->create(Rule::class)->setData(
            [
                'prefix' => $prefix,
                'store_id' => $storeId,
            ]
        );
    }
}
