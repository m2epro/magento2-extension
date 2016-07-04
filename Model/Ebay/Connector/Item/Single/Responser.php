<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Connector\Item\Single;

use Ess\M2ePro\Model\ActiveRecord\Factory;
use Ess\M2ePro\Model\Connector\Command\RealTime\Virtual;
use Ess\M2ePro\Model\Connector\Connection\Response\Message;
use Ess\M2ePro\Model\Ebay\Listing\Product;
use Ess\M2ePro\Model\Ebay\Synchronization\Templates\Synchronization\Inspector;
use Ess\M2ePro\Model\Ebay\Listing\Product\Action\Configurator;
use Ess\M2ePro\Model\Exception\Logic;
use Ess\M2ePro\Model\Listing\Product\Variation;
use Ess\M2ePro\Model\Ebay\Listing\Product\Variation as EbayVariation;
use Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection;
use Ess\M2ePro\Model\Synchronization\Templates\Synchronization\Runner;

abstract class Responser extends \Ess\M2ePro\Model\Ebay\Connector\Item\Responser
{
    /**
     * @var Factory
     */
    protected $activeRecordFactory = NULL;

    /**
     * @var \Ess\M2ePro\Model\Listing\Product
     */
    protected $listingProduct = array();

    /**
     * @var \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Configurator
     */
    protected $configurator = NULL;

    /**
     * @var \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Type\Response
     */
    protected $responseObject = NULL;

    /**
     * @var \Ess\M2ePro\Model\Ebay\Listing\Product\Action\RequestData
     */
    protected $requestDataObject = NULL;

    protected $isSuccess = false;

    protected $isSkipped = false;

    //########################################

    function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Model\Connector\Connection\Response $response,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        array $params = array()
    )
    {
        parent::__construct($ebayFactory, $response, $helperFactory, $modelFactory, $params);

        $this->activeRecordFactory = $activeRecordFactory;

        $listingProductId = $this->params['product']['id'];
        $this->listingProduct = $this->ebayFactory->getObjectLoaded('Listing\Product', $listingProductId);
    }

    //########################################

    public function failDetected($messageText)
    {
        parent::failDetected($messageText);

        $message = $this->modelFactory->getObject('Connector\Connection\Response\Message');
        $message->initFromPreparedData(
            $messageText,
           \Ess\M2ePro\Model\Connector\Connection\Response\Message::TYPE_ERROR
        );

        $this->getLogger()->logListingProductMessage(
            $this->listingProduct,
            $message,
            \Ess\M2ePro\Model\Log\AbstractLog::PRIORITY_HIGH
        );
    }

    public function eventAfterExecuting()
    {
        parent::eventAfterExecuting();

        if (empty($this->params['is_realtime'])) {
            $this->inspectProduct();
        }
    }

    protected function inspectProduct()
    {
        if (!$this->isSuccess && !$this->isSkipped) {
            return;
        }

        /** @var Runner $runner */
        $runner = $this->modelFactory->getObject('Synchronization\Templates\Synchronization\Runner');
        $runner->setConnectorModel('Ebay\Connector\Item\Dispatcher');
        $runner->setMaxProductsPerStep(100);

        /** @var Inspector $inspector */
        $inspector = $this->modelFactory->getObject('Ebay\Synchronization\Templates\Synchronization\Inspector');

        /** @var \Ess\M2ePro\Model\Ebay\Listing\Product $ebayListingProduct */
        $ebayListingProduct = $this->listingProduct->getChildObject();

        if ($this->listingProduct->isListed()) {
            /** @var Configurator $configurator */
            $configurator = $this->modelFactory->getObject('Ebay\Listing\Product\Action\Configurator');

            if ($inspector->isMeetStopRequirements($this->listingProduct)) {
                $action = \Ess\M2ePro\Model\Listing\Product::ACTION_STOP;

                if ($ebayListingProduct->isOutOfStockControlEnabled()) {
                    $action = \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE;

                    $configurator->setParams(
                        array('replaced_action' => \Ess\M2ePro\Model\Listing\Product::ACTION_STOP)
                    );
                    $configurator->setPartialMode();
                    $configurator->allowQty()->allowVariations();
                }

                $runner->addProduct(
                    $this->listingProduct, $action, $configurator
                );

                $runner->execute();
                return;
            }

            $configurator->setPartialMode();

            $needRevise = false;

            if ($inspector->isMeetReviseQtyRequirements($this->listingProduct)) {
                $configurator->allowQty();
                $needRevise = true;
            }

            if ($inspector->isMeetRevisePriceRequirements($this->listingProduct)) {
                $configurator->allowPrice();
                $needRevise = true;
            }

            if (!$needRevise) {
                return;
            }

            if ($ebayListingProduct->isVariationsReady()) {
                $configurator->allowVariations();
            }

            $runner->addProduct(
                $this->listingProduct, \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE, $configurator
            );

            $runner->execute();
            return;
        }

        if ($this->listingProduct->isStopped() || $this->listingProduct->isHidden()) {
            if (!$inspector->isMeetRelistRequirements($this->listingProduct)) {
                return;
            }

            /** @var Configurator $configurator */
            $configurator = $this->modelFactory->getObject('Ebay\Listing\Product\Action\Configurator');
            $action = \Ess\M2ePro\Model\Listing\Product::ACTION_RELIST;

            if ($this->listingProduct->isHidden()) {
                $configurator->setParams(array('replaced_action' => \Ess\M2ePro\Model\Listing\Product::ACTION_RELIST));

                $action = \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE;
            }

            if (!$ebayListingProduct->getEbaySynchronizationTemplate()->isRelistSendData()) {
                $configurator->setPartialMode();
                $configurator->allowQty()->allowPrice()->allowVariations();
            }

            $runner->addProduct(
                $this->listingProduct, $action, $configurator
            );
            $runner->execute();
        }
    }

    //########################################

    protected function processResponseMessages()
    {
        parent::processResponseMessages();

        $this->processMessages($this->getResponse()->getMessages()->getEntities());
    }

    protected function prepareResponseData()
    {
        $responseData = $this->getResponse()->getResponseData();
        if (empty($responseData['result'])) {
            $this->preparedResponseData = $responseData;
            return;
        }

        $this->preparedResponseData = reset($responseData['result']);
    }

    protected function processResponseData()
    {
        if ($this->getResponse()->isResultError()) {
            if (!empty($responseData['is_skipped'])) {
                $this->isSkipped = true;
            }

            return;
        }

        $responseData = $this->getPreparedResponseData();
        $responseMessages = $this->getResponse()->getMessages()->getEntities();

        $this->processCompleted($responseData, array(
            'is_images_upload_error' => $this->isImagesUploadFailed($responseMessages)
        ));
    }

    protected function processMessages(array $messages)
    {
        foreach ($messages as $message) {
            $this->getLogger()->logListingProductMessage($this->listingProduct, $message);
        }
    }

    protected function processCompleted(array $data = array(), array $params = array())
    {
        $this->getResponseObject()->processSuccess($data, $params);

        $message = $this->modelFactory->getObject('Connector\Connection\Response\Message');
        $message->initFromPreparedData(
            $this->getSuccessfulMessage(),
            \Ess\M2ePro\Model\Connector\Connection\Response\Message::TYPE_SUCCESS
        );

        $this->getLogger()->logListingProductMessage(
            $this->listingProduct, $message
        );

        $this->isSuccess = true;
    }

    //----------------------------------------

    abstract protected function getSuccessfulMessage();

    //########################################

    /**
     * @param Message[] $messages
     * @return bool
     *
     * eBay internal error. The operation was not completed (code:34) (returned by M2e Pro server)
     */
    protected function isEbayApplicationErrorAppeared(array $messages)
    {
        foreach ($messages as $message) {
            if (strpos($message->getText(), 'code:34') !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param Message[] $messages
     * @return bool
     *
     * 32704531: Can't upload product image on eBay (returned by M2e Pro server)
     */
    protected function isImagesUploadFailed(array $messages)
    {
        foreach ($messages as $message) {
            if ($message->getCode() == 32704531) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param Message[] $messages
     * @return bool
     *
     * 17: This item cannot be accessed because the listing has been deleted, is a Half.com listing,
     *     or you are not the seller.
     */
    protected function isItemCanNotBeAccessed(array $messages)
    {
        foreach ($messages as $message) {
            if ($message->getCode() == 17) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param Message[] $messages
     * @return bool
     *
     * 21919301: (UPC/EAN/ISBN) is missing a value. Enter a value and try again.
     */
    protected function isNewRequiredSpecificNeeded(array $messages)
    {
        foreach ($messages as $message) {
            if ($message->getCode() == 21919301) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param Message[] $messages
     * @return bool
     *
     * 21916587: The multi-variation titles have been changed and were not updated on the eBay.
     * 21916626: Variations Specifics and Item Specifics entered for a Multi-SKU item should be different.
     * 21916603: Variation specifics cannot be changed in restricted revise
     * 21916664: Variation Specifics provided does not match with the variation specifics of the variations on the item.
     * 21916585: Duplicate custom variation label.
     * 21916582: Duplicate VariationSpecifics trait value in the VariationSpecificsSet container.
     * 21916672: The tags (MPN) is/are disabled as Variant.
     */
    protected function isVariationErrorAppeared(array $messages)
    {
        $errorCodes = array(
            21916587,
            21916626,
            21916603,
            21916664,
            21916585,
            21916582,
            21916672,
        );

        foreach ($messages as $message) {
            if (in_array($message->getCode(), $errorCodes)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param Message[] $messages
     * @return bool
     *
     * 21916884: Condition is required for this category.
     */
    protected function isConditionErrorAppeared(array $messages)
    {
        foreach ($messages as $message) {
            if ($message->getCode() == 21916884) {
                return true;
            }
        }

        return false;
    }

    //########################################

    protected function tryToResolveVariationMpnErrors()
    {
        if (!$this->canPerformGetItemCall()) {
            return;
        }

        $variationMpnValues = $this->getVariationMpnDataFromEbay();
        if ($variationMpnValues === false) {
            return;
        }

        $isVariationMpnFilled = !empty($variationMpnValues);

        $this->listingProduct->setSetting('additional_data', 'is_variation_mpn_filled', $isVariationMpnFilled);
        if (!$isVariationMpnFilled) {
            $this->listingProduct->setSetting('additional_data', 'without_mpn_variation_issue', true);
        }

        $this->listingProduct->save();

        if (!empty($variationMpnValues)) {
            $this->fillVariationMpnValues($variationMpnValues);
        }

        $message = $this->modelFactory->getObject('Connector\Connection\Response\Message');
        $message->initFromPreparedData(
            $this->getHelper('Module\Translation')->__(
                'It has been detected that this Item failed to be updated on eBay because of the errors.
                M2E Pro will automatically try to apply another solution to Revise this Item.'),
            Message::TYPE_SUCCESS
        );

        $this->getLogger()->logListingProductMessage($this->listingProduct, $message);

        $this->processAdditionalAction($this->getActionType(), $this->getConfigurator());
    }

    protected function canPerformGetItemCall()
    {
        if ($this->getStatusChanger() == \Ess\M2ePro\Model\Listing\Product::STATUS_CHANGER_USER) {
            return true;
        }

        $getItemCallsCount   = 0;
        $getItemLastCallDate = NULL;

        $maxAllowedGetItemCallsCount = 2;

        $additionalData = $this->listingProduct->getAdditionalData();
        if (!empty($additionalData['get_item_calls_statistic'])) {
            $getItemCallsCount   = $additionalData['get_item_calls_statistic']['count'];
            $getItemLastCallDate = $additionalData['get_item_calls_statistic']['last_call_date'];
        }

        if ($getItemCallsCount >= $maxAllowedGetItemCallsCount) {
            $minAllowedDate = new \DateTime('now', new \DateTimeZone('UTC'));
            $minAllowedDate->modify('- 1 day');

            if (strtotime($getItemLastCallDate) > $minAllowedDate->format('U')) {
                return false;
            }

            $getItemCallsCount = 0;
        }

        $getItemCallsCount++;
        $getItemLastCallDate = $this->helperFactory->getObject('Data')->getCurrentGmtDate();

        $additionalData['get_item_calls_statistic']['count']          = $getItemCallsCount;
        $additionalData['get_item_calls_statistic']['last_call_date'] = $getItemLastCallDate;

        $this->listingProduct->setSettings('additional_data', $additionalData);
        $this->listingProduct->save();

        return true;
    }

    protected function getVariationMpnDataFromEbay()
    {
        /** @var Product $ebayListingProduct */
        $ebayListingProduct = $this->listingProduct->getChildObject();

        /** @var Virtual $connector */
        $connector = $this->modelFactory->getObject('Ebay\Connector\Item\Dispatcher')->getVirtualConnector(
            'item', 'get', 'info',
            array(
                'item_id' => $ebayListingProduct->getEbayItemIdReal(),
                'parser_type' => 'standard',
                'full_variations_mode' => true
            ), 'result', $this->getMarketplace(), $this->getAccount()
        );

        try {
            $connector->process();
        } catch (\Exception $exception) {
            $this->helperFactory->getObject('Module\Exception')->process($exception);
            return false;
        }

        $itemData = $connector->getResponseData();
        if (empty($itemData['variations'])) {
            return array();
        }

        $variationMpnValues = array();

        foreach ($itemData['variations'] as $variation) {
            if (empty($variation['specifics']['MPN'])) {
                continue;
            }

            $mpnValue = $variation['specifics']['MPN'];
            unset($variation['specifics']['MPN']);

            $variationMpnValues[] = array(
                'mpn'       => $mpnValue,
                'sku'       => $variation['sku'],
                'specifics' => $variation['specifics'],
            );
        }

        return $variationMpnValues;
    }

    /**
     * @param $variationMpnValues
     * @throws Logic
     */
    protected function fillVariationMpnValues($variationMpnValues)
    {
        /** @var Collection $variationCollection */
        $variationCollection = $this->activeRecordFactory->getObject('Listing\Product\Variation')->getCollection();
        $variationCollection->addFieldToFilter('listing_product_id', $this->listingProduct->getId());

        /** @var Collection $variationOptionCollection */
        $variationOptionCollection = $this->activeRecordFactory->getObject('Listing\Product\Variation\Option')
            ->getCollection();
        $variationOptionCollection->addFieldToFilter(
            'listing_product_variation_id', $variationCollection->getColumnValues('id')
        );

        /** @var Variation[] $variations */
        $variations = $variationCollection->getItems();

        /** @var Variation\Option[] $variationOptions */
        $variationOptions = $variationOptionCollection->getItems();

        foreach ($variations as $variation) {
            $specifics = array();

            foreach ($variationOptions as $id => $variationOption) {
                if ($variationOption->getListingProductVariationId() != $variation->getId()) {
                    continue;
                }

                $specifics[$variationOption->getAttribute()] = $variationOption->getOption();
                unset($variationOptions[$id]);
            }

            /** @var EbayVariation $ebayVariation */
            $ebayVariation = $variation->getChildObject();

            foreach ($variationMpnValues as $id => $variationMpnValue) {
                if ($ebayVariation->getOnlineSku() != $variationMpnValue['sku'] &&
                    $specifics != $variationMpnValue['specifics']
                ) {
                    continue;
                }

                $additionalData = $variation->getAdditionalData();

                if (!isset($additionalData['ebay_mpn_value']) ||
                    $additionalData['ebay_mpn_value'] != $variationMpnValue['mpn']
                ) {
                    $additionalData['ebay_mpn_value'] = $variationMpnValue['mpn'];

                    $variation->setSettings('additional_data', $additionalData);
                    $variation->save();
                }

                unset($variationMpnValues[$id]);

                break;
            }
        }
    }

    //########################################

    protected function getConfigurator()
    {
        if (empty($this->configurator)) {

            $configurator = $this->modelFactory->getObject('Ebay\Listing\Product\Action\Configurator');
            $configurator->setUnserializedData($this->params['product']['configurator']);

            $this->configurator = $configurator;
        }

        return $this->configurator;
    }

    /**
     * @return \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Type\Response
     */
    protected function getResponseObject()
    {
        if (empty($this->responseObject)) {

            /* @var $response \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Type\Response */
            $response = $this->modelFactory->getObject(
                'Ebay\Listing\Product\Action\Type\\'.$this->getOrmActionType().'\Response'
            );

            $response->setParams($this->params['params']);
            $response->setListingProduct($this->listingProduct);
            $response->setConfigurator($this->getConfigurator());
            $response->setRequestData($this->getRequestDataObject());

            $requestMetaData = !empty($this->params['product']['request_metadata'])
                ? $this->params['product']['request_metadata'] : array();

            $response->setRequestMetaData($requestMetaData);

            $this->responseObject = $response;
        }

        return $this->responseObject;
    }

    /**
     * @return \Ess\M2ePro\Model\Ebay\Listing\Product\Action\RequestData
     */
    protected function getRequestDataObject()
    {
        if (empty($this->requestDataObject)) {

            /** @var \Ess\M2ePro\Model\Ebay\Listing\Product\Action\RequestData $requestData */
            $requestData = $this->modelFactory->getObject('Ebay\Listing\Product\Action\RequestData');

            $requestData->setData($this->params['product']['request']);
            $requestData->setListingProduct($this->listingProduct);

            $this->requestDataObject = $requestData;
        }

        return $this->requestDataObject;
    }

    //########################################

    protected function markAsPotentialDuplicate()
    {
        $additionalData = $this->listingProduct->getAdditionalData();

        $additionalData['last_failed_action_data'] = array(
            'native_request_data' => $this->getRequestDataObject()->getData(),
            'previous_status' => $this->listingProduct->getStatus(),
            'action' => $this->getActionType(),
            'request_time' => $this->getResponse()->getRequestTime(),
        );

        $this->listingProduct->addData(array(
            'status' => \Ess\M2ePro\Model\Listing\Product::STATUS_BLOCKED,
            'additional_data' => json_encode($additionalData),
        ))->save();

        $this->listingProduct->getChildObject()->updateVariationsStatus();
    }

    //########################################

    protected function processAdditionalAction($actionType,
                                               Configurator $configurator,
                                               array $params = array())
    {
        $listingProduct = clone $this->listingProduct;
        $listingProduct->setActionConfigurator($configurator);

        $params = array_merge(
            $params,
            array(
                'status_changer' => $this->getStatusChanger(),
                'is_realtime'    => true,
            )
        );

        $dispatcher = $this->modelFactory->getObject('Ebay\Connector\Item\Dispatcher');
        $dispatcher->process($actionType, array($this->listingProduct), $params);

        $logsActionId = $this->params['logs_action_id'];
        if (!is_array($logsActionId)) {
            $logsActionId = array($logsActionId);
        }

        $logsActionId[] = $dispatcher->getLogsActionId();

        $this->params['logs_action_id'] = $logsActionId;
    }

    //########################################
}