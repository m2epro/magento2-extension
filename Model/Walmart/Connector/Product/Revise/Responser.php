<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Walmart\Connector\Product\Revise;

use Ess\M2ePro\Model\ResourceModel\Walmart\Listing\Product as WalmartListingProductResource;
use Ess\M2ePro\Model\Walmart\Listing\Product\Action\Type\Revise\Request as ReviseRequest;

/**
 * @method \Ess\M2ePro\Model\Walmart\Listing\Product\Action\Type\Revise\Response getResponseObject()
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

            return $sequenceStrings[0] . ' ' . $verb . ' Revised';
        }

        return implode(', ', $sequenceStrings) . ' were Revised';
    }

    protected function processSuccess(array $params = [])
    {
        parent::processSuccess($params);

        $this->processSuccessRevisePrice();
        $this->processSuccessReviseQty();
        $this->processSuccessReviseRepricer();
    }

    protected function processSuccessRevisePrice()
    {
        if (!$this->getConfigurator()->isPriceAllowed()) {
            return;
        }

        $currency = $this->localeCurrency
            ->getCurrency($this->getWalmartListingProduct()->getWalmartMarketplace()->getCurrency());

        $from = $this->getWalmartListingProduct()->getOrigData('online_price');
        $to = $this->getWalmartListingProduct()->getOnlinePrice();
        if ($from == $to) {
            return;
        }

        $message = $this->createSuccessMessage(
            sprintf(
                'Price was revised from %s to %s',
                $currency->toCurrency($from),
                $currency->toCurrency($to)
            )
        );

        $this->getLogger()->logListingProductMessage($this->listingProduct, $message);
    }

    protected function processSuccessReviseQty()
    {
        if (!$this->getConfigurator()->isQtyAllowed()) {
            return;
        }

        $from = $this->getWalmartListingProduct()->getOrigData('online_qty');
        $to = $this->getWalmartListingProduct()->getOnlineQty();
        if ($from == $to) {
            return;
        }

        $message = $this->createSuccessMessage(
            sprintf('QTY was revised from %s to %s', $from, $to)
        );

        $this->getLogger()->logListingProductMessage($this->listingProduct, $message);
    }

    private function processSuccessReviseRepricer(): void
    {
        if (!$this->getConfigurator()->isPriceAllowed()) {
            return;
        }

        $currency = $this->localeCurrency
            ->getCurrency($this->getWalmartListingProduct()->getWalmartMarketplace()->getCurrency());

        $messages = [];

        $oldRepricerStrategyName = (string)$this
            ->getWalmartListingProduct()
            ->getOrigData(WalmartListingProductResource::COLUMN_ONLINE_REPRICER_STRATEGY_NAME);
        $newRepricerStrategyName = (string)$this->getWalmartListingProduct()->getOnlineRepricerStrategyName();

        if ($oldRepricerStrategyName !== $newRepricerStrategyName) {
            $messages[] = $this->createSuccessMessage(
                sprintf(
                    'Repricer Strategy was revised from "%s" to "%s"',
                    $oldRepricerStrategyName,
                    $newRepricerStrategyName
                )
            );
        }

        $oldMinPrice = (float)$this
            ->getWalmartListingProduct()
            ->getOrigData(WalmartListingProductResource::COLUMN_ONLINE_REPRICER_MIN_PRICE);
        $newMinPrice = (float)$this->getWalmartListingProduct()->getOnlineRepricerMinPrice();

        if ($oldMinPrice !== $newMinPrice) {
            $messages[] = $this->createSuccessMessage(
                sprintf(
                    'Repricer Min Price was revised from %s to %s',
                    $currency->toCurrency($oldMinPrice),
                    $currency->toCurrency($newMinPrice)
                )
            );
        }

        $oldMaxPrice = (float)$this
            ->getWalmartListingProduct()
            ->getOrigData(WalmartListingProductResource::COLUMN_ONLINE_REPRICER_MAX_PRICE);
        $newMaxPrice = (float)$this->getWalmartListingProduct()->getOnlineRepricerMaxPrice();
        if ($oldMaxPrice !== $newMaxPrice) {
            $messages[] = $this->createSuccessMessage(
                sprintf(
                    'Repricer Max Price was revised from %s to %s',
                    $currency->toCurrency($oldMaxPrice),
                    $currency->toCurrency($newMaxPrice)
                )
            );
        }

        foreach ($messages as $message) {
            $this
                ->getLogger()
                ->logListingProductMessage($this->listingProduct, $message);
        }
    }

    private function getWalmartListingProduct(): \Ess\M2ePro\Model\Walmart\Listing\Product
    {
        return $this->listingProduct->getChildObject();
    }

    private function createSuccessMessage(string $text): \Ess\M2ePro\Model\Connector\Connection\Response\Message
    {
        $message = $this->modelFactory
            ->getObjectByClass(\Ess\M2ePro\Model\Connector\Connection\Response\Message::class);

        $message->initFromPreparedData(
            $text,
            \Ess\M2ePro\Model\Connector\Connection\Response\Message::TYPE_SUCCESS
        );

        return $message;
    }
}
