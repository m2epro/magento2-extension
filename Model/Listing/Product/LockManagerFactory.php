<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Listing\Product;

class LockManagerFactory
{
    /** @var \Magento\Framework\ObjectManagerInterface */
    private $objectManager;

    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function create(): \Ess\M2ePro\Model\Listing\Product\LockManager
    {
        return $this->objectManager->create(\Ess\M2ePro\Model\Listing\Product\LockManager::class);
    }
}
