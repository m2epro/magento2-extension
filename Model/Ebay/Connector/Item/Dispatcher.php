<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Connector\Item;

class Dispatcher extends \Ess\M2ePro\Model\AbstractModel
{
    /** @var \Ess\M2ePro\Model\Tag\ListingProduct\Buffer */
    private $tagBuffer;
    /** @var \Ess\M2ePro\Model\TagFactory */
    private $tagFactory;
    /** @var \Ess\M2ePro\Model\Ebay\Connector\DispatcherFactory */
    private $dispatcherFactory;
    /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Log */
    private $listingLogResource;
    /** @var \Ess\M2ePro\Helper\Module\Exception */
    private $exceptionHelper;
    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;
    /** @var \Ess\M2ePro\Model\ActiveRecord\Factory */
    private $activeRecordFactory;
    /** @var \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory */
    private $ebayFactory;

    /** @var int|null */
    private $logsActionId = null;

    public function __construct(
        \Ess\M2ePro\Model\Tag\ListingProduct\Buffer $tagBuffer,
        \Ess\M2ePro\Model\TagFactory $tagFactory,
        \Ess\M2ePro\Model\Ebay\Connector\DispatcherFactory $dispatcherFactory,
        \Ess\M2ePro\Model\ResourceModel\Listing\Log $listingLogResource,
        \Ess\M2ePro\Helper\Module\Exception $exceptionHelper,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Helper\Data $dataHelper,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    ) {
        parent::__construct($helperFactory, $modelFactory);

        $this->tagBuffer = $tagBuffer;
        $this->tagFactory = $tagFactory;
        $this->dispatcherFactory = $dispatcherFactory;
        $this->listingLogResource = $listingLogResource;
        $this->exceptionHelper = $exceptionHelper;
        $this->dataHelper = $dataHelper;
        $this->activeRecordFactory = $activeRecordFactory;
        $this->ebayFactory = $ebayFactory;
    }

    /**
     * @param int $action
     * @param \Ess\M2ePro\Model\Listing\Product[]|\Ess\M2ePro\Model\Listing\Product|int[]|int $products
     * @param array $params
     *
     * @return int
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function process(int $action, $products, array $params = []): int
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

    /**
     * @return int
     */
    public function getLogsActionId(): int
    {
        return (int)$this->logsActionId;
    }

    // ----------------------------------------

    /**
     * @param array $sortedProducts
     * @param int $action
     * @param bool $isRealTime
     * @param array $params
     *
     * @return int
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function processAccountsMarketplaces(
        array $sortedProducts,
        int $action,
        bool $isRealTime = false,
        array $params = []
    ): int {
        $results = [];

        foreach ($sortedProducts as $accountId => $accountProducts) {
            foreach ($accountProducts as $marketplaceId => $products) {
                if (empty($products)) {
                    continue;
                }

                try {
                    $result = $this->processProducts($products, $action, $isRealTime, $params);
                } catch (\Throwable $exception) {

                    /** @var \Ess\M2ePro\Model\Listing\Product $product */
                    foreach ($products as $product) {
                        $this->logListingProductException($product, $exception, $action, $params);
                    }

                    $this->exceptionHelper->process($exception);

                    $result = \Ess\M2ePro\Helper\Data::STATUS_ERROR;
                }

                $results[] = $result;
            }
        }

        $this->tagBuffer->flush();
        return $this->dataHelper->getMainStatus($results);
    }

    /**
     * @param array $products
     * @param int $action
     * @param bool $isRealTime
     * @param array $params
     *
     * @return int
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function processProducts(
        array $products,
        int $action,
        bool $isRealTime = false,
        array $params = []
    ): int {
        /** @var \Ess\M2ePro\Model\Ebay\Connector\Dispatcher $dispatcher */
        $dispatcher = $this->dispatcherFactory->create();
        $connectorName = 'Ebay\Connector\Item\\' . $this->getActionNick($action) . '\Requester';

        $results = [];

        if (!$this->isAdditionalDispatch($params)) {
            foreach ($products as $listingProduct) {
                $this->tagBuffer->removeAllTags($listingProduct);
            }
            $this->tagBuffer->flush();
        }

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

                if ($result === \Ess\M2ePro\Helper\Data::STATUS_ERROR) {
                    $this->tagBuffer->addTag($product, $this->tagFactory->createWithHasErrorCode());
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
     * @param array $params
     *
     * @return bool
     */
    private function isAdditionalDispatch(array $params): bool
    {
        return $params['is_additional_action'] ?? false;
    }

    /**
     * @param \Ess\M2ePro\Model\Listing\Product|\Ess\M2ePro\Model\Listing\Product[]|int[]|int $products
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

    /**
     * @param \Ess\M2ePro\Model\Listing\Product $listingProduct
     * @param \Throwable $exception
     * @param int $action
     * @param array $params
     *
     * @return void
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function logListingProductException(
        \Ess\M2ePro\Model\Listing\Product $listingProduct,
        \Throwable $exception,
        int $action,
        array $params
    ): void {
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

    /**
     * @param array $params
     *
     * @return int
     */
    private function recognizeInitiatorForLogging(array $params): int
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

    /**
     * @param int $action
     * @param array $params
     *
     * @return int
     */
    private function recognizeActionForLogging(int $action, array $params): int
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

    /**
     * @param int $action
     *
     * @return string
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function getActionNick(int $action): string
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
