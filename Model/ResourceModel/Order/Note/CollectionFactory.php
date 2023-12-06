<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Order\Note;

use Magento\Framework\ObjectManagerInterface;

class CollectionFactory
{
    /** @var \Magento\Framework\ObjectManagerInterface */
    private $objectManager;

    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function create(): \Ess\M2ePro\Model\ResourceModel\Order\Note\Collection
    {
        return $this->objectManager
            ->create(\Ess\M2ePro\Model\ResourceModel\Order\Note\Collection::class);
    }
}
