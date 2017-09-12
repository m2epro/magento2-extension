<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  2011-2017 ESS-UA [M2E Pro]
 * @license    Any usage is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Settings\Tabs;

class Main extends \Ess\M2ePro\Block\Adminhtml\Settings\Tabs\AbstractTab
{
    //########################################

    protected function _prepareForm()
    {
        $form = $this->_formFactory->create();

        $form->addField('block_notice_general', self::HELP_BLOCK,
            [
                'content' => $this->__('This section allows you to configure the general settings for the interaction
                                        between M2E Pro Module and Amazon marketplaces.<br />
                                        You can enable Amazon Business (B2B) to use Business Price and
                                        QTY Discounts for your Offers.<br /><br />
                                        <strong>Note:</strong> Amazon Business is available for the <strong>US</strong>,
                                        <strong>UK</strong>, <strong>DE</strong> marketplaces only.')
            ]
        );

        $fieldset = $form->addFieldset('amazon_main',
            [
                'legend' => $this->__('Business (B2B)'),
                'collapsable' => false,
            ]
        );

        $fieldset->addField('business_mode',
            self::SELECT,
            [
                'name'        => 'business_mode',
                'label'       => $this->__('Price, QTY Discounts'),
                'values' => [
                    0 => $this->__('Disabled'),
                    1 => $this->__('Enabled')
                ],
                'value' => $this->getHelper('Component\Amazon\Business')->isEnabled(),
                'tooltip' => $this->__(
                    'After you <strong>Enable</strong> this option, you can provide the settings for
                    <strong>Business Price</strong> and <strong >Quantity Discounts</strong>
                    within M2E Pro Selling Format Policy.<br />
                    <strong>Note:</strong> your Business Account must be approved by Amazon.'
                )
            ]
        );

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    //########################################

    protected function _beforeToHtml()
    {
        $this->jsUrl->add(
            $this->getUrl('*/amazon_settings/save'),
            \Ess\M2ePro\Block\Adminhtml\Amazon\Settings\Tabs::TAB_ID_MAIN
        );

        return parent::_beforeToHtml();
    }

    //########################################

    protected function getGlobalNotice()
    {
        return '';
    }

    //########################################
}