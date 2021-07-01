<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Connector\Item\Relist;

use Ess\M2ePro\Model\Connector\Connection\Response\Message;
use Ess\M2ePro\Model\Ebay\Listing\Product\Variation as EbayVariation;

/**
 * @method \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Type\Relist\Response getResponseObject()
 */
class Responser extends \Ess\M2ePro\Model\Ebay\Connector\Item\Responser
{
    /** @var \Magento\Framework\Locale\CurrencyInterface */
    protected $localeCurrency;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\Connector\Connection\Response $response,
        \Magento\Framework\Locale\CurrencyInterface $localeCurrency,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        EbayVariation\Resolver $variationResolver,
        array $params = []
    ) {
        $this->localeCurrency = $localeCurrency;
        parent::__construct(
            $walmartFactory,
            $amazonFactory,
            $ebayFactory,
            $activeRecordFactory,
            $response,
            $helperFactory,
            $modelFactory,
            $variationResolver,
            $params
        );
    }

    //########################################

    protected function getSuccessfulMessage()
    {
        $currency = $this->localeCurrency->getCurrency(
            $this->listingProduct->getMarketplace()->getChildObject()->getCurrency()
        );

        $onlineQty = $this->listingProduct->getChildObject()->getOnlineQty() -
                     $this->listingProduct->getChildObject()->getOnlineQtySold();

        if ($this->getRequestDataObject()->isVariationItem()) {
            $calculateWithEmptyQty = $this->listingProduct->getChildObject()->isOutOfStockControlEnabled();

            return sprintf(
                'Product was Relisted with QTY %d, Price %s - %s',
                $onlineQty,
                $currency->toCurrency($this->getRequestDataObject()->getVariationMinPrice($calculateWithEmptyQty)),
                $currency->toCurrency($this->getRequestDataObject()->getVariationMaxPrice($calculateWithEmptyQty))
            );
        }

        return sprintf(
            'Product was Relisted with QTY %d, Price %s',
            $onlineQty,
            $currency->toCurrency($this->listingProduct->getChildObject()->getOnlineCurrentPrice())
        );
    }

    //########################################

    protected function processCompleted(array $data = [], array $params = [])
    {
        if (!empty($data['already_active'])) {
            $this->getResponseObject()->processAlreadyActive($data, $params);

            /** @var \Ess\M2ePro\Model\Connector\Connection\Response\Message $message */
            $message = $this->modelFactory->getObject('Connector_Connection_Response_Message');
            $message->initFromPreparedData(
                'Item was already started on eBay',
                \Ess\M2ePro\Model\Connector\Connection\Response\Message::TYPE_ERROR
            );

            $this->getLogger()->logListingProductMessage($this->listingProduct, $message);
            return;
        }

        parent::processCompleted($data, $params);
    }

    /**
     * @return void|null
     * @throws \Ess\M2ePro\Model\Exception
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function eventAfterExecuting()
    {
        $responseMessages = $this->getResponse()->getMessages()->getEntities();

        if (!$this->listingProduct->getAccount()->getChildObject()->isModeSandbox() &&
            $this->isEbayApplicationErrorAppeared($responseMessages)) {
            $this->markAsPotentialDuplicate();

            /** @var \Ess\M2ePro\Model\Connector\Connection\Response\Message $message */
            $message = $this->modelFactory->getObject('Connector_Connection_Response_Message');
            $message->initFromPreparedData(
                'An error occurred while Listing the Item. The Item has been blocked.
                 The next M2E Pro Synchronization will resolve the problem.',
                Message::TYPE_WARNING
            );

            $this->getLogger()->logListingProductMessage($this->listingProduct, $message);
        }

        if ($this->isConditionErrorAppeared($responseMessages)) {
            /** @var \Ess\M2ePro\Model\Connector\Connection\Response\Message $message */
            $message = $this->modelFactory->getObject('Connector_Connection_Response_Message');
            $message->initFromPreparedData(
                $this->getHelper('Module\Translation')->__(
                    'M2E Pro was not able to send Condition on eBay. Please try to perform the Relist Action once more.'
                ),
                Message::TYPE_WARNING
            );

            $this->getLogger()->logListingProductMessage($this->listingProduct, $message);

            $additionalData = $this->listingProduct->getAdditionalData();
            $additionalData['is_need_relist_condition'] = true;

            $this->listingProduct
                ->setSettings('additional_data', $additionalData)
                ->save();
        }

        if ($this->getStatusChanger() == \Ess\M2ePro\Model\Listing\Product::STATUS_CHANGER_SYNCH &&
            $this->isItemCanNotBeAccessed($responseMessages)) {
            $itemId = null;
            if (isset($this->params['product']['request']['item_id'])) {
                $itemId = $this->params['product']['request']['item_id'];
            }

            /** @var \Ess\M2ePro\Model\Connector\Connection\Response\Message $message */
            $message = $this->modelFactory->getObject('Connector_Connection_Response_Message');
            $message->initFromPreparedData(
                $this->getHelper('Module\Translation')->__(
                    "This Item {$itemId} was not relisted as it cannot be accessed on eBay.
                    Instead, M2E Pro will run the List action based on your Synchronization Rules"
                ),
                Message::TYPE_WARNING
            );

            $this->getLogger()->logListingProductMessage($this->listingProduct, $message);

            $configurator = $this->modelFactory->getObject('Ebay_Listing_Product_Action_Configurator');
            $this->processAdditionalAction(
                \Ess\M2ePro\Model\Listing\Product::ACTION_LIST,
                $configurator,
                ['skip_check_the_same_product_already_listed_ids' => [$this->listingProduct->getId()]]
            );
        }

        if ($this->getStatusChanger() == \Ess\M2ePro\Model\Listing\Product::STATUS_CHANGER_SYNCH &&
            $this->getConfigurator()->isIncludingMode()) {
            /** @var \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Configurator $configurator */
            $configurator = $this->modelFactory->getObject('Ebay_Listing_Product_Action_Configurator');

            if ($this->isProductIdentifierNeeded($responseMessages)) {
                /** @var \Ess\M2ePro\Model\Connector\Connection\Response\Message $message */
                $message = $this->modelFactory->getObject('Connector_Connection_Response_Message');
                $message->initFromPreparedData(
                    $this->getHelper('Module\Translation')->__(
                        'It has been detected that the Category you are using is going to require the Product Identifiers
                    to be specified (UPC, EAN, ISBN, etc.). The Relist Action will be automatically performed
                    to send the value(s) of the required Identifier(s) based on the settings
                    provided in eBay Catalog Identifiers section of the Description Policy.'
                    ),
                    Message::TYPE_WARNING
                );

                $this->getLogger()->logListingProductMessage($this->listingProduct, $message);
                $this->processAdditionalAction($this->getActionType(), $configurator);
            } elseif ($this->isNewRequiredSpecificNeeded($responseMessages)) {
                $configurator->allowCategories();
                $this->processAdditionalAction($this->getActionType(), $configurator);
            }
        }

        if ($this->isVariationErrorAppeared($responseMessages) && $this->getRequestDataObject()->hasVariations()) {
            $this->tryToResolveVariationErrors();
        }

        if ($message = $this->isDuplicateErrorByUUIDAppeared($responseMessages)) {
            $this->processDuplicateByUUID($message);
        }

        if ($message = $this->isDuplicateErrorByEbayEngineAppeared($responseMessages)) {
            $this->processDuplicateByEbayEngine($message);
        }

        parent::eventAfterExecuting();
    }

    //########################################
}
