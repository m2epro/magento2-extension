<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Listing\Product\Identifiers;

use Ess\M2ePro\Model\Amazon\Listing\Product\Identifiers;
use Magento\Framework\ObjectManagerInterface;

class Factory
{
    /** @var ObjectManagerInterface */
    private $objectManager;

    /**
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     */
    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * @param \Ess\M2ePro\Model\Magento\Product $magentoProduct
     *
     * @return \Ess\M2ePro\Model\Amazon\Listing\Product\Identifiers
     */
    public function create(\Ess\M2ePro\Model\Magento\Product $magentoProduct): Identifiers
    {
        return $this->objectManager->create(
            Identifiers::class,
            ['magentoProduct' => $magentoProduct]
        );
    }
}
