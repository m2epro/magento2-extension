<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Connector\Item\ListAction;

use Ess\M2ePro\Model\Connector\Connection\Response\Message;
use Ess\M2ePro\Model\Ebay\Listing\Product\Variation as EbayVariation;

/**
 * Class \Ess\M2ePro\Model\Ebay\Connector\Item\ListAction\Responser
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
                'Product was Listed with QTY %d, Price %s - %s',
                $onlineQty,
                $currency->toCurrency($this->getRequestDataObject()->getVariationMinPrice($calculateWithEmptyQty)),
                $currency->toCurrency($this->getRequestDataObject()->getVariationMaxPrice($calculateWithEmptyQty))
            );
        }

        return sprintf(
            'Product was Listed with QTY %d, Price %s',
            $onlineQty,
            $currency->toCurrency($this->listingProduct->getChildObject()->getOnlineCurrentPrice())
        );
    }

    //########################################

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
