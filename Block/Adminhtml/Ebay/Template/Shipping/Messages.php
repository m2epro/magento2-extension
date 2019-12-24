<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Template\Shipping;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Ebay\Template\Shipping\Messages
 */
class Messages extends \Ess\M2ePro\Block\Adminhtml\Template\Messages
{
    const TYPE_CURRENCY_CONVERSION = 'currency_conversion';

    //########################################

    public function getMessages()
    {
        $messages = [];

        // ---------------------------------------
        $message = $this->getCurrencyConversionMessage();

        if ($message !== null) {
            $messages[self::TYPE_CURRENCY_CONVERSION] = $message;
        }
        // ---------------------------------------

        $messages = array_merge($messages, parent::getMessages());

        return $messages;
    }

    //########################################

    public function getCurrencyConversionMessage($marketplaceCurrency = null)
    {
        if ($this->getMarketplace() === null) {
            return null;
        }

        if ($marketplaceCurrency === null) {
            $marketplaceCurrency = $this->getMarketplace()->getChildObject()->getCurrency();
        }

        if (!$this->canDisplayCurrencyConversionMessage($marketplaceCurrency)) {
            return null;
        }

        $storePath = $this->helperFactory->getObject('Magento\Store')->getStorePath($this->getStore()->getId());
        $allowed   = $this->modelFactory->getObject('Currency')->isAllowed($marketplaceCurrency, $this->getStore());

        if (!$allowed) {
            $currencySetupUrl = $this->getUrl(
                'admin/system_config/edit',
                [
                    'section' => 'currency',
                    'website' => $this->getStore()->getId() != \Magento\Store\Model\Store::DEFAULT_STORE_ID
                                    ? $this->getStore()->getWebsite()->getId() : null,
                    'store'   => $this->getStore()->getId() != \Magento\Store\Model\Store::DEFAULT_STORE_ID
                                    ? $this->getStore()->getId() : null
                ]
            );

            return
                $this->__(
                    'Currency "%currency_code%" is not allowed in <a href="%url%" target="_blank">Currency Setup</a> '
                    . 'for Store View "%store_path%" of your Magento. '
                    . 'Currency conversion will not be performed.',
                    $marketplaceCurrency,
                    $currencySetupUrl,
                    $this->escapeHtml($storePath)
                );
        }

        $rate = $this->modelFactory->getObject('Currency')
            ->getConvertRateFromBase(
                $marketplaceCurrency,
                $this->getStore(),
                4
            );

        if ($rate == 0) {
            return
                $this->__(
                    'There is no rate for "%currency_from%-%currency_to%" in'
                    . ' <a href="%url%" target="_blank">Manage Currency Rates</a> of your Magento.'
                    . ' Currency conversion will not be performed.',
                    $this->getStore()->getBaseCurrencyCode(),
                    $marketplaceCurrency,
                    $this->getUrl('adminhtml/system_currency')
                );
        }

        $message =
            $this->__(
                'There is a rate %value% for "%currency_from%-%currency_to%" in'
                . ' <a href="%url%" target="_blank">Manage Currency Rates</a> of your Magento.'
                . ' Currency conversion will be performed automatically.',
                $rate,
                $this->getStore()->getBaseCurrencyCode(),
                $marketplaceCurrency,
                $this->getUrl('adminhtml/system_currency')
            );

        return '<span style="color: #3D6611 !important;">' . $message . '</span>';
    }

    //########################################

    protected function canDisplayCurrencyConversionMessage($marketplaceCurrency)
    {
        if ($this->getStore() === null) {
            return false;
        }

        if ($this->modelFactory->getObject('Currency')->isBase($marketplaceCurrency, $this->getStore())) {
            return false;
        }

        $template = $this->activeRecordFactory->getObject('Ebay_Template_Shipping');
        $template->addData($this->getTemplateData());

        $attributes = [];

        if ($template->getId()) {
            foreach ($template->getServices(true) as $service) {
                /** @var \Ess\M2ePro\Model\Ebay\Template\Shipping\Service $service */
                $attributes = array_merge($attributes, $service->getUsedAttributes());
            }
        } else {
            $shippingCostAttributes = $template->getData('shipping_cost_attribute');
            if (!empty($shippingCostAttributes)) {
                $attributes = array_merge($attributes, $shippingCostAttributes);
            }

            $shippingCostAdditionalAttributes = $template->getData('shipping_cost_additional_attribute');
            if (!empty($shippingCostAdditionalAttributes)) {
                $attributes = array_merge($attributes, $shippingCostAdditionalAttributes);
            }

            $shippingCostSurchargeAttributes = $template->getData('shipping_cost_surcharge_attribute');
            if (!empty($shippingCostSurchargeAttributes)) {
                $attributes = array_merge($attributes, $shippingCostSurchargeAttributes);
            }
        }

        $preparedAttributes = [];
        foreach (array_filter($attributes) as $attribute) {
            $preparedAttributes[] = ['code' => $attribute];
        }

        $attributes = $this->helperFactory->getObject('Magento\Attribute')->filterByInputTypes(
            $preparedAttributes,
            ['price']
        );

        if (!empty($attributes)) {
            return true;
        }

        return false;
    }

    //########################################
}
