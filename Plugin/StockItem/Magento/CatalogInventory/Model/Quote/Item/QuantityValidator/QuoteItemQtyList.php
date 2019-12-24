<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Plugin\StockItem\Magento\CatalogInventory\Model\Quote\Item\QuantityValidator;

use \Ess\M2ePro\Model\Magento\Quote\Builder;

/**
 * Class \Ess\M2ePro\Plugin\StockItem\Magento\CatalogInventory\Model\Quote\Item\QuantityValidator\QuoteItemQtyList
 */
class QuoteItemQtyList extends \Ess\M2ePro\Plugin\AbstractPlugin
{
    //########################################

    public function aroundGetQty($interceptor, \Closure $callback, ...$arguments)
    {
        return $this->execute('getQty', $interceptor, $callback, $arguments);
    }

    /**
     * @param \Magento\CatalogInventory\Model\Quote\Item\QuantityValidator\QuoteItemQtyList $interceptor
     * @param \Closure $callback
     * @param array $arguments
     * @return mixed
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function processGetQty($interceptor, \Closure $callback, array $arguments)
    {
        $quoteItemId = $arguments[1];
        $quoteId     = $arguments[2];
        $itemQty     = &$arguments[3];

        if ($this->getHelper('Data\GlobalData')->getValue(Builder::PROCESS_QUOTE_ID) == $quoteId) {
            empty($quoteItemId) && $itemQty = 0;
        }

        return $callback(...$arguments);
    }

    //########################################
}
