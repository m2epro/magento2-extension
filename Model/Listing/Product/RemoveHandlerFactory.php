<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Listing\Product;

class RemoveHandlerFactory
{
    /** @var \Magento\Framework\ObjectManagerInterface */
    private $objectManager;

    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function create(
        \Ess\M2ePro\Model\Listing\Product $listingProduct
    ): \Ess\M2ePro\Model\Listing\Product\RemoveHandler {
        /** @var \Ess\M2ePro\Model\Listing\Product\RemoveHandler $handler */
        $handler = $this->objectManager->create(\Ess\M2ePro\Model\Listing\Product\RemoveHandler::class);

        $handler->setListingProduct($listingProduct);

        return $handler;
    }
}
