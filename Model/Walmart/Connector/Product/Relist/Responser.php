<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

/**
 * @method \Ess\M2ePro\Model\Walmart\Listing\Product\Action\Type\Relist\Response getResponseObject()
 */

namespace Ess\M2ePro\Model\Walmart\Connector\Product\Relist;

/**
 * Class \Ess\M2ePro\Model\Walmart\Connector\Product\Relist\Responser
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

    protected function getSuccessfulMessage()
    {
        $currency = $this->localeCurrency->getCurrency(
            $this->listingProduct->getMarketplace()->getChildObject()->getCurrency()
        );

        return sprintf(
            'Product was Relisted with QTY %d, Price %s',
            $this->listingProduct->getChildObject()->getOnlineQty(),
            $currency->toCurrency($this->listingProduct->getChildObject()->getOnlinePrice())
        );
    }

    //########################################
}
