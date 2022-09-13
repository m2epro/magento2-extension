<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Search\Custom;

use Magento\Framework\ObjectManagerInterface;
use Ess\M2ePro\Model\Amazon\Search\Custom;

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
     * @param string $query
     * @param \Ess\M2ePro\Model\Listing\Product $listingProduct
     *
     * @return Custom
     */
    public function create(string $query, \Ess\M2ePro\Model\Listing\Product $listingProduct): Custom
    {
        return $this->objectManager->create(
            Custom::class,
            [
                'query'          => $query,
                'listingProduct' => $listingProduct,
            ]
        );
    }
}
