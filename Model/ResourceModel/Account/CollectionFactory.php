<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Account;

class CollectionFactory
{
    /** @var \Magento\Framework\ObjectManagerInterface */
    private $objectManager;

    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * @param array $data
     *
     * @return \Ess\M2ePro\Model\ResourceModel\Account\Collection
     */
    public function create(array $data = []): Collection
    {
        return $this->objectManager->create(Collection::class, $data);
    }

    /**
     * @return \Ess\M2ePro\Model\ResourceModel\Account\Collection
     */
    public function createWithEbayChildMode(): Collection
    {
        return $this->createWithChildMode(\Ess\M2ePro\Helper\Component\Ebay::NICK);
    }

    /**
     * @return \Ess\M2ePro\Model\ResourceModel\Account\Collection
     */
    public function createWithAmazonChildMode(): Collection
    {
        return $this->createWithChildMode(\Ess\M2ePro\Helper\Component\Amazon::NICK);
    }

    /**
     * @return \Ess\M2ePro\Model\ResourceModel\Account\Collection
     */
    public function createWithWalmartChildMode(): Collection
    {
        return $this->createWithChildMode(\Ess\M2ePro\Helper\Component\Walmart::NICK);
    }

    /**
     * @param string $componentMode
     *
     * @return \Ess\M2ePro\Model\ResourceModel\Account\Collection
     */
    private function createWithChildMode(string $componentMode): Collection
    {
        return $this->objectManager->create(Collection::class, [
            'childMode' => $componentMode,
        ]);
    }
}
