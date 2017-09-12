<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Connector\Item;

class Dispatcher extends \Ess\M2ePro\Model\AbstractModel
{
    private $logsActionId = NULL;

    protected $activeRecordFactory;
    protected $ebayFactory;

    // ########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    )
    {
        $this->activeRecordFactory = $activeRecordFactory;
        $this->ebayFactory = $ebayFactory;
        parent::__construct($helperFactory, $modelFactory);
    }

    // ########################################

    /**
     * @param int $action
     * @param array|\Ess\M2ePro\Model\Listing\Product $products
     * @param array $params
     * @return int
     */
    public function process($action, $products, array $params = array())
    {
        $params = array_merge(array(
            'status_changer' => \Ess\M2ePro\Model\Listing\Product::STATUS_CHANGER_UNKNOWN
        ), $params);

        $this->logsActionId = $this->activeRecordFactory->getObject('Listing\Log')->getResource()->getNextActionId();
        $params['logs_action_id'] = $this->logsActionId;

        $isRealTime = !empty($params['is_realtime']);

        $products = $this->prepareProducts($products);
        $sortedProducts = $this->sortProductsByAccountsMarketplaces($products);

        return $this->processAccountsMarketplaces($sortedProducts, $action, $isRealTime, $params);
    }

    public function getLogsActionId()
    {
        return (int)$this->logsActionId;
    }

    // ########################################

    /**
     * @param array $sortedProducts
     * @param string $action
     * @param bool $isRealTime
     * @param array $params
     * @return int
     * @throws \LogicException
     */
    protected function processAccountsMarketplaces(array $sortedProducts,
                                                   $action,
                                                   $isRealTime = false,
                                                   array $params = array())
    {
        $results = array();

        foreach ($sortedProducts as $accountId => $accountProducts) {
            foreach ($accountProducts as $marketplaceId => $products) {

                if (empty($products)) {
                    continue;
                }

                try {

                    $result = $this->processProducts($products, $action, $isRealTime, $params);

                } catch (\Exception $exception) {

                    foreach ($products as $product) {
                        /** @var \Ess\M2ePro\Model\Listing\Product $product */

                        $this->logListingProductException($product, $exception, $action, $params);
                    }

                    $this->getHelper('Module\Exception')->process($exception);

                    $result = \Ess\M2ePro\Helper\Data::STATUS_ERROR;
                }

                $results[] = $result;
            }
        }

        return $this->getHelper('Data')->getMainStatus($results);
    }

    // ########################################

    protected function processProducts(array $products, $action, $isRealTime = false, array $params = array())
    {
        $dispatcher = $this->modelFactory->getObject('Ebay\Connector\Dispatcher');
        $connectorName = 'Ebay\Connector\Item\\'.$this->getActionNick($action).'\Requester';

        $results = array();

        foreach ($products as $product) {
            /** @var \Ess\M2ePro\Model\Listing\Product $product */

            try {

                /** @var \Ess\M2ePro\Model\Ebay\Connector\Item\Requester $connector */
                $connector = $dispatcher->getCustomConnector($connectorName, $params);
                $connector->setIsRealTime($isRealTime);
                $connector->setListingProduct($product);

                $dispatcher->process($connector);
                $result = $connector->getStatus();

                $logsActionId = $connector->getLogsActionId();

                if (is_array($logsActionId)) {
                    $this->logsActionId = max($logsActionId);
                    $listingLog = $this->activeRecordFactory->getObject('Listing\Log');
                    $result = $listingLog->getResource()->getStatusByActionId(
                        $listingLog,
                        $this->logsActionId
                    );
                } else {
                    $this->logsActionId = $logsActionId;
                }

            } catch (\Exception $exception) {

                $this->logListingProductException($product, $exception, $action, $params);
                $this->getHelper('Module\Exception')->process($exception);

                $result = \Ess\M2ePro\Helper\Data::STATUS_ERROR;
            }

            $results[] = $result;
        }

        return $this->getHelper('Data')->getMainStatus($results);
    }

    // ########################################

    protected function prepareProducts($products)
    {
        $productsTemp = array();

        if (!is_array($products)) {
            $products = array($products);
        }

        $productsIdsTemp = array();
        foreach ($products as $product) {

            $tempProduct = NULL;
            if ($product instanceof \Ess\M2ePro\Model\Listing\Product) {
                $tempProduct = $product;
            } else {
                $tempProduct = $this->ebayFactory->getObjectLoaded('Listing\Product',(int)$product);
            }

            if (in_array((int)$tempProduct->getId(),$productsIdsTemp)) {
                continue;
            }

            $productsIdsTemp[] = (int)$tempProduct->getId();
            $productsTemp[] = $tempProduct;
        }

        return $productsTemp;
    }

    protected function sortProductsByAccountsMarketplaces($products)
    {
        $sortedProducts = array();

        foreach ($products as $product) {

            /** @var \Ess\M2ePro\Model\Listing\Product $product */

            $accountId     = $product->getListing()->getAccountId();
            $marketplaceId = $product->getListing()->getMarketplaceId();

            $sortedProducts[$accountId][$marketplaceId][] = $product;
        }

        return $sortedProducts;
    }

    // ----------------------------------------

    protected function logListingProductException(\Ess\M2ePro\Model\Listing\Product $listingProduct,
                                                  \Exception $exception,
                                                  $action, $params)
    {
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
            \Ess\M2ePro\Model\Log\AbstractModel::TYPE_ERROR,
            \Ess\M2ePro\Model\Log\AbstractModel::PRIORITY_HIGH
        );
    }

    protected function recognizeInitiatorForLogging(array $params)
    {
        $statusChanger = \Ess\M2ePro\Model\Listing\Product::STATUS_CHANGER_UNKNOWN;
        isset($params['status_changer']) && $statusChanger = $params['status_changer'];

        if ($statusChanger == \Ess\M2ePro\Model\Listing\Product::STATUS_CHANGER_UNKNOWN) {
            $initiator = \Ess\M2ePro\Helper\Data::INITIATOR_UNKNOWN;
        } else if ($statusChanger == \Ess\M2ePro\Model\Listing\Product::STATUS_CHANGER_USER) {
            $initiator = \Ess\M2ePro\Helper\Data::INITIATOR_USER;
        } else {
            $initiator = \Ess\M2ePro\Helper\Data::INITIATOR_EXTENSION;
        }

        return $initiator;
    }

    protected function recognizeActionForLogging($action, array $params)
    {
        $logAction =\Ess\M2ePro\Model\Listing\Log::ACTION_UNKNOWN;

        switch ($action)
        {
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

    // ########################################

    private function getActionNick($action)
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
                throw new \Ess\M2ePro\Model\Exception\Logic('Unknown action');
        }
    }

    // ########################################
}