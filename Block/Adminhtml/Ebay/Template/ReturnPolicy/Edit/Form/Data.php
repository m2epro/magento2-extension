<?php

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Template\ReturnPolicy\Edit\Form;

class Data extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm
{
    protected $returnPolicyTemplate;

    public $formData = [];
    public $marketplaceData = [];

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\Ebay\Template\ReturnPolicy $returnPolicyTemplate,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        array $data = []
    )
    {
        $this->returnPolicyTemplate = $returnPolicyTemplate;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('ebayTemplateReturnEditFormData');
        // ---------------------------------------

        $this->formData = $this->getFormData();
        $this->marketplaceData = $this->getMarketplaceData();
    }

    protected function _prepareForm()
    {
        $form = $this->_formFactory->create();

        $form->addField('return_policy_id',
            'hidden', [
                'name' => 'return_policy[id]',
                'value' => (!$this->isCustom() && isset($this->formData['id'])) ? (int)$this->formData['id'] : ''
            ]
        );

        $form->addField('return_policy_title', 'hidden',
            [
                'name' => 'return_policy[title]',
                'value' => $this->getTitle()
            ]
        );

        $form->addField('hidden_marketplace_id_'.$this->marketplaceData['id'], 'hidden',
            [
                'name' => 'return_policy[marketplace_id]',
                'value' => $this->marketplaceData['id']
            ]
        );

        $form->addField('is_custom_template',
            'hidden', [
                'name' => 'return_policy[is_custom_template]',
                'value' => $this->isCustom() ? 1 : 0
            ]
        );

        $fieldset = $form->addFieldset('return_policy_fieldset',
            ['legend' => __('Return Policy'), 'collapsable' => false]
        );

        if (!empty($this->marketplaceData['info']['returns_accepted'])) {
            $fieldset->addField('return_accepted',
                self::SELECT,
                [
                    'name' => 'return_policy[accepted]',
                    'label' => __('Return Policy'),
                    'title' => __('Return Policy'),
                    'values' => $this->getMarketplaceDataToOptions('returns_accepted'),
                    'value' => $this->formData['accepted'],
                    'tooltip' => $this->__(
                        'Buyers are more comfortable shopping from Sellers who offer
                         <a href="http://sellercentre.ebay.co.uk/offer-returns-policy"
                            target="_blank">Returns Policies</a>,
                         even though most Buyers will never return an Item.
                         Items with a clear Returns Policy typically sell better than Items that don\'t have one.'
                    )
                ]
            );
        }

        if (!empty($this->marketplaceData['info']['refund'])) {
            $fieldset->addField('return_option',
                self::SELECT,
                [
                    'name' => 'return_policy[option]',
                    'label' => __('Refund Will Be Given As'),
                    'title' => __('Refund Will Be Given As'),
                    'values' => $this->getMarketplaceDataToOptions('refund'),
                    'value' => $this->formData['option'],
                ]
            );
        }

        if (!empty($this->marketplaceData['info']['returns_within'])) {
            $fieldset->addField('return_within',
                self::SELECT,
                [
                    'name' => 'return_policy[within]',
                    'label' => __('Item Must Be Returned Within'),
                    'title' => __('Item Must Be Returned Within'),
                    'values' => $this->getMarketplaceDataToOptions('returns_within'),
                    'value' => $this->formData['within'],
                ]
            );
        }

        if ($this->canShowHolidayReturnOption()) {
            $fieldset->addField('return_holiday_mode',
                self::SELECT,
                [
                    'name' => 'return_policy[holiday_mode]',
                    'label' => __('Extended Holiday Returns'),
                    'title' => __('Extended Holiday Returns'),
                    'values' => [
                        ['value' => 0, 'label' => __('No')],
                        ['value' => 1, 'label' => __('Yes')]
                    ],
                    'value' => isset($this->formData['holiday_mode']) ? $this->formData['holiday_mode'] : 0,
                    'class' => 'return return-accepted',
                    'tooltip' => $this->__(
                        'You can specify Extended Holiday Returns (as well as their regular non-holiday returns period)
                         for chosen Listings at any time during the year. The Extended Holiday Returns offer
                         is not visible in the Listings until the current year\'s holiday returns period start date,
                         at which point it overrides the non-holiday Returns Policy.
                         <br/><br/>Buyers will see and be subject to the Extended Holiday Returns offer
                         in Listings purchased through the purchase cutoff date, and will be able to return those
                         purchases through the end date.
                         <br/><br/>After the purchase cutoff date, the Extended Holiday Returns offer automatically
                         disappears from the Listings, and non-holiday returns period reappears.
                         Purchases made from that point on are subject to the non-holiday returns period,
                         while purchases made before the cutoff date still have until the end date to be returned.
                         <br/><br/>For more details please read
                         <a href="http://pages.ebay.com/sellerinformation/returns-on-eBay/#faq=faq-58"
                            target="_blank">this documentation</a>.'
                    )
                ]
            );
        }

        if (!empty($this->marketplaceData['info']['shipping_cost_paid_by'])) {
            $fieldset->addField('return_shipping_cost',
                self::SELECT,
                [
                    'name' => 'return_policy[shipping_cost]',
                    'label' => __('Return Shipping Will Be Paid By'),
                    'title' => __('Return Shipping Will Be Paid By'),
                    'values' => $this->getMarketplaceDataToOptions('shipping_cost_paid_by'),
                    'value' => $this->formData['shipping_cost']
                ]
            );
        }

        if (!empty($this->marketplaceData['info']['restocking_fee_value'])) {
            $fieldset->addField('return_restocking_fee',
                self::SELECT,
                [
                    'name' => 'return_policy[restocking_fee]',
                    'label' => __('Restocking Fee Value'),
                    'title' => __('Restocking Fee Value'),
                    'values' => $this->getMarketplaceDataToOptions('restocking_fee_value'),
                    'value' => $this->formData['restocking_fee'],
                    'class' => 'return return-accepted'
                ]
            );
        }

        $fieldset->addField('return_description',
            'textarea',
            [
                'name' => 'return_policy[description]',
                'label' => __('Refund Description'),
                'title' => __('Refund Description'),
                'value' => $this->formData['description'],
                'class' => 'input-text'
            ]
        );

        $this->setForm($form);
        return $this;
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

        $template = $this->getHelper('Data\GlobalData')->getValue('ebay_template_return_policy');

        if (is_null($template)) {
            return '';
        }

        return $template->getTitle();
    }

    public function getFormData()
    {
        $template = $this->getHelper('Data\GlobalData')->getValue('ebay_template_return_policy');

        $default = $this->getDefault();
        if (is_null($template) || is_null($template->getId())) {
            return $default;
        }

        return array_merge($default, $template->getData());
    }

    public function getDefault()
    {
        return $this->returnPolicyTemplate->getDefaultSettingsAdvancedMode();
    }

    public function getMarketplaceData()
    {
        $marketplace = $this->getHelper('Data\GlobalData')->getValue('ebay_marketplace');

        if (!$marketplace instanceof \Ess\M2ePro\Model\Marketplace) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Marketplace is required for editing Return Policy.');
        }

        $data = array(
            'id' => $marketplace->getId(),
            'info' => $marketplace->getChildObject()->getReturnPolicyInfo()
        );

        $policyLocalization = $this->getData('policy_localization');

        if (!empty($policyLocalization)) {
            /** @var \Ess\M2ePro\Helper\Module\Translation $translator */

            foreach ($data['info']['returns_within'] as $key => $item) {
                $data['info']['returns_within'][$key]['title'] = $translator->__($item['title']);
            }

            foreach ($data['info']['returns_accepted'] as $key => $item) {
                $data['info']['returns_accepted'][$key]['title'] = $translator->__($item['title']);
            }

            foreach ($data['info']['shipping_cost_paid_by'] as $key => $item) {
                $data['info']['shipping_cost_paid_by'][$key]['title'] = $translator->__($item['title']);
            }
        }

        return $data;
    }

    protected function getMarketplaceDataToOptions($key)
    {
        if (empty($this->marketplaceData['info'][$key])) {
            return [];
        }

        $optionsData = [];
        $helper = $this->getHelper('Data');
        foreach ($this->marketplaceData['info'][$key] as $value) {
            $optionsData[] = [
                'value' => $value['ebay_id'],
                'label' => $helper->escapeHtml($value['title'])
            ];
        }

        return $optionsData;
    }

    //########################################

    protected function _toHtml()
    {
        $this->js->addRequireJs([
            'form' => 'M2ePro/Ebay/Template/ReturnPolicy'
        ], <<<JS

        window.ebayTemplateReturnPolicyObj = new EbayTemplateReturnPolicy();
        ebayTemplateReturnPolicyObj.initObservers();
JS
        );

        return parent::_toHtml();
    }

    //########################################

    public function canShowHolidayReturnOption()
    {
        $marketplace = $this->getHelper('Data\GlobalData')->getValue('ebay_marketplace');

        if (!$marketplace instanceof \Ess\M2ePro\Model\Marketplace) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Marketplace is required for editing Return Policy.');
        }

        return $marketplace->getChildObject()->isHolidayReturnEnabled();
    }

    //########################################
}