<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Connector\Product\Revise;

use Ess\M2ePro\Model\Amazon\Listing\Product\Action\DataBuilder\Qty as DataBuilderQty;
use Ess\M2ePro\Model\ResourceModel\Amazon\Listing\Product as AmazonListingProductResource;

/**
 * @method \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\Revise\Response getResponseObject()
 */
class Responser extends \Ess\M2ePro\Model\Amazon\Connector\Product\Responser
{
    /** @var \Magento\Framework\Locale\CurrencyInterface */
    protected $localeCurrency;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\Tag\ListingProduct\Buffer $tagBuffer,
        \Ess\M2ePro\Model\Amazon\TagFactory $amazonTagFactory,
        \Ess\M2ePro\Model\TagFactory $baseTagFactory,
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
            $tagBuffer,
            $amazonTagFactory,
            $baseTagFactory,
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

        if (empty($sequenceStrings)) {
            return null;
        }

        if (count($sequenceStrings) == 1) {
            $verb = $isPlural ? 'were' : 'was';

            return $sequenceStrings[0] . ' ' . $verb . ' Revised';
        }

        return implode(', ', $sequenceStrings) . ' were Revised';
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

        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
        $amazonListingProduct = $this->listingProduct->getChildObject();

        $mapPriceFrom = (float)($amazonListingProduct->getOrigData(
            \Ess\M2ePro\Model\ResourceModel\Amazon\Listing\Product::COLUMN_ONLINE_REGULAR_MAP_PRICE
        ) ?? 0.0);
        $mapPriceTo = $amazonListingProduct->getOnlineRegularMapPrice();
        if ($mapPriceFrom !== $mapPriceTo) {
            $this->logSuccessMessage(
                sprintf(
                    'MAP Price was revised from %s to %s',
                    $currency->toCurrency($mapPriceFrom),
                    $currency->toCurrency($mapPriceTo)
                )
            );
        }

        $from = $amazonListingProduct->getOrigData('online_regular_price');
        $to = $amazonListingProduct->getOnlineRegularPrice();
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
        if ($this->getRequestDataObject()->hasDeleteBusinessPriceFlag()) {
            $this->logSuccessMessage('Business Price was removed');
        }

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

        $handlingTimeFrom = (int)$amazonListingProduct->getOrigData('online_handling_time');
        $handlingTimeTo = $amazonListingProduct->getOnlineHandlingTime();

        if ($handlingTimeFrom != $handlingTimeTo) {
            $this->logSuccessMessage(
                sprintf('Handling Time was revised from %s to %s', $handlingTimeFrom, $handlingTimeTo)
            );
        }

        $qtyFrom = $amazonListingProduct->getOrigData(AmazonListingProductResource::COLUMN_ONLINE_QTY);
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

    protected function getSuccessfulParams(): array
    {
        $successfulParams = [];
        $responseData = $this->getPreparedResponseData();

        if (!empty($responseData['system_items_update_request_date'])) {
            $successfulParams['system_items_update_request_date'] = $responseData['system_items_update_request_date'];
        }

        return $successfulParams;
    }
}
