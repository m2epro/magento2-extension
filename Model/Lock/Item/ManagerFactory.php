<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Lock\Item;

class ManagerFactory
{
    /** @var \Magento\Framework\ObjectManagerInterface */
    private $objectManager;

    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function create(string $nick): \Ess\M2ePro\Model\Lock\Item\Manager
    {
        return $this->objectManager->create(
            \Ess\M2ePro\Model\Lock\Item\Manager::class,
            [
                'nick' => $nick,
            ]
        );
    }
}
