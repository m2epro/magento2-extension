<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Observer\Order\Quote\Address\Collect\Totals;

use Magento\Framework\Pricing\PriceCurrencyInterface;

class After extends \Ess\M2ePro\Observer\AbstractModel
{
    /** @var PriceCurrencyInterface */
    private $priceCurrency;

    /**
     * @param \Ess\M2ePro\Helper\Factory $helperFactory
     * @param \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory
     * @param \Ess\M2ePro\Model\Factory $modelFactory
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
     */
    public function __construct(
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        PriceCurrencyInterface $priceCurrency
    ) {
        parent::__construct($helperFactory, $activeRecordFactory, $modelFactory);

        $this->priceCurrency = $priceCurrency;
    }

    /**
     * @return void
     */
    public function process(): void
    {
        /** @var \Magento\Quote\Model\Quote\Address\Total $total */
        /** @var \Magento\Quote\Model\Quote $quote */
        $total = $this->getEvent()->getTotal();
        $quote = $this->getEvent()->getQuote();

        if ($quote->getIsM2eProQuote() && $quote->getUseM2eProDiscount()) {
            $discountAmount = $this->priceCurrency->convert($quote->getCoinDiscount());
        
            if ($total->getTotalAmount('subtotal')) {
                $total->setTotalAmount('subtotal', $total->getTotalAmount('subtotal') - $discountAmount);
            }
        
            if ($total->getBaseTotalAmount('subtotal')) {
                $total->setTotalAmount('subtotal', $total->getBaseTotalAmount('subtotal') - $discountAmount);
            }
        
            if ($total->hasData('grand_total') && $total->getGrandTotal()) {
                $total->setGrandTotal($total->getGrandTotal() - $discountAmount);
            }
        
            if ($total->hasData('base_grand_total') && $total->getBaseGrandTotal()) {
                $total->setBaseGrandTotal($total->getBaseGrandTotal() - $discountAmount);
            }
        }
    }
}
