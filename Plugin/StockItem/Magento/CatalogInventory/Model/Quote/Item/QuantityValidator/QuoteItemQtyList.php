<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Plugin\StockItem\Magento\CatalogInventory\Model\Quote\Item\QuantityValidator;

class QuoteItemQtyList extends \Ess\M2ePro\Plugin\AbstractPlugin
{
    //########################################

    /**
     * @param \Magento\CatalogInventory\Model\Quote\Item\QuantityValidator\QuoteItemQtyList $interceptor
     * @param \Closure $callback
     * @param mixed ...$arguments
     * @return mixed
     * @throws \Ess\M2ePro\Model\Exception
     */
    public function aroundGetQty($interceptor, \Closure $callback, ...$arguments)
    {
        return $this->execute('getQty', $interceptor, $callback, $arguments);
    }

    /**
     * @param \Magento\CatalogInventory\Model\Quote\Item\QuantityValidator\QuoteItemQtyList $interceptor
     * @param \Closure $callback
     * @param mixed ...$arguments
     * @return mixed
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function processGetQty($interceptor, \Closure $callback, ...$arguments)
    {
        /** @var \Ess\M2ePro\Helper\Data\GlobalData $globalDataHelper */
        $globalDataHelper = $this->getHelper('Data\GlobalData');

        $currentProcessQuoteItemId = empty($arguments[0][1]) ? NULL : (int)$arguments[0][1];
        $currentProcessQuoteId = empty($arguments[0][2]) ? NULL : (int)$arguments[0][2];
        $quoteId = (int)$globalDataHelper->getValue(\Ess\M2ePro\Model\Magento\Quote\Builder::PROCESS_QUOTE_ID);

        if ($quoteId !== $currentProcessQuoteId) {
            return $callback(...$arguments[0]);
        }

        // 4rd argument is qty in quote item
        empty($currentProcessQuoteItemId) && $arguments[0][3] = 0;

        return $callback(...$arguments[0]);
    }

    //########################################
}