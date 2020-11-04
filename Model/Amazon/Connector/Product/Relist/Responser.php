<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Connector\Product\Relist;

/**
 * @method \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\Relist\Response getResponseObject()
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
        $currency = $this->localeCurrency->getCurrency(
            $this->listingProduct->getMarketplace()->getChildObject()->getCurrency()
        );

        $parts = [
            sprintf('Product was Relisted with QTY %d', $this->listingProduct->getChildObject()->getOnlineQty())
        ];

        if ($regularPrice = $this->listingProduct->getChildObject()->getOnlineRegularPrice()) {
            $parts[] = sprintf('Regular Price %s', $currency->toCurrency($regularPrice));
        }

        if ($businessPrice = $this->listingProduct->getChildObject()->getOnlineBusinessPrice()) {
            $parts[] = sprintf('Business Price %s', $currency->toCurrency($businessPrice));
        }

        return implode(', ', $parts);
    }

    //########################################
}
