<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Magento\Quote\Total;

use Ess\M2ePro\Model\Magento\Tax\Rule\Builder as TaxRuleBuilder;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Ess\M2ePro\Model\Order\Tax\ProductPriceTaxInterface as ProductPriceTax;

class RoundTaxPercent extends \Magento\Tax\Model\Sales\Total\Quote\Tax
{
    public const PRODUCT_PRICE_TAX_DATA_KEY = 'm2e_product_price_tax';

    /** @var \Ess\M2ePro\Model\Order\Tax\ProductPriceTaxInterface */
    private $productPriceTax;

    /**
     * @inerhitDoc
     */
    public function collect(
        \Magento\Quote\Model\Quote $quote,
        \Magento\Quote\Api\Data\ShippingAssignmentInterface $shippingAssignment,
        \Magento\Quote\Model\Quote\Address\Total $total
    ) {
        /** @var \Ess\M2ePro\Model\Order\Tax\ProductPriceTaxInterface $productPriceTax */
        $productPriceTax = $quote->getDataByKey(self::PRODUCT_PRICE_TAX_DATA_KEY);
        if (empty($productPriceTax)) {
            return;
        }

        $getCodesOfItemsTaxDetails = $this->getCodesOfItemsTaxDetails($shippingAssignment, $total);
        if (empty($getCodesOfItemsTaxDetails)) {
            return;
        }

        $this->setProductPrice($productPriceTax);
        if (!$this->updatePercentForAppliedTaxes($total)) {
            return;
        }

        $this->updatePercentForItemsAppliedTaxes($total);

        $this->processQuoteItems(
            $getCodesOfItemsTaxDetails,
            $shippingAssignment,
            $total
        );
    }

    /**
     * @param \Magento\Quote\Api\Data\ShippingAssignmentInterface $shippingAssignment
     * @param \Magento\Quote\Model\Quote\Address\Total $total
     *
     * @return void
     */
    private function getCodesOfItemsTaxDetails(
        ShippingAssignmentInterface $shippingAssignment,
        \Magento\Quote\Model\Quote\Address\Total $total
    ): array {
        $baseTaxDetails = $this->getQuoteTaxDetails($shippingAssignment, $total, true);
        $taxDetails = $this->getQuoteTaxDetails($shippingAssignment, $total, false);

        $itemsByType = $this->organizeItemTaxDetailsByType($taxDetails, $baseTaxDetails);

        if (empty($itemsByType[self::ITEM_TYPE_PRODUCT])) {
            return [];
        }

        return array_keys($itemsByType[self::ITEM_TYPE_PRODUCT]);
    }

    /**
     * @param \Magento\Framework\DataObject $dataObject
     * @param bool $filterByKey
     *
     * @return bool
     */
    private function updatePercentForAppliedTaxes(
        \Magento\Framework\DataObject $dataObject,
        bool $filterByKey = true
    ): bool {
        $appliedTaxes = $dataObject->getDataByPath(
            \Magento\Tax\Model\Sales\Total\Quote\CommonTaxCollector::KEY_TAX_DETAILS_APPLIED_TAXES
        );

        if (empty($appliedTaxes)) {
            return false;
        }

        foreach ($appliedTaxes as $key => &$appliedTax) {
            if ($filterByKey && $key !== TaxRuleBuilder::TAX_RATE_CODE_PRODUCT) {
                continue;
            }

            $this->updatePercentForAppliedTaxArray($appliedTax);
        }

        $dataObject->setData(
            \Magento\Tax\Model\Sales\Total\Quote\CommonTaxCollector::KEY_TAX_DETAILS_APPLIED_TAXES,
            $appliedTaxes
        );

        return true;
    }

    /**
     * @param \Magento\Quote\Model\Quote\Address\Total $total
     *
     * @return void
     */
    private function updatePercentForItemsAppliedTaxes(\Magento\Quote\Model\Quote\Address\Total $total): void
    {
        $itemsAppliedTaxes = $total->getItemsAppliedTaxes();

        if (empty($itemsAppliedTaxes)) {
            return;
        }

        foreach ($itemsAppliedTaxes as &$itemAppliedTax) {
            foreach ($itemAppliedTax as &$item) {
                $this->updatePercentForAppliedTaxArray($item);
            }
        }

        $total->setItemsAppliedTaxes($itemsAppliedTaxes);
    }

    /**
     * @param array $appliedTax
     *
     * @return void
     */
    private function updatePercentForAppliedTaxArray(array &$appliedTax): void
    {
        if (empty($appliedTax['id']) || $appliedTax['id'] !== TaxRuleBuilder::TAX_RATE_CODE_PRODUCT) {
            return;
        }

        $appliedTax['percent'] = $this->replacePercent($appliedTax['percent']);

        if (!isset($appliedTax['rates'])) {
            return;
        }

        foreach ($appliedTax['rates'] as &$rate) {
            if (isset($rate['code']) && $rate['code'] === TaxRuleBuilder::TAX_RATE_CODE_PRODUCT) {
                $rate['percent'] = $this->replacePercent($rate['percent']);
            }
        }
    }

    /**
     * @param array $codesOfItemTaxDetails
     * @param \Magento\Quote\Api\Data\ShippingAssignmentInterface $shippingAssignment
     * @param \Magento\Quote\Model\Quote\Address\Total $total
     *
     * @return void
     */
    private function processQuoteItems(
        array $codesOfItemTaxDetails,
        ShippingAssignmentInterface $shippingAssignment,
        \Magento\Quote\Model\Quote\Address\Total $total
    ): void {
        $keyedAddressItems = [];
        foreach ($shippingAssignment->getItems() as $addressItem) {
            if ($calculationItemId = $addressItem->getTaxCalculationItemId()) {
                $keyedAddressItems[$calculationItemId] = $addressItem;
            }
        }

        foreach ($codesOfItemTaxDetails as $code) {
            /** @var \Magento\Quote\Model\Quote\Item $quoteItem */
            $quoteItem = $keyedAddressItems[$code] ?? null;
            if ($quoteItem && !$quoteItem->isDeleted()) {
                $quoteItem->setTaxPercent(
                    $this->replacePercent($quoteItem->getTaxPercent())
                );
                $this->updatePercentForAppliedTaxes($quoteItem, false);
            }
        }
    }

    /**
     * @param float|int $inputPercent
     *
     * @return float|int
     */
    private function replacePercent($inputPercent)
    {
        $productPriceTax = $this->getProductPriceTax();
        if ($inputPercent === $productPriceTax->getNotRoundedTaxRateValue()) {
            return $productPriceTax->getTaxRateValue();
        }
        return $inputPercent;
    }

    /**
     * @param \Ess\M2ePro\Model\Order\Tax\ProductPriceTaxInterface $productPriceTax
     *
     * @return void
     */
    private function setProductPrice(ProductPriceTax $productPriceTax): void
    {
        $this->productPriceTax = $productPriceTax;
    }

    /**
     * @return \Ess\M2ePro\Model\Order\Tax\ProductPriceTaxInterface
     */
    private function getProductPriceTax(): ProductPriceTax
    {
        return $this->productPriceTax;
    }
}
