<?php
namespace Ess\M2ePro\Block\Adminhtml\Ebay\Template\Payment\Edit\Form;

class Data extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm
{
    protected $paypalConfigFactory;

    public $formData = [];
    public $marketplaceData = [];

    //########################################

    public function __construct(
        \Magento\Paypal\Model\Config\Factory $paypalConfigFactory,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        array $data = []
    )
    {
        $this->paypalConfigFactory = $paypalConfigFactory;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('ebayTemplatePaymentEditFormData');
        // ---------------------------------------

        $this->formData = $this->getFormData();
        $this->marketplaceData = $this->getMarketplaceData();
    }

    //########################################

    protected function _prepareForm()
    {
        $form = $this->_formFactory->create();

        $form->addField('payment_id', 'hidden', [
                'name' => 'payment[id]',
                'value' => (!$this->isCustom() && isset($this->formData['id']))
                            ? (int)$this->formData['id'] : ''
            ]
        );

        $form->addField('payment_title', 'hidden',
            [
                'name' => 'payment[title]',
                'value' => $this->getTitle()
            ]
        );

        $form->addField('hidden_marketplace_id_'.$this->marketplaceData['id'], 'hidden',
            [
                'name' => 'payment[marketplace_id]',
                'value' => $this->marketplaceData['id']
            ]
        );

        $form->addField('is_custom_template', 'hidden', [
                'name' => 'payment[is_custom_template]',
                'value' => $this->isCustom() ? 1 : 0
            ]
        );

        $fieldSet = $form->addFieldset('magento_block_ebay_template_payment_form_data_paypal',
            ['legend' => $this->__('PayPal'), 'collapsable' => false]
        );

        $fieldSet->addField('pay_pal_mode', 'checkbox',
            [
                'name' => 'payment[pay_pal_mode]',
                'label' => $this->__('Accepted'),
                'value' => 1,
                'class' => 'M2ePro-validate-payment-methods admin__control-checkbox',
                'checked' => (bool)$this->formData['pay_pal_mode'],
                'after_element_html' => '<label for="pay_pal_mode"></label>'
            ]
        );

        $fieldSet->addField('pay_pal_email_address', 'text',
            [
                'name' => 'payment[pay_pal_email_address]',
                'label' => $this->__('Email'),
                'value' => $this->formData['pay_pal_email_address'],
                'class' => 'input-text M2ePro-validate-ebay-payment-email',
                'field_extra_attributes' => 'id="pay_pal_email_address_container" style="display: none;"',
                'required' => true
            ]
        );

        $fieldSet->addField('pay_pal_immediate_payment', 'checkbox',
            [
                'name' => 'payment[pay_pal_immediate_payment]',
                'label' => $this->__('Immediate Payment Required'),
                'value' => 1,
                'checked' => (bool)$this->formData['pay_pal_immediate_payment'],
                'class' => 'admin__control-checkbox',
                'field_extra_attributes' => 'id="pay_pal_immediate_payment_container" style="display: none;"',
                'after_element_html' => '<label for="pay_pal_mode"></label>'.$this->getTooltipHtml($this->__(
                    'This is only applicable to Items Listed on PayPal-enabled
                    Marketplaces in Categories that support immediate payment,
                    when a Seller has a Premier or Business PayPal Account.'
                ), true)
            ]
        );

        if (!empty($this->marketplaceData['services'])) {
            $fieldSet = $form->addFieldset('magento_block_ebay_template_payment_form_data_additional_service',
                ['legend' => $this->__('Additional Payment Methods'), 'collapsable' => false]
            );

            $fieldSet->addField('payment_methods', 'checkboxes',
                [
                    'label' => $this->__('Payment Methods'),
                    'name' => 'payment[services][]',
                    'values' => $this->getPaymentMethods(),
                    'value' => $this->formData['services']
                ]
            );
        }

        $this->setForm($form);
        return $this;
    }

    // ---------------------------------------

    public function getPaymentMethods()
    {
        $options = [];
        $helper = $this->getHelper('Data');
        foreach ($this->marketplaceData['services'] as $service) {
            if ($service['ebay_id'] == 'PayPal') {
                continue;
            }

            if ((int)$this->marketplaceData['id'] == \Ess\M2ePro\Helper\Component\Ebay::MARKETPLACE_AU
                && $service['title'] == 'CIP') {
                $label = 'Bank Deposit Express';
            } else {
                $label = $helper->escapeHtml($service['title']);
            }

            $options[] = [
                'value' => $service['ebay_id'],
                'label' => $label
            ];
        }

        return $options;
    }

    //########################################

    public function isCustom()
    {
        if (isset($this->_data['is_custom'])) {
            return (bool)$this->_data['is_custom'];
        }

        return false;
    }

    public function getTitle()
    {
        if ($this->isCustom()) {
            return isset($this->_data['custom_title']) ? $this->_data['custom_title'] : '';
        }

        $template = $this->getHelper('Data\GlobalData')->getValue('ebay_template_payment');

        if (is_null($template)) {
            return '';
        }

        return $template->getTitle();
    }

    //########################################

    public function getFormData()
    {
        $template = $this->getHelper('Data\GlobalData')->getValue('ebay_template_payment');

        $default = $this->getDefault();
        if (is_null($template)) {
            return $default;
        }

        $data = $template->getData();
        $data['services'] = $this->activeRecordFactory->getObject('Ebay\Template\Payment\Service')
            ->getCollection()
            ->addFieldToFilter('template_payment_id', $template->getId())
            ->getColumnValues('code_name');

        return array_merge($default, $data);
    }

    public function getDefault()
    {
        $default = $this->activeRecordFactory->getObject('Ebay\Template\Payment')->getDefaultSettingsAdvancedMode();

        // populate payment fields with the data from magento configuration
        // ---------------------------------------
        $store = $this->getHelper('Data\GlobalData')->getValue('ebay_store');

        $payPalConfig = $this->paypalConfigFactory->create('Magento\Paypal\Model\Config');
        $payPalConfig->setStoreId($store->getId());

        if ($businessAccount = $payPalConfig->getValue('business_account')) {
            $default['pay_pal_mode'] = 1;
            $default['pay_pal_email_address'] = $businessAccount;
        }
        // ---------------------------------------

        return $default;
    }

    public function getMarketplaceData()
    {
        $marketplace = $this->getHelper('Data\GlobalData')->getValue('ebay_marketplace');

        if (!$marketplace instanceof \Ess\M2ePro\Model\Marketplace) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Marketplace is required for editing Payment Policy.');
        }

        $data = array(
            'id' => $marketplace->getId(),
            'services' => $marketplace->getChildObject()->getPaymentInfo()
        );

        $policyLocalization = $this->getData('policy_localization');

        if (!empty($policyLocalization)) {
            $translator = $this->getHelper('Module\Translation');

            foreach ($data['services'] as $key => $item) {
                $data['services'][$key]['title'] = $translator->translate($item['title']);
            }
        }

        return $data;
    }

    //########################################

    protected function _toHtml()
    {
        $this->jsTranslator->addTranslations([
            'Payment method should be specified.' => $this->__('Payment method should be specified.'),
        ]);

        $this->js->addRequireJs([
            'form' => 'M2ePro/Ebay/Template/Payment'
        ], <<<JS

        window.EbayTemplatePaymentObj = new EbayTemplatePayment();
        EbayTemplatePaymentObj.initObservers();
JS
        );
        return parent::_toHtml();
    }

    //########################################
}