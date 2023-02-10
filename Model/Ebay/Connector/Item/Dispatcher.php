<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Connector\Item;

class Dispatcher extends \Ess\M2ePro\Model\AbstractModel
{
    /** @var int */
    private $logsActionId;

    private $activeRecordFactory;
    private $ebayFactory;
    /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Log */
    private $listingLogResource;
    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;
    /** @var \Ess\M2ePro\Helper\Module\Exception */
    private $exceptionHelper;
    /** @var \Ess\M2ePro\Model\Ebay\Connector\DispatcherFactory */
    private $dispatcherFactory;

    public function __construct(
        \Ess\M2ePro\Model\Ebay\Connector\DispatcherFactory $dispatcherFactory,
        \Ess\M2ePro\Model\ResourceModel\Listing\Log $listingLogResource,
        \Ess\M2ePro\Helper\Data $dataHelper,
        \Ess\M2ePro\Helper\Module\Exception $exceptionHelper,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    ) {
        parent::__construct($helperFactory, $modelFactory);
        $this->activeRecordFactory = $activeRecordFactory;
        $this->ebayFactory = $ebayFactory;
        $this->listingLogResource = $listingLogResource;
        $this->dataHelper = $dataHelper;
        $this->exceptionHelper = $exceptionHelper;
        $this->dispatcherFactory = $dispatcherFactory;
    }

    // ----------------------------------------

    /**
     * @param int $action
     * @param array|\Ess\M2ePro\Model\Listing\Product $products
     * @param array $params
     *
     * @return int
     */
    public function process($action, $products, array $params = [])
    {
        $params = array_merge([
            'status_changer' => \Ess\M2ePro\Model\Listing\Product::STATUS_CHANGER_UNKNOWN,
        ], $params);

        if (empty($params['logs_action_id'])) {
            $this->logsActionId = $this->listingLogResource->getNextActionId();
            $params['logs_action_id'] = $this->logsActionId;
        } else {
            $this->logsActionId = $params['logs_action_id'];
        }

        $isRealTime = !empty($params['is_realtime']);

        $products = $this->prepareProducts($products);
        $sortedProducts = $this->sortProductsByAccountsMarketplaces($products);

        return $this->processAccountsMarketplaces($sortedProducts, $action, $isRealTime, $params);
    }

    public function getLogsActionId(): int
    {
        return (int)$this->logsActionId;
    }

    // ----------------------------------------

    /**
     * @param array $sortedProducts
     * @param string $action
     * @param bool $isRealTime
     * @param array $params
     *
     * @return int
     * @throws \LogicException
     */
    private function processAccountsMarketplaces(
        array $sortedProducts,
        $action,
        bool $isRealTime,
        array $params
    ): int {
        $results = [];

        foreach ($sortedProducts as $accountId => $accountProducts) {
            /** @var \Ess\M2ePro\Model\Listing\Product[] $products */
            foreach ($accountProducts as $marketplaceId => $products) {
                if (empty($products)) {
                    continue;
                }

                try {
                    $result = $this->processProducts($products, $action, $isRealTime, $params);
                } catch (\Throwable $exception) {
                    foreach ($products as $product) {
                        $this->logListingProductException($product, $exception, $action, $params);
                    }

                    $this->exceptionHelper->process($exception);

                    $result = \Ess\M2ePro\Helper\Data::STATUS_ERROR;
                }

                $results[] = $result;
            }
        }

        return $this->dataHelper->getMainStatus($results);
    }

    /**
     * @param \Ess\M2ePro\Model\Listing\Product[] $products
     * @param $action
     * @param bool $isRealTime
     * @param array $params
     *
     * @return int
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function processProducts(array $products, $action, bool $isRealTime, array $params): int
    {
        /** @var \Ess\M2ePro\Model\Ebay\Connector\Dispatcher $dispatcher */
        $dispatcher = $this->dispatcherFactory->create();
        $connectorName = 'Ebay\Connector\Item\\' . $this->getActionNick($action) . '\Requester';

        $results = [];

        foreach ($products as $product) {
            try {
                /** @var \Ess\M2ePro\Model\Ebay\Connector\Item\Requester $connector */
                $connector = $dispatcher->getCustomConnector($connectorName, $params);
                $connector->setIsRealTime($isRealTime);
                $connector->setListingProduct($product);

                $dispatcher->process($connector);
                $result = $connector->getStatus();

                $logsActionId = $connector->getLogsActionId();
                // When additional action runs using processing, there is no status for it
                if (is_array($logsActionId) && $isRealTime) {
                    $this->logsActionId = max($logsActionId);
                    /** @var \Ess\M2ePro\Model\Listing\Log $listingLog */
                    $listingLog = $this->activeRecordFactory->getObject('Listing\Log');
                    $result = $this->listingLogResource->getStatusByActionId(
                        $listingLog,
                        $this->logsActionId
                    );
                } else {
                    $this->logsActionId = $logsActionId;
                }
            } catch (\Throwable $exception) {
                $this->logListingProductException($product, $exception, $action, $params);
                $this->exceptionHelper->process($exception);

                $result = \Ess\M2ePro\Helper\Data::STATUS_ERROR;
            }

            $results[] = $result;
        }

        return $this->dataHelper->getMainStatus($results);
    }

    // ----------------------------------------

    /**
     * @param $products
     *
     * @return \Ess\M2ePro\Model\Listing\Product[]
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function prepareProducts($products): array
    {
        $productsTemp = [];

        if (!is_array($products)) {
            $products = [$products];
        }

        $productsIdsTemp = [];
        foreach ($products as $product) {
            $tempProduct = null;
            if ($product instanceof \Ess\M2ePro\Model\Listing\Product) {
                $tempProduct = $product;
            } else {
                $tempProduct = $this->ebayFactory->getObjectLoaded('Listing\Product', (int)$product);
            }

            if (in_array((int)$tempProduct->getId(), $productsIdsTemp)) {
                continue;
            }

            $productsIdsTemp[] = (int)$tempProduct->getId();
            $productsTemp[] = $tempProduct;
        }

        return $productsTemp;
    }

    /**
     * @param \Ess\M2ePro\Model\Listing\Product[] $products
     *
     * @return array
     */
    private function sortProductsByAccountsMarketplaces(array $products): array
    {
        $sortedProducts = [];

        foreach ($products as $product) {
            $accountId = $product->getListing()->getAccountId();
            $marketplaceId = $product->getListing()->getMarketplaceId();

            $sortedProducts[$accountId][$marketplaceId][] = $product;
        }

        return $sortedProducts;
    }

    // ----------------------------------------

    protected function logListingProductException(
        \Ess\M2ePro\Model\Listing\Product $listingProduct,
        \Throwable $exception,
        $action,
        $params
    ): void {
        /** @var \Ess\M2ePro\Model\Listing\Log $logModel */
        $logModel = $this->activeRecordFactory->getObject('Listing\Log');
        $logModel->setComponentMode(\Ess\M2ePro\Helper\Component\Ebay::NICK);

        $action = $this->recognizeActionForLogging($action, $params);
        $initiator = $this->recognizeInitiatorForLogging($params);

        $logModel->addProductMessage(
            $listingProduct->getListingId(),
            $listingProduct->getProductId(),
            $listingProduct->getId(),
            $initiator,
            $this->logsActionId,
            $action,
            $exception->getMessage(),
            \Ess\M2ePro\Model\Log\AbstractModel::TYPE_ERROR
        );
    }

    protected function recognizeInitiatorForLogging(array $params)
    {
        $statusChanger = \Ess\M2ePro\Model\Listing\Product::STATUS_CHANGER_UNKNOWN;
        isset($params['status_changer']) && $statusChanger = $params['status_changer'];

        if ($statusChanger == \Ess\M2ePro\Model\Listing\Product::STATUS_CHANGER_UNKNOWN) {
            $initiator = \Ess\M2ePro\Helper\Data::INITIATOR_UNKNOWN;
        } elseif ($statusChanger == \Ess\M2ePro\Model\Listing\Product::STATUS_CHANGER_USER) {
            $initiator = \Ess\M2ePro\Helper\Data::INITIATOR_USER;
        } else {
            $initiator = \Ess\M2ePro\Helper\Data::INITIATOR_EXTENSION;
        }

        return $initiator;
    }

    protected function recognizeActionForLogging($action, array $params)
    {
        $logAction = \Ess\M2ePro\Model\Listing\Log::ACTION_UNKNOWN;

        switch ($action) {
            case \Ess\M2ePro\Model\Listing\Product::ACTION_LIST:
                $logAction = \Ess\M2ePro\Model\Listing\Log::ACTION_LIST_PRODUCT_ON_COMPONENT;
                break;
            case \Ess\M2ePro\Model\Listing\Product::ACTION_RELIST:
                $logAction = \Ess\M2ePro\Model\Listing\Log::ACTION_RELIST_PRODUCT_ON_COMPONENT;
                break;
            case \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE:
                $logAction = \Ess\M2ePro\Model\Listing\Log::ACTION_REVISE_PRODUCT_ON_COMPONENT;
                break;
            case \Ess\M2ePro\Model\Listing\Product::ACTION_STOP:
                if (isset($params['remove']) && (bool)$params['remove']) {
                    $logAction = \Ess\M2ePro\Model\Listing\Log::ACTION_STOP_AND_REMOVE_PRODUCT;
                } else {
                    $logAction = \Ess\M2ePro\Model\Listing\Log::ACTION_STOP_PRODUCT_ON_COMPONENT;
                }
                break;
        }

        return $logAction;
    }

    // ----------------------------------------

    private function getActionNick($action): string
    {
        switch ($action) {
            case \Ess\M2ePro\Model\Listing\Product::ACTION_LIST:
                return 'ListAction';

            case \Ess\M2ePro\Model\Listing\Product::ACTION_RELIST:
                return 'Relist';

            case \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE:
                return 'Revise';

            case \Ess\M2ePro\Model\Listing\Product::ACTION_STOP:
                return 'Stop';

            default:
                throw new \Ess\M2ePro\Model\Exception\Logic('Unknown action', ['action' => $action]);
        }
    }
}
