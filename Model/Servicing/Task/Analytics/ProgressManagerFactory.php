<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Servicing\Task\Analytics;

class ProgressManagerFactory
{
    /** @var \Ess\M2ePro\Model\Servicing\Task\Analytics\Registry */
    private $registry;

    public function __construct(Registry $registry)
    {
        $this->registry = $registry;
    }

    public function create(string $collectorId): ProgressManager
    {
        return new ProgressManager($collectorId, $this->registry);
    }
}
