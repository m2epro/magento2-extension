<?php

declare(strict_types=1);

namespace Ess\M2ePro\Block\Adminhtml\Ebay\PromotedListing\Campaign;

class CreateForm extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm
{
    private \Ess\M2ePro\Model\Ebay\Account $ebayAccount;
    private \Ess\M2ePro\Model\Ebay\Marketplace $ebayMarketplace;

    public function __construct(
        \Ess\M2ePro\Model\Ebay\Account $ebayAccount,
        \Ess\M2ePro\Model\Ebay\Marketplace $ebayMarketplace,
        \Magento\Framework\Data\Form\FormKey $formKey,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        array $data = []
    ) {
        parent::__construct($context, $registry, $formFactory, $data);
        $this->ebayAccount = $ebayAccount;
        $this->ebayMarketplace = $ebayMarketplace;
        $this->formKey = $formKey;
    }

    protected function _prepareForm()
    {
        $form = $this->_formFactory->create([
            'data' => [
                'id' => 'new_campaign_form',
            ],
        ]);

        $fieldset = $form->addFieldset('main_fieldset', []);

        $fieldset->addField(
            'form_key',
            'hidden',
            [
                'name' => 'form_key',
                'value' => $this->formKey->getFormKey(),
            ]
        );

        $fieldset->addField(
            'account_id',
            'hidden',
            [
                'name' => 'account_id',
                'value' => $this->ebayAccount->getId(),
            ]
        );

        $fieldset->addField(
            'marketplace_id',
            'hidden',
            [
                'name' => 'marketplace_id',
                'value' => $this->ebayMarketplace->getId(),
            ]
        );

        $fieldset->addField(
            'name',
            'text',
            [
                'name' => 'name',
                'label' => __('Campaign Name'),
                'required' => true,
            ]
        );

        $fieldset->addField(
            'start_date',
            'date',
            [
                'name' => 'start_date',
                'label' => __('Start Time'),
                'required' => true,
                'date_format' => $this->_localeDate->getDateFormatWithLongYear(),
                'time_format' => $this->_localeDate->getTimeFormat(\IntlDateFormatter::SHORT),
            ]
        );

        $fieldset->addField(
            'end_date',
            'date',
            [
                'name' => 'end_date',
                'label' => __('End Time'),
                'required' => false,
                'date_format' => $this->_localeDate->getDateFormatWithLongYear(),
                'time_format' => $this->_localeDate->getTimeFormat(\IntlDateFormatter::SHORT),
            ]
        );

        $fieldset->addField(
            'rate',
            'text',
            [
                'name' => 'rate',
                'label' => __('Promote Listings at rate'),
                'required' => true,
                'tooltip' => __("This value determines the percentage of the final sale price that you'll pay " .
                    "as a fee when an item sells through the promoted listing"),
            ]
        );

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    protected function _toHtml()
    {
        return $this->getStyleBlock()
            . $this->getMessagesContainer()
            . parent::_toHtml();
    }

    private function getStyleBlock(): string
    {
        $formId = $this->getForm()->getId();
        $css = <<<CSS
#$formId { margin-top: 3rem; }
#$formId .m2epro-fieldset input[type="text"].input-text { width: 80% }
#$formId input._has-datepicker { width: 80% !important; max-width: initial !important; }
#$formId .admin__fieldset > .admin__field > .admin__field-label { width: calc( (100%) * 0.25 - 30px ) }
#$formId .admin__fieldset > .admin__field > .admin__field-control { width: calc( (100%) * 0.75  - 30px ) }

CSS;

        return '<style>' . $css . '</style>';
    }

    private function getMessagesContainer(): string
    {
        return '<div id="campaign_form_messages"></div>';
    }
}
