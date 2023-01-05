<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Listing\Product\Action\Manual\Realtime;

class StopAndRemoveAction extends AbstractRealtime
{
    /** @var \Ess\M2ePro\Model\Listing\Product\RemoveHandlerFactory */
    private $removeHandlerFactory;

    public function __construct(
        \Ess\M2ePro\Model\Listing\Product\RemoveHandlerFactory $removeHandlerFactory,
        \Ess\M2ePro\Model\Ebay\Connector\Item\DispatcherFactory $connectionDispatcherFactory,
        \Ess\M2ePro\Helper\Server\Maintenance $serverHelper,
        \Ess\M2ePro\Model\Listing\Product\LockManagerFactory $lockManagerFactory
    ) {
        parent::__construct($connectionDispatcherFactory, $serverHelper, $lockManagerFactory);
        $this->removeHandlerFactory = $removeHandlerFactory;
    }

    protected function getAction(): int
    {
        return \Ess\M2ePro\Model\Listing\Product::ACTION_STOP;
    }

    protected function prepareOrFilterProducts(array $listingsProducts): array
    {
        $result = [];
        foreach ($listingsProducts as $listingProduct) {
            if ($listingProduct->isStoppable()) {
                $result[] = $listingProduct;

                continue;
            }

            $this->removeHandlerFactory->create($listingProduct)
                                       ->process();
        }

        return $result;
    }
}
