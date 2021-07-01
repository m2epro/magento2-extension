<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Connector\Product\Revise;

use \Ess\M2ePro\Model\Amazon\Listing\Product\Action\DataBuilder\Qty as DataBuilderQty;

/**
 * @method \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\Revise\Response getResponseObject()
 */
class Responser extends \Ess\M2ePro\Model\Amazon\Connector\Product\Responser
{
    /** @var \Magento\Framework\Locale\CurrencyInterface */
    protected $localeCurrency;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\Connector\Connection\Response $response,
        \Magento\Framework\Locale\CurrencyInterface $localeCurrency,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $params = []
    ) {
        $this->localeCurrency = $localeCurrency;
        parent::__construct(
            $amazonFactory,
            $activeRecordFactory,
            $walmartFactory,
            $ebayFactory,
            $response,
            $helperFactory,
            $modelFactory,
            $params
        );
    }

    //########################################

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

        if ($this->getConfigurator()->isQtyAllowed()) {
            $params = $this->getResponseObject()->getParams();

            if (!empty($params['switch_to']) && $params['switch_to'] === DataBuilderQty::FULFILLMENT_MODE_AFN) {
                return 'Item was switched to AFN';
            }

            if (!empty($params['switch_to']) && $params['switch_to'] === DataBuilderQty::FULFILLMENT_MODE_MFN) {
                return 'Item was switched to MFN';
            }
        }

        if ($this->getConfigurator()->isDetailsAllowed()) {
            $sequenceStrings[] = 'Details';
            $isPlural = true;
        }

        if ($this->getConfigurator()->isImagesAllowed()) {
            $sequenceStrings[] = 'Images';
            $isPlural = true;
        }

        if (empty($sequenceStrings)) {
            return null;
        }

        if (count($sequenceStrings) == 1) {
            $verb = $isPlural ? 'were' : 'was';
            return $sequenceStrings[0].' '.$verb.' Revised';
        }

        return implode(', ', $sequenceStrings).' were Revised';
    }

    //########################################

    protected function processSuccess(array $params = [])
    {
        parent::processSuccess($params);

        $this->processSuccessReviseRegularPrice();
        $this->processSuccessReviseBusinessPrice();
        $this->processSuccessReviseQty();
    }

    protected function processSuccessReviseRegularPrice()
    {
        if (!$this->getConfigurator()->isRegularPriceAllowed()) {
            return;
        }

        $currency = $this->localeCurrency->getCurrency(
            $this->listingProduct->getMarketplace()->getChildObject()->getCurrency()
        );

        $from = $this->listingProduct->getChildObject()->getOrigData('online_regular_price');
        $to = $this->listingProduct->getChildObject()->getOnlineRegularPrice();
        if ($from != $to) {
            $this->logSuccessMessage(
                sprintf(
                    'Regular Price was revised from %s to %s',
                    $currency->toCurrency($from),
                    $currency->toCurrency($to)
                )
            );
        }
    }

    protected function processSuccessReviseBusinessPrice()
    {
        if (!$this->getConfigurator()->isBusinessPriceAllowed()) {
            return;
        }

        $currency = $this->localeCurrency->getCurrency(
            $this->listingProduct->getMarketplace()->getChildObject()->getCurrency()
        );

        $from = $this->listingProduct->getChildObject()->getOrigData('online_business_price');
        $to = $this->listingProduct->getChildObject()->getOnlineBusinessPrice();
        if ($from != $to) {
            $this->logSuccessMessage(
                sprintf(
                    'Business Price was revised from %s to %s',
                    $currency->toCurrency($from),
                    $currency->toCurrency($to)
                )
            );
        }
    }

    protected function processSuccessReviseQty()
    {
        if (!$this->getConfigurator()->isQtyAllowed()) {
            return;
        }

        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
        $amazonListingProduct = $this->listingProduct->getChildObject();
        
        $handlingTimeFrom = $amazonListingProduct->getOrigData('online_handling_time');
        $handlingTimeTo = $amazonListingProduct->getOnlineHandlingTime();

        if ($handlingTimeFrom != $handlingTimeTo) {
            $this->logSuccessMessage(
                sprintf('Handling Time was revised from %s to %s', $handlingTimeFrom, $handlingTimeTo)
            );
        }

        $qtyFrom = $amazonListingProduct->getOrigData('online_qty');
        $qtyTo = $amazonListingProduct->getOnlineQty();
        if ($qtyFrom != $qtyTo) {
            $this->logSuccessMessage(sprintf('QTY was revised from %s to %s', $qtyFrom, $qtyTo));
        }
    }

    //########################################
    
    protected function logSuccessMessage($text)
    {
        /** @var \Ess\M2ePro\Model\Connector\Connection\Response\Message $message */
        $message = $this->modelFactory->getObject('Connector_Connection_Response_Message');
        $message->initFromPreparedData($text, \Ess\M2ePro\Model\Connector\Connection\Response\Message::TYPE_SUCCESS);
        $this->getLogger()->logListingProductMessage($this->listingProduct, $message);
    }
    
    //########################################
}
