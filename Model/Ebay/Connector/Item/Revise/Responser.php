<?php

namespace Ess\M2ePro\Model\Ebay\Connector\Item\Revise;

use Ess\M2ePro\Model\Connector\Connection\Response\Message;
use Ess\M2ePro\Model\Ebay\Listing\Product\Variation as EbayVariation;

/**
 * @method \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Type\Revise\Response getResponseObject()
 */
class Responser extends \Ess\M2ePro\Model\Ebay\Connector\Item\Responser
{
    /** @var \Magento\Framework\Locale\CurrencyInterface */
    private $localeCurrency;

    public function __construct(
        \Ess\M2ePro\Model\Tag\ListingProduct\Buffer $tagBuffer,
        \Ess\M2ePro\Model\Ebay\TagFactory $tagFactory,
        \Ess\M2ePro\Helper\Data $helperData,
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
        parent::__construct(
            $tagBuffer,
            $tagFactory,
            $helperData,
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

        $this->localeCurrency = $localeCurrency;
    }

    /**
     * @return string
     */
    protected function getSuccessfulMessage()
    {
        if ($this->getConfigurator()->isExcludingMode()) {
            return 'Item was Revised';
        }

        $sequenceStrings = [];
        $isPlural = false;

        if ($this->getConfigurator()->isTitleAllowed()) {
            $sequenceStrings[] = 'Title';
        }

        if ($this->getConfigurator()->isSubtitleAllowed()) {
            $sequenceStrings[] = 'Subtitle';
        }

        if ($this->getConfigurator()->isDescriptionAllowed()) {
            $sequenceStrings[] = 'Description';
        }

        if ($this->getConfigurator()->isImagesAllowed()) {
            $sequenceStrings[] = 'Images';
            $isPlural = true;
        }

        if ($this->getConfigurator()->isCategoriesAllowed()) {
            $sequenceStrings[] = 'Categories / Specifics';
            $isPlural = true;
        }

        if ($this->getConfigurator()->isPartsAllowed()) {
            $sequenceStrings[] = 'eBay Parts Compatibility';
            $isPlural = true;
        }

        if ($this->getConfigurator()->isShippingAllowed()) {
            $sequenceStrings[] = 'Shipping';
        }

        if ($this->getConfigurator()->isReturnAllowed()) {
            $sequenceStrings[] = 'Return';
        }

        if ($this->getConfigurator()->isOtherAllowed()) {
            $sequenceStrings[] = 'Product details';
            $isPlural = true;
        }

        if (empty($sequenceStrings)) {
            return null;
        }

        if (count($sequenceStrings) == 1) {
            $verb = $isPlural ? 'were' : 'was';

            return $sequenceStrings[0] . ' ' . $verb . ' Revised';
        }

        return implode(', ', $sequenceStrings) . ' were Revised';
    }

    protected function processCompleted(array $data = [], array $params = [])
    {
        if (!empty($data['already_stop'])) {
            $this->getResponseObject()->processAlreadyStopped($data, $params);

            /** @var \Ess\M2ePro\Model\Connector\Connection\Response\Message $message */
            $message = $this->modelFactory->getObject('Connector_Connection_Response_Message');
            $message->initFromPreparedData(
                'Item was already Stopped on eBay',
                \Ess\M2ePro\Model\Connector\Connection\Response\Message::TYPE_ERROR
            );

            $this->getLogger()->logListingProductMessage($this->listingProduct, $message);

            return;
        }

        parent::processCompleted($data, $params);

        $this->processSuccessReviseGeneral();
        $this->processSuccessRevisePrice();
        $this->processSuccessReviseQty();
        $this->processSuccessReviseVariations();
    }

    private function processSuccessReviseGeneral(): void
    {
        if (!$this->getConfigurator()->isGeneralAllowed()) {
            return;
        }

        /** @var \Ess\M2ePro\Model\Connector\Connection\Response\Message $message */
        $message = $this->modelFactory->getObject('Connector_Connection_Response_Message');
        $message->initFromPreparedData(
            'Product details was revised',
            \Ess\M2ePro\Model\Connector\Connection\Response\Message::TYPE_SUCCESS
        );

        $this->getLogger()->logListingProductMessage($this->listingProduct, $message);
    }

    protected function processSuccessRevisePrice()
    {
        if (!$this->getConfigurator()->isPriceAllowed()) {
            return;
        }

        $currency = $this->localeCurrency->getCurrency(
            $this->listingProduct->getMarketplace()->getChildObject()->getCurrency()
        );

        $from = $this->listingProduct->getChildObject()->getOrigData('online_current_price');
        $to = $this->listingProduct->getChildObject()->getOnlineCurrentPrice();
        if ($from == $to) {
            return;
        }

        /** @var \Ess\M2ePro\Model\Connector\Connection\Response\Message $message */
        $message = $this->modelFactory->getObject('Connector_Connection_Response_Message');
        $message->initFromPreparedData(
            sprintf(
                'Price was revised from %s to %s',
                $currency->toCurrency($from),
                $currency->toCurrency($to)
            ),
            \Ess\M2ePro\Model\Connector\Connection\Response\Message::TYPE_SUCCESS
        );

        $this->getLogger()->logListingProductMessage($this->listingProduct, $message);
    }

    protected function processSuccessReviseQty()
    {
        if ($this->getRequestDataObject()->isVariationItem()) {
            if (!$this->getConfigurator()->isVariationsAllowed()) {
                return;
            }
        } elseif (!$this->getConfigurator()->isQtyAllowed()) {
            return;
        }

        $from = $this->listingProduct->getChildObject()->getOrigData('online_qty') -
            $this->listingProduct->getChildObject()->getOrigData('online_qty_sold');

        $to = $this->listingProduct->getChildObject()->getOnlineQty() -
            $this->listingProduct->getChildObject()->getOnlineQtySold();

        if ($from == $to) {
            return;
        }

        /** @var \Ess\M2ePro\Model\Connector\Connection\Response\Message $message */
        $message = $this->modelFactory->getObject('Connector_Connection_Response_Message');
        $message->initFromPreparedData(
            sprintf('QTY was revised from %s to %s', $from, $to),
            \Ess\M2ePro\Model\Connector\Connection\Response\Message::TYPE_SUCCESS
        );

        $this->getLogger()->logListingProductMessage($this->listingProduct, $message);
    }

    protected function processSuccessReviseVariations()
    {
        if (
            !$this->getRequestDataObject()->isVariationItem() ||
            !$this->getConfigurator()->isVariationsAllowed()
        ) {
            return;
        }

        $currency = $this->localeCurrency->getCurrency(
            $this->listingProduct->getMarketplace()->getChildObject()->getCurrency()
        );

        $requestMetadata = $this->getResponseObject()->getRequestMetaData();
        $variationMetadata = !empty($requestMetadata['variation_data']) ? $requestMetadata['variation_data'] : [];

        foreach ($this->listingProduct->getVariations(true) as $variation) {
            if (
                !isset($variationMetadata[$variation->getId()]['online_qty']) ||
                !isset($variationMetadata[$variation->getId()]['online_price'])
            ) {
                continue;
            }

            $sku = $variation->getChildObject()->getOnlineSku();
            $origPrice = $variationMetadata[$variation->getId()]['online_price'];
            $currentPrice = $variation->getChildObject()->getOnlinePrice();

            if ($currentPrice != $origPrice) {
                /** @var \Ess\M2ePro\Model\Connector\Connection\Response\Message $message */
                $message = $this->modelFactory->getObject('Connector_Connection_Response_Message');
                $message->initFromPreparedData(
                    sprintf(
                        'SKU %s: Price was revised from %s to %s',
                        $sku,
                        $currency->toCurrency($origPrice),
                        $currency->toCurrency($currentPrice)
                    ),
                    \Ess\M2ePro\Model\Connector\Connection\Response\Message::TYPE_SUCCESS
                );

                $this->getLogger()->logListingProductMessage($this->listingProduct, $message);
            }

            $origQty = $variationMetadata[$variation->getId()]['online_qty'];
            $currentQty = $variation->getChildObject()->getOnlineQty();

            if ($currentQty != $origQty) {
                /** @var \Ess\M2ePro\Model\Connector\Connection\Response\Message $message */
                $message = $this->modelFactory->getObject('Connector_Connection_Response_Message');
                $message->initFromPreparedData(
                    sprintf(
                        'SKU %s: QTY was revised from %s to %s',
                        $sku,
                        $origQty,
                        $currentQty
                    ),
                    \Ess\M2ePro\Model\Connector\Connection\Response\Message::TYPE_SUCCESS
                );

                $this->getLogger()->logListingProductMessage($this->listingProduct, $message);
            }
        }
    }

    /**
     * @return void|null
     * @throws \Ess\M2ePro\Model\Exception
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function eventAfterExecuting()
    {
        $responseMessages = $this->getResponse()->getMessages()->getEntities();

        if (
            $this->getStatusChanger() == \Ess\M2ePro\Model\Listing\Product::STATUS_CHANGER_SYNCH
            && $this->getConfigurator()->isIncludingMode()
            && $this->isProductIdentifierNeeded($responseMessages)
        ) {
            /** @var \Ess\M2ePro\Model\Connector\Connection\Response\Message $message */
            $message = $this->modelFactory->getObject('Connector_Connection_Response_Message');
            $message->initFromPreparedData(
                $this->getHelper('Module\Translation')->__(
                    'It has been detected that the Category you are using is going to require the Product Identifiers
                to be specified (UPC, EAN, ISBN, etc.). Full Revise will be automatically performed to send
                the value(s) of the required Identifier(s) based on the settings
                provided in the eBay Catalog Identifiers section of the Description Policy.'
                ),
                Message::TYPE_WARNING
            );
            $this->getLogger()->logListingProductMessage($this->listingProduct, $message);

            /** @var \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Configurator $configurator */
            $configurator = $this->modelFactory->getObject('Ebay_Listing_Product_Action_Configurator');
            $this->processAdditionalAction($this->getActionType(), $configurator);
        }

        if ($this->isVariationErrorAppeared($responseMessages) && $this->getRequestDataObject()->hasVariations()) {
            $this->tryToResolveVariationErrors();
        }

        parent::eventAfterExecuting();
    }
}
