<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Connector\Product;

/**
 * Class \Ess\M2ePro\Model\Amazon\Connector\Product\Dispatcher
 */
class Dispatcher extends \Ess\M2ePro\Model\AbstractModel
{
    private \Ess\M2ePro\Model\Tag\ListingProduct\Buffer $tagBuffer;
    /** @var null  */
    private $logsActionId = null;
    /** @var \Ess\M2ePro\Model\ActiveRecord\Factory  */
    protected $activeRecordFactory;
    /** @var \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory  */
    protected $amazonFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\Tag\ListingProduct\Buffer $tagBuffer,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    ) {
        $this->tagBuffer = $tagBuffer;
        $this->activeRecordFactory = $activeRecordFactory;
        $this->amazonFactory = $amazonFactory;
        parent::__construct($helperFactory, $modelFactory);
    }

    //########################################

    /**
     * @param int $action
     * @param array|\Ess\M2ePro\Model\Listing\Product $products
     * @param array $params
     *
     * @return int
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function process($action, $products, array $params = [])
    {
        $params = array_merge([
            'status_changer' => \Ess\M2ePro\Model\Listing\Product::STATUS_CHANGER_UNKNOWN,
        ], $params);

        if (empty($params['logs_action_id'])) {
            $this->logsActionId = $this->activeRecordFactory->getObject('Listing\Log')
                                                            ->getResource()->getNextActionId();
            $params['logs_action_id'] = $this->logsActionId;
        } else {
            $this->logsActionId = $params['logs_action_id'];
        }

        $products = $this->prepareProducts($products);
        $sortedProducts = $this->sortProductsByAccount($products);

        return $this->processGroupedProducts($sortedProducts, $action, $params);
    }

    //-----------------------------------------

    public function getLogsActionId()
    {
        return (int)$this->logsActionId;
    }

    //########################################

    /**
     * @param array $sortedProductsData
     * @param string $action
     * @param array $params
     *
     * @return int
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function processGroupedProducts(
        array $sortedProductsData,
        $action,
        array $params = []
    ) {
        $results = [];

        foreach ($sortedProductsData as $products) {
            if (empty($products)) {
                continue;
            }

            foreach ($products as $product) {
                $results[] = $this->processProduct($product, $action, $params);
            }
        }

        return $this->getHelper('Data')->getMainStatus($results);
    }

    /**
     * @param \Ess\M2ePro\Model\Listing\Product $product
     * @param string $action
     * @param array $params
     *
     * @return int
     */
    protected function processProduct(\Ess\M2ePro\Model\Listing\Product $product, $action, array $params = [])
    {
        try {
            /** @var \Ess\M2ePro\Model\Amazon\Connector\Dispatcher $dispatcher */
            $dispatcher = $this->modelFactory->getObject('Amazon_Connector_Dispatcher');
            $connectorName = 'Amazon\Connector\Product\\' . $this->getActionNick($action) . '\Requester';

            $this->tagBuffer->removeAllTags($product);
            $this->tagBuffer->flush();

            /** @var \Ess\M2ePro\Model\Amazon\Connector\Product\Requester $connector */
            $connector = $dispatcher->getCustomConnector($connectorName, $params);
            $connector->setListingProduct($product);

            $dispatcher->process($connector);

            return $connector->getStatus();
        } catch (\Exception $exception) {
            $this->getHelper('Module\Exception')->process($exception);

            $logModel = $this->activeRecordFactory->getObject('Amazon_Listing_Log');

            $action = $this->recognizeActionForLogging($action, $params);
            $initiator = $this->recognizeInitiatorForLogging($params);

            if (!$product->isDeleted()) {
                $logModel->addProductMessage(
                    $product->getListingId(),
                    $product->getProductId(),
                    $product->getId(),
                    $initiator,
                    $this->logsActionId,
                    $action,
                    $exception->getMessage(),
                    \Ess\M2ePro\Model\Log\AbstractModel::TYPE_ERROR
                );
            }

            return \Ess\M2ePro\Helper\Data::STATUS_ERROR;
        }
    }

    //########################################

    protected function prepareProducts($products)
    {
        if (!is_array($products)) {
            $products = [$products];
        }

        $preparedProducts = [];
        $parentsForProcessing = [];

        foreach ($products as $listingProduct) {
            if (is_numeric($listingProduct)) {
                if (isset($preparedProducts[(int)$listingProduct])) {
                    continue;
                }

                $listingProduct = $this->amazonFactory->getObjectLoaded(
                    'Listing\Product',
                    (int)$listingProduct
                );
            }

            /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */

            if (isset($preparedProducts[(int)$listingProduct->getId()])) {
                continue;
            }

            $preparedProducts[(int)$listingProduct->getId()] = $listingProduct;

            /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
            $amazonListingProduct = $listingProduct->getChildObject();
            $variationManager = $amazonListingProduct->getVariationManager();

            if (!$variationManager->isRelationMode()) {
                continue;
            }

            if ($variationManager->isRelationParentType()) {
                $parentListingProduct = $listingProduct;
            } else {
                $parentListingProduct = $variationManager->getTypeModel()->getParentListingProduct();
            }

            /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $parentAmazonListingProduct */
            $parentAmazonListingProduct = $parentListingProduct->getChildObject();

            if (!$parentAmazonListingProduct->getVariationManager()->getTypeModel()->isNeedProcessor()) {
                continue;
            }

            $parentsForProcessing[$parentListingProduct->getId()] = $parentListingProduct;
        }

        if (empty($parentsForProcessing)) {
            return $preparedProducts;
        }

        $massProcessor = $this->modelFactory->getObject(
            'Amazon_Listing_Product_Variation_Manager_Type_Relation_ParentRelation_Processor_Mass'
        );
        $massProcessor->setListingsProducts($parentsForProcessing);

        $massProcessor->execute();

        $actionConfigurators = [];
        foreach ($preparedProducts as $id => $listingProduct) {
            if ($listingProduct->getActionConfigurator() === null) {
                continue;
            }

            $actionConfigurators[$id] = $listingProduct->getActionConfigurator();
        }

        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection $listingProductCollection */
        $listingProductCollection = $this->amazonFactory->getObject('Listing\Product')->getCollection();
        $listingProductCollection->addFieldToFilter('id', ['in' => array_keys($preparedProducts)]);

        /** @var \Ess\M2ePro\Model\Listing\Product[] $actualListingsProducts */
        $actualListingsProducts = $listingProductCollection->getItems();

        if (empty($actualListingsProducts)) {
            return [];
        }

        foreach ($actualListingsProducts as $id => $actualListingProduct) {
            if ($actionConfigurators[$id] === null) {
                continue;
            }

            $actualListingProduct->setActionConfigurator($actionConfigurators[$id]);
        }

        return $actualListingsProducts;
    }

    protected function sortProductsByAccount($products)
    {
        $sortedProducts = [];

        /** @var \Ess\M2ePro\Model\Listing\Product $product */
        foreach ($products as $product) {
            $accountId = $product->getListing()->getAccountId();
            $sortedProducts[$accountId][] = $product;
        }

        return array_values($sortedProducts);
    }

    // ----------------------------------------

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
            case \Ess\M2ePro\Model\Listing\Product::ACTION_DELETE:
                if (isset($params['remove']) && (bool)$params['remove']) {
                    $logAction = \Ess\M2ePro\Model\Listing\Log::ACTION_DELETE_AND_REMOVE_PRODUCT;
                } else {
                    $logAction = \Ess\M2ePro\Model\Listing\Log::_ACTION_DELETE_PRODUCT_FROM_COMPONENT;
                }
                break;
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

    //########################################

    protected function getActionNick($action)
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

            case \Ess\M2ePro\Model\Listing\Product::ACTION_DELETE:
                return 'Delete';

            default:
                throw new \Ess\M2ePro\Model\Exception\Logic('Unknown action');
        }
    }

    //########################################
}
