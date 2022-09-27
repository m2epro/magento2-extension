<?php

namespace Ess\M2ePro\Model\ChangeTracker\Common\PriceCondition;

class PriceConditionFactory
{
    /** @var \Magento\Framework\ObjectManagerInterface */
    private $objectManager;

    /**
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        $this->objectManager = $objectManager;
    }

    /**
     * @param string $channel
     *
     * @return \Ess\M2ePro\Model\ChangeTracker\Common\PriceCondition\AbstractPriceCondition
     */
    public function create(string $channel): AbstractPriceCondition
    {
        $arguments = ['channel' => $channel];

        if ($channel === 'ebay') {
            return $this->objectManager->create(Ebay::class, $arguments);
        }

        if ($channel === 'amazon') {
            return $this->objectManager->create(Amazon::class, $arguments);
        }

        if ($channel === 'walmart') {
            return $this->objectManager->create(Walmart::class, $arguments);
        }

        throw new \RuntimeException('Undefined channel name ' . $channel);
    }
}
