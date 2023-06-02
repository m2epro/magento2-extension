<?php

namespace Ess\M2ePro\Model\Magento\Quote\Total;

use Ess\M2ePro\Model\Magento\Tax\Rule\Builder as TaxRuleBuilder;
use Magento\Tax\Model\Sales\Total\Quote\CommonTaxCollector;

class RoundTaxPercent extends \Magento\Quote\Model\Quote\Address\Total\AbstractTotal
{
    public const PRODUCT_PRICE_TAX_DATA_KEY = 'm2e_product_price_tax';
    public const SHIPPING_PRICE_TAX_DATA_KEY = 'm2e_shipping_price_tax';

    public function collect(
        \Magento\Quote\Model\Quote $quote,
        \Magento\Quote\Api\Data\ShippingAssignmentInterface $shippingAssignment,
        \Magento\Quote\Model\Quote\Address\Total $total
    ) {
        /** @var \Ess\M2ePro\Model\Order\Tax\PriceTaxRateInterface $productPriceTaxRate */
        if ($productPriceTaxRate = $quote->getDataByKey(self::PRODUCT_PRICE_TAX_DATA_KEY)) {
            $this->processForPriceTax(TaxRuleBuilder::TAX_RATE_CODE_PRODUCT, $total, $productPriceTaxRate);
            $this->processForQuoteItems($productPriceTaxRate, $shippingAssignment);
        }

        /** @var \Ess\M2ePro\Model\Order\Tax\PriceTaxRateInterface $shippingPriceTaxRate */
        if ($shippingPriceTaxRate = $quote->getDataByKey(self::SHIPPING_PRICE_TAX_DATA_KEY)) {
            $this->processForPriceTax(TaxRuleBuilder::TAX_RATE_CODE_SHIPPING, $total, $shippingPriceTaxRate);
        }

        return $this;
    }

    private function processForPriceTax(
        string $ruleCode,
        \Magento\Quote\Model\Quote\Address\Total $total,
        \Ess\M2ePro\Model\Order\Tax\PriceTaxRateInterface $taxRate
    ): void {
        if (!$this->updatePercentForAppliedTax($ruleCode, $total, $taxRate)) {
            return;
        }
        $this->updatePercentForItemsAppliedTaxes($ruleCode, $total, $taxRate);
    }

    private function updatePercentForAppliedTax(
        string $ruleCode,
        \Magento\Quote\Model\Quote\Address\Total $total,
        \Ess\M2ePro\Model\Order\Tax\PriceTaxRateInterface $taxRate
    ): bool {
        $appliedTaxes = $total->getData(CommonTaxCollector::KEY_TAX_DETAILS_APPLIED_TAXES);
        if (empty($appliedTaxes[$ruleCode])) {
            return false;
        }

        $isUpdated = false;
        foreach ($appliedTaxes as $key => &$appliedTax) {
            if ($key !== $ruleCode || empty($appliedTax['percent'])) {
                continue;
            }

            $appliedTax['percent'] = $this->replaceTaxPercent($appliedTax['percent'], $taxRate);
            $isUpdated = true;

            if (empty($appliedTax['rates'])) {
                continue;
            }

            foreach ($appliedTax['rates'] as &$rate) {
                if (isset($rate['code']) && $rate['code'] === $ruleCode) {
                    $rate['percent'] = $this->replaceTaxPercent($rate['percent'], $taxRate);
                }
            }
        }

        $total->setData(CommonTaxCollector::KEY_TAX_DETAILS_APPLIED_TAXES, $appliedTaxes);

        return $isUpdated;
    }

    /**
     * @param float|int $inputPercent
     * @param \Ess\M2ePro\Model\Order\Tax\PriceTaxRateInterface $taxRate
     *
     * @return float|int
     */
    private function replaceTaxPercent($inputPercent, \Ess\M2ePro\Model\Order\Tax\PriceTaxRateInterface $taxRate)
    {
        if ($inputPercent === $taxRate->getNotRoundedValue()) {
            return $taxRate->getValue();
        }

        return $inputPercent;
    }

    private function updatePercentForItemsAppliedTaxes(
        string $ruleCode,
        \Magento\Quote\Model\Quote\Address\Total $total,
        \Ess\M2ePro\Model\Order\Tax\PriceTaxRateInterface $taxRate
    ): void {
        $itemsAppliedTaxes = $total->getDataByKey('items_applied_taxes');
        if (empty($itemsAppliedTaxes)) {
            return;
        }

        foreach ($itemsAppliedTaxes as &$itemAppliedTax) {
            foreach ($itemAppliedTax as &$item) {
                if (empty($item['id']) || $item['id'] !== $ruleCode) {
                    continue;
                }

                $item['percent'] = $this->replaceTaxPercent($item['percent'], $taxRate);
                if (empty($item['rates'])) {
                    continue;
                }

                foreach ($item['rates'] as &$rate) {
                    if (isset($rate['code']) && $rate['code'] === $ruleCode) {
                        $rate['percent'] = $this->replaceTaxPercent($rate['percent'], $taxRate);
                    }
                }
            }
        }

        $total->setData('items_applied_taxes', $itemsAppliedTaxes);
    }

    private function processForQuoteItems(
        \Ess\M2ePro\Model\Order\Tax\PriceTaxRateInterface $taxRate,
        \Magento\Quote\Api\Data\ShippingAssignmentInterface $shippingAssignment
    ): void {
        /** @var \Magento\Quote\Model\Quote\Item $quoteItem */
        foreach ($shippingAssignment->getItems() as $quoteItem) {
            if ($quoteItem && !$quoteItem->isDeleted()) {
                $this->updatePercentForQuoteItem($quoteItem, $taxRate);
            }
        }
    }

    private function updatePercentForQuoteItem(
        \Magento\Quote\Model\Quote\Item $quoteItem,
        \Ess\M2ePro\Model\Order\Tax\PriceTaxRateInterface $taxRate
    ): void {
        $appliedTaxes = $quoteItem->getDataByKey(CommonTaxCollector::KEY_TAX_DETAILS_APPLIED_TAXES);
        if (empty($appliedTaxes)) {
            return;
        }

        $isUpdated = false;
        foreach ($appliedTaxes as &$appliedTax) {
            if (empty($appliedTax['id']) || $appliedTax['id'] !== TaxRuleBuilder::TAX_RATE_CODE_PRODUCT) {
                continue;
            }

            $appliedTax['percent'] = $this->replaceTaxPercent($appliedTax['percent'], $taxRate);

            $isUpdated = true;
            if (empty($appliedTax['rates'])) {
                continue;
            }
            foreach ($appliedTax['rates'] as &$rate) {
                if (isset($rate['code']) && $rate['code'] === TaxRuleBuilder::TAX_RATE_CODE_PRODUCT) {
                    $rate['percent'] = $this->replaceTaxPercent($rate['percent'], $taxRate);
                }
            }
        }

        if ($isUpdated) {
            $quoteItem->setTaxPercent(
                $this->replaceTaxPercent($quoteItem->getTaxPercent(), $taxRate)
            );
        }

        $quoteItem->setData(CommonTaxCollector::KEY_TAX_DETAILS_APPLIED_TAXES, $appliedTaxes);
    }
}
