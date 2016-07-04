<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Template\SellingFormat;

class Messages extends \Ess\M2ePro\Block\Adminhtml\Template\SellingFormat\Messages
{
    //########################################

    public function getCurrencyConversionMessage($marketplaceCurrency = null)
    {
        $messageText = parent::getCurrencyConversionMessage($marketplaceCurrency);

        if (is_null($messageText)) {
            return NULL;
        }

        //todo link
        //Magento 2 hasn't got yet any user documentation
        //$docUrl='http://www.magentocommerce.com/wiki/modules_reference/English/Mage_Adminhtml/system_currency/index';

        // M2ePro_TRANSLATIONS
        // More about Currency rate set-up can be found in the <a href="%url%" target="_blank">Magento documentation</a>
        //$helpText = 'More about Currency rate set-up can be found in the ';
        //$helpText .= '<a href="%url%" target="_blank">Magento documentation</a>';
        //$helpText = $this->__($helpText, $docUrl);

        return $messageText;
    }

    //########################################
}