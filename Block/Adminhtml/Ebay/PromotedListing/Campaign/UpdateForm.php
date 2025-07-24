<?php

declare(strict_types=1);

namespace Ess\M2ePro\Block\Adminhtml\Ebay\PromotedListing\Campaign;

class UpdateForm extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm
{
    private \Ess\M2ePro\Model\Ebay\PromotedListing\Campaign $campaign;

    public function __construct(
        \Ess\M2ePro\Model\Ebay\PromotedListing\Campaign $campaign,
        \Magento\Framework\Data\Form\FormKey $formKey,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        array $data = []
    ) {
        parent::__construct($context, $registry, $formFactory, $data);
        $this->campaign = $campaign;
        $this->formKey = $formKey;
    }

    protected function _prepareForm()
    {
        $form = $this->_formFactory->create([
            'data' => [
                'id' => 'update_campaign_form',
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
            'id',
            'hidden',
            [
                'name' => 'id',
                'value' => $this->campaign->getId(),
            ]
        );

        $fieldset->addField(
            'name',
            'text',
            [
                'name' => 'name',
                'label' => __('Campaign Name'),
                'value' => $this->campaign->getName(),
                'required' => true,
            ]
        );

        $fieldset->addField(
            'start_date',
            'date',
            [
                'name' => 'start_date',
                'class' => 'M2ePro-validate-date M2ePro-start-date-greater-than-now',
                'label' => __('Start Time'),
                'required' => true,
                'value' => \Ess\M2ePro\Helper\Date::createWithLocalTimeZone($this->campaign->getStartDate()),
                'date_format' => $this->_localeDate->getDateFormatWithLongYear(),
                'time_format' => $this->_localeDate->getTimeFormat(\IntlDateFormatter::SHORT),
                'min_date' => '0',
            ]
        );

        $endDateValue = $this->campaign->getEndDate();
        if ($endDateValue !== null) {
            $endDateValue = \Ess\M2ePro\Helper\Date::createWithLocalTimeZone($endDateValue);
        }
        $fieldset->addField(
            'end_date',
            'date',
            [
                'name' => 'end_date',
                'class' => 'M2ePro-validate-date M2ePro-end-date-greater-than-now M2ePro-greater-than-start-date',
                'label' => __('End Time'),
                'value' => $endDateValue,
                'required' => false,
                'date_format' => $this->_localeDate->getDateFormatWithLongYear(),
                'time_format' => $this->_localeDate->getTimeFormat(\IntlDateFormatter::SHORT),
                'min_date' => '0',
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

#$formId .ui-datepicker-trigger {
    width: auto !important;
    margin-left: -2.6rem !important;
    border: none;
    background: none;
    padding: 0;
}

#$formId .ui-datepicker-trigger:hover {
    background: none !important;
}

#$formId .ui-datepicker-trigger:after {
    /*width: auto !important;*/
    /*margin-left: -2.6rem !important;*/
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
    background: none;
    font-size: 2.1rem;
    line-height: 32px;
    color: #514943;
    content: '\\e627';
    font-family: 'Admin Icons';
    vertical-align: middle;
    display: inline-block;
    font-weight: normal;
    overflow: hidden;
    speak: none;
    text-align: center;
}

#$formId label.error {
    color: #eb5202
}
CSS;

        return '<style>' . $css . '</style>';
    }

    private function getMessagesContainer(): string
    {
        return '<div id="campaign_form_messages"></div>';
    }
}
