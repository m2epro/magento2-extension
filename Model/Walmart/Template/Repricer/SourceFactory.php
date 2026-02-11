<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Walmart\Template\Repricer;

class SourceFactory
{
    private \Magento\Framework\ObjectManagerInterface $objectManager;

    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function create(
        \Ess\M2ePro\Model\Walmart\Template\Repricer $templateModel,
        \Ess\M2ePro\Model\Magento\Product $magentoProduct
    ): Source {
        return $this->objectManager->create(Source::class, [
            'templateModel' => $templateModel,
            'magentoProduct' => $magentoProduct,
        ]);
    }
}
