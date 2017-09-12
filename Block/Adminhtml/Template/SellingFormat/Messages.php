<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Template\SellingFormat;

class Messages extends \Ess\M2ePro\Block\Adminhtml\Template\Messages
{
    const TYPE_CURRENCY_CONVERSION = 'currency_conversion';

    //########################################

    public function getCurrencyConversionMessage($marketplaceCurrency = null)
    {
        if (is_null($this->getMarketplace())) {
            return NULL;
        }

        if (is_null($marketplaceCurrency)) {
            $marketplaceCurrency = $this->getMarketplace()->getChildObject()->getCurrency();
        }

        if (!$this->canDisplayCurrencyConversionMessage($marketplaceCurrency)) {
            return NULL;
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
                array(
                    'section' => 'currency',
                    'website' => $this->getStore()->getId() != \Magento\Store\Model\Store::DEFAULT_STORE_ID ?
                        $this->getStore()->getWebsite()->getId() : null,
                    'store'   => $this->getStore()->getId() != \Magento\Store\Model\Store::DEFAULT_STORE_ID ?
                        $this->getStore()->getId() : null
                )
            );

            // M2ePro_TRANSLATIONS
            // Currency "%currency_code%" is not allowed in <a href="%url%" target="_blank">Currency Setup</a> for Store View "%store_path%" of your Magento. Currency conversion will not be performed.
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

        // M2ePro_TRANSLATIONS
        // There is no rate for "%currency_from%-%currency_to%" in <a href="%url%" target="_blank">Manage Currency Rates</a> of your Magento. Currency conversion will not be performed.
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

        // M2ePro_TRANSLATIONS
        // There is a rate %value% for "%currency_from%-%currency_to%" in <a href="%url%" target="_blank">Manage Currency Rates</a> of your Magento. Currency conversion will be performed automatically.
        $message =
            $this->__(
                'There is a rate %value% for "%currency_from%-%currency_to%" in'
                . ' <a href="%url%" target="_blank">Manage Currency Rates</a> of your Magento.'
                . ' Currency conversion will be performed automatically.'
            ,
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
        $messages = array();

        // ---------------------------------------
        if (!is_null($message = $this->getCurrencyConversionMessage())) {
            $messages[self::TYPE_CURRENCY_CONVERSION] = $message;
        }
        // ---------------------------------------

        $messages = array_merge($messages, parent::getMessages());

        return $messages;
    }

    //########################################

    protected function canDisplayCurrencyConversionMessage($marketplaceCurrency)
    {
        if (is_null($this->getStore())) {
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
                $model = $this->activeRecordFactory->getObject('Ebay\Template\SellingFormat');
                break;
            case \Ess\M2ePro\Helper\Component\Amazon::NICK:
                $model = $this->activeRecordFactory->getObject('Amazon\Template\SellingFormat');
                break;
        }

        if (is_null($model)) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Policy model is unknown.');
        }

        return $model;
    }

    //########################################
}