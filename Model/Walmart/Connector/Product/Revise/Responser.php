<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

/**
 * @method \Ess\M2ePro\Model\Walmart\Listing\Product\Action\Type\Revise\Response getResponseObject()
 */

namespace Ess\M2ePro\Model\Walmart\Connector\Product\Revise;

use Ess\M2ePro\Model\Walmart\Listing\Product\Action\Type\Revise\Request as ReviseRequest;

/**
 * Class \Ess\M2ePro\Model\Walmart\Connector\Product\Revise\Responser
 */
class Responser extends \Ess\M2ePro\Model\Walmart\Connector\Product\Responser
{
    /** @var \Magento\Framework\Locale\CurrencyInterface */
    protected $localeCurrency;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
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

        if ($this->getConfigurator()->isPromotionsAllowed()) {
            $sequenceStrings[] = 'Promotions details';
            $isPlural = true;
        }

        if ($this->getConfigurator()->isDetailsAllowed()) {
            if ($this->getRequestDataObject()->getIsNeedSkuUpdate()) {
                $sequenceStrings[] = 'SKU';
            }

            if ($this->getRequestDataObject()->getIsNeedProductIdUpdate()) {
                $ids = $this->getResponseObject()->getRequestMetaData(ReviseRequest::PRODUCT_ID_UPDATE_METADATA_KEY);
                !empty($ids) && $sequenceStrings[] = strtoupper($ids['type']);
            }

            $sequenceStrings[] = 'Details';
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

    protected function processSuccess(array $params = [])
    {
        parent::processSuccess($params);

        $this->processSuccessRevisePrice();
        $this->processSuccessReviseQty();
    }

    protected function processSuccessRevisePrice()
    {
        if (!$this->getConfigurator()->isPriceAllowed()) {
            return;
        }

        $currency = $this->localeCurrency->getCurrency(
            $this->listingProduct->getMarketplace()->getChildObject()->getCurrency()
        );

        $from = $this->listingProduct->getChildObject()->getOrigData('online_price');
        $to = $this->listingProduct->getChildObject()->getOnlinePrice();
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
        if (!$this->getConfigurator()->isQtyAllowed()) {
            return;
        }

        $from = $this->listingProduct->getChildObject()->getOrigData('online_qty');
        $to = $this->listingProduct->getChildObject()->getOnlineQty();
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

    //########################################
}
