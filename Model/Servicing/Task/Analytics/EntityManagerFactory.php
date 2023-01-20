<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Servicing\Task\Analytics;

class EntityManagerFactory
{
    /** @var \Magento\Framework\ObjectManagerInterface */
    private $objectManager;

    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function create(array $params): \Ess\M2ePro\Model\Servicing\Task\Analytics\EntityManager
    {
        return $this->objectManager->create(
            \Ess\M2ePro\Model\Servicing\Task\Analytics\EntityManager::class,
            ['params' => $params]
        );
    }
}
