<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Connector\Product\ListAction;

/**
 * @method \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\ListAction\Response getResponseObject()
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

    protected function getSuccessfulMessage()
    {
        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
        $amazonListingProduct = $this->listingProduct->getChildObject();
        $currency = $this->localeCurrency->getCurrency(
            $this->listingProduct->getMarketplace()->getChildObject()->getCurrency()
        );
        $parts = [];

        if ($amazonListingProduct->getVariationManager()->isRelationParentType()) {
            $parts[] = 'Parent Product was Listed';
        } else {
            $parts[] = sprintf('Product was Listed with QTY %d', $amazonListingProduct->getOnlineQty());
        }

        if ($regularPrice = $amazonListingProduct->getOnlineRegularPrice()) {
            $parts[] = sprintf('Regular Price %s', $currency->toCurrency($regularPrice));
        }

        if ($businessPrice = $amazonListingProduct->getOnlineBusinessPrice()) {
            $parts[] = sprintf('Business Price %s', $currency->toCurrency($businessPrice));
        }

        return implode(', ', $parts);
    }

    //########################################

    protected function processSuccess(array $params = [])
    {
        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
        $amazonListingProduct = $this->listingProduct->getChildObject();

        if ($amazonListingProduct->getVariationManager()->isRelationMode() &&
            !$this->getRequestDataObject()->hasProductId() &&
            empty($params['general_id'])
        ) {
            /** @var \Ess\M2ePro\Model\Connector\Connection\Response\Message $message */
            $message = $this->modelFactory->getObject('Connector_Connection_Response_Message');
            $message->initFromPreparedData(
                'Unexpected error. The ASIN/ISBN for Parent or Child Product was not returned from Amazon.
                 Operation cannot be finished correctly.',
                \Ess\M2ePro\Model\Connector\Connection\Response\Message::TYPE_ERROR
            );

            $this->getLogger()->logListingProductMessage($this->listingProduct, $message);
            return;
        }

        parent::processSuccess($params);
    }

    protected function getSuccessfulParams()
    {
        $responseData = $this->getPreparedResponseData();

        if (empty($responseData['asins'])) {
            return [];
        }

        return ['general_id' => $responseData['asins']];
    }

    //########################################
}
