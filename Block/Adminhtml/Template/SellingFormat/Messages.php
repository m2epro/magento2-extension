<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Template\SellingFormat;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Template\SellingFormat\Messages
 */
class Messages extends \Ess\M2ePro\Block\Adminhtml\Template\Messages
{
    const TYPE_CURRENCY_CONVERSION = 'currency_conversion';

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

        $storePath = $this->getHelper('Magento\Store')->getStorePath($this->getStore()->getId());
        $allowed = $this->modelFactory->getObject('Currency')
            ->isAllowed(
                $marketplaceCurrency,
                $this->getStore()
            );

        if (!$allowed) {
            $currencySetupUrl = $this->getUrl(
                'admin/system_config/edit',
                [
                    'section' => 'currency',
                    'website' => $this->getStore()->getId() != \Magento\Store\Model\Store::DEFAULT_STORE_ID ?
                        $this->getStore()->getWebsite()->getId() : null,
                    'store'   => $this->getStore()->getId() != \Magento\Store\Model\Store::DEFAULT_STORE_ID ?
                        $this->getStore()->getId() : null
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

    protected function canDisplayCurrencyConversionMessage($marketplaceCurrency)
    {
        if ($this->getStore() === null) {
            return false;
        }

        if ($this->modelFactory->getObject('Currency')->isBase($marketplaceCurrency, $this->getStore())) {
            return false;
        }

        $template = $this->getTemplateModel();
        $template->addData($this->getTemplateData());

        if (!$template->usesConvertiblePrices($marketplaceCurrency)) {
            return false;
        }

        return true;
    }

    //########################################

    protected function getTemplateModel()
    {
        $model = null;

        switch ($this->getComponentMode()) {
            case \Ess\M2ePro\Helper\Component\Ebay::NICK:
                $model = $this->activeRecordFactory->getObject('Ebay_Template_SellingFormat');
                break;
            case \Ess\M2ePro\Helper\Component\Amazon::NICK:
                $model = $this->activeRecordFactory->getObject('Amazon_Template_SellingFormat');
                break;
            case \Ess\M2ePro\Helper\Component\Walmart::NICK:
                $model = $this->activeRecordFactory->getObject('Walmart_Template_SellingFormat');
                break;
        }

        if ($model === null) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Policy model is unknown.');
        }

        return $model;
    }

    //########################################
}
