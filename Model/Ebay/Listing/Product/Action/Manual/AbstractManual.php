<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Listing\Product\Action\Manual;

abstract class AbstractManual
{
    /** @var int */
    private $logsActionId;
    /** @var \Ess\M2ePro\Model\Listing\Product\LockManagerFactory */
    private $lockManagerFactory;

    public function __construct(
        \Ess\M2ePro\Model\Listing\Product\LockManagerFactory $lockManagerFactory
    ) {
        $this->lockManagerFactory = $lockManagerFactory;
    }

    // ----------------------------------------

    abstract protected function getAction(): int;

    /**
     * @param \Ess\M2ePro\Model\Listing\Product[] $listingsProducts
     *
     * @return array
     */
    abstract protected function prepareOrFilterProducts(array $listingsProducts): array;

    /**
     * @param \Ess\M2ePro\Model\Listing\Product[] $listingsProducts
     * @param array $params
     *
     * @return Result
     */
    abstract protected function processListingsProducts(array $listingsProducts, array $params): Result;

    /**
     * @param \Ess\M2ePro\Model\Listing\Product[] $listingsProducts
     * @param array $params
     * @param int $logsActionId
     * @return Result
     */
    public function process(array $listingsProducts, array $params, int $logsActionId): Result
    {
        $this->logsActionId = $logsActionId;

        $listingsProducts = $this->checkLocking($listingsProducts);
        if (empty($listingsProducts)) {
            return Result::createError($this->getLogActionId());
        }

        $listingsProducts = $this->prepareOrFilterProducts($listingsProducts);

        if (empty($listingsProducts)) {
            return Result::createSuccess($this->getLogActionId());
        }

        return $this->processListingsProducts($listingsProducts, $params);
    }

    /**
     * @param \Ess\M2ePro\Model\Listing\Product[] $listingsProducts
     *
     * @return \Ess\M2ePro\Model\Listing\Product[]
     */
    private function checkLocking(array $listingsProducts): array
    {
        $result = [];
        foreach ($listingsProducts as $listingProduct) {
            $lockManager = $this->lockManagerFactory->create();
            $lockManager->setListingProduct($listingProduct)
                        ->setInitiator(\Ess\M2ePro\Helper\Data::INITIATOR_USER)
                        ->setLogsActionId($this->logsActionId)
                        ->setLogsAction($this->getLogsAction());

            if ($lockManager->checkLocking()) {
                continue;
            }

            $result[] = $listingProduct;
        }

        return $result;
    }

    private function getLogsAction(): int
    {
        switch ($this->getAction()) {
            case \Ess\M2ePro\Model\Listing\Product::ACTION_LIST:
                return \Ess\M2ePro\Model\Listing\Log::ACTION_LIST_PRODUCT_ON_COMPONENT;

            case \Ess\M2ePro\Model\Listing\Product::ACTION_RELIST:
                return \Ess\M2ePro\Model\Listing\Log::ACTION_RELIST_PRODUCT_ON_COMPONENT;

            case \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE:
                return \Ess\M2ePro\Model\Listing\Log::ACTION_REVISE_PRODUCT_ON_COMPONENT;

            case \Ess\M2ePro\Model\Listing\Product::ACTION_STOP:
                return \Ess\M2ePro\Model\Listing\Log::ACTION_STOP_PRODUCT_ON_COMPONENT;
        }

        throw new \Ess\M2ePro\Model\Exception\Logic('Unknown action.', ['action' => $this->getAction()]);
    }

    // ----------------------------------------

    protected function getLogActionId(): int
    {
        return $this->logsActionId;
    }
}
