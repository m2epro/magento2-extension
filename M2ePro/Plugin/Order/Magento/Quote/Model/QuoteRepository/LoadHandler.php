<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Plugin\Order\Magento\Quote\Model\QuoteRepository;

use \Ess\M2ePro\Model\Magento\Quote\Builder;

/**
 * Class \Ess\M2ePro\Plugin\Order\Magento\Quote\Model\QuoteRepository\LoadHandler
 */
class LoadHandler extends \Ess\M2ePro\Plugin\AbstractPlugin
{
    //########################################

    public function aroundLoad($interceptor, \Closure $callback, ...$arguments)
    {
        return $this->execute('load', $interceptor, $callback, $arguments);
    }

    // ---------------------------------------

    /**
     * setIsSuperMode() is getting lost while a quote is reloaded by repository
     *
     * @param \Magento\Quote\Model\QuoteRepository\LoadHandler $interceptor
     * @param \Closure $callback
     * @param array $arguments
     * @return \Magento\Quote\Api\Data\CartInterface
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function processLoad($interceptor, \Closure $callback, array $arguments)
    {
        /** @var \Magento\Quote\Api\Data\CartInterface $quote */
        $quote = $arguments[0];

        if ($this->getHelper('Data\GlobalData')->getValue(Builder::PROCESS_QUOTE_ID) == $quote->getId()) {
            $quote->setIsSuperMode(true);
        }

        return $callback(...$arguments);
    }

    //########################################
}
