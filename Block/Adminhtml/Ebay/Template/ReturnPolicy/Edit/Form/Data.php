<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Template\ReturnPolicy\Edit\Form;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Ebay\Template\ReturnPolicy\Edit\Form\Data
 */
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
    ) {
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

        $form->addField(
            'return_policy_id',
            'hidden',
            [
                'name' => 'return_policy[id]',
                'value' => (!$this->isCustom() && isset($this->formData['id'])) ? (int)$this->formData['id'] : ''
            ]
        );

        $form->addField(
            'return_policy_title',
            'hidden',
            [
                'name' => 'return_policy[title]',
                'value' => $this->getTitle()
            ]
        );

        $form->addField(
            'hidden_marketplace_id_' . $this->marketplaceData['id'],
            'hidden',
            [
                'name' => 'return_policy[marketplace_id]',
                'value' => $this->marketplaceData['id']
            ]
        );

        $form->addField(
            'is_custom_template',
            'hidden',
            [
                'name' => 'return_policy[is_custom_template]',
                'value' => $this->isCustom() ? 1 : 0
            ]
        );

        $fieldset = $form->addFieldset(
            'return_policy_domestic_returns_fieldset',
            ['legend' => __('Domestic Returns'), 'collapsable' => false]
        );

        if (!empty($this->marketplaceData['info']['returns_accepted'])) {
            $fieldset->addField(
                'return_accepted',
                self::SELECT,
                [
                    'name' => 'return_policy[accepted]',
                    'label' => __('Return Policy'),
                    'title' => __('Return Policy'),
                    'values' => $this->getMarketplaceDataToOptions('returns_accepted'),
                    'value' => $this->formData['accepted']
                ]
            );
        }

        if (!empty($this->marketplaceData['info']['refund'])) {
            $fieldset->addField(
                'return_option',
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
            $fieldset->addField(
                'return_within',
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

        if (!empty($this->marketplaceData['info']['shipping_cost_paid_by'])) {
            $fieldset->addField(
                'return_shipping_cost',
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

        $fieldset = $form->addFieldset(
            'return_policy_international_returns_fieldset',
            ['legend' => __('International Returns'), 'collapsable' => false]
        );

        if (!empty($this->marketplaceData['info']['international_returns_accepted'])) {
            $fieldset->addField(
                'return_international_accepted',
                self::SELECT,
                [
                    'name' => 'return_policy[international_accepted]',
                    'label' => __('Return Policy'),
                    'title' => __('Return Policy'),
                    'values' => $this->getMarketplaceDataToOptions('international_returns_accepted'),
                    'value' => $this->formData['international_accepted']
                ]
            );
        }

        if (!empty($this->marketplaceData['info']['international_refund'])) {
            $fieldset->addField(
                'return_international_option',
                self::SELECT,
                [
                    'name' => 'return_policy[international_option]',
                    'label' => __('Refund Will Be Given As'),
                    'title' => __('Refund Will Be Given As'),
                    'values' => $this->getMarketplaceDataToOptions('international_refund'),
                    'value' => $this->formData['international_option']
                ]
            );
        }

        if (!empty($this->marketplaceData['info']['international_returns_within'])) {
            $fieldset->addField(
                'return_international_within',
                self::SELECT,
                [
                    'name' => 'return_policy[international_within]',
                    'label' => __('Item Must Be Returned Within'),
                    'title' => __('Item Must Be Returned Within'),
                    'values' => $this->getMarketplaceDataToOptions('international_returns_within'),
                    'value' => $this->formData['international_within']
                ]
            );
        }

        if (!empty($this->marketplaceData['info']['international_shipping_cost_paid_by'])) {
            $fieldset->addField(
                'return_international_shipping_cost',
                self::SELECT,
                [
                    'name' => 'return_policy[international_shipping_cost]',
                    'label' => __('Return Shipping Will Be Paid By'),
                    'title' => __('Return Shipping Will Be Paid By'),
                    'values' => $this->getMarketplaceDataToOptions('international_shipping_cost_paid_by'),
                    'value' => $this->formData['international_shipping_cost']
                ]
            );
        }

        if ($this->canShowGeneralBlock()) {
            $fieldset = $form->addFieldset(
                'return_policy_additional_fieldset',
                ['legend' => __('Additional'), 'collapsable' => false]
            );

            $fieldset->addField(
                'return_description',
                'textarea',
                [
                    'name' => 'return_policy[description]',
                    'label' => __('Refund Description'),
                    'title' => __('Refund Description'),
                    'value' => $this->formData['description'],
                    'class' => 'input-text'
                ]
            );
        }

        $this->setForm($form);
        return $this;
    }

    //########################################

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

        if ($template === null) {
            return '';
        }

        return $template->getTitle();
    }

    public function getFormData()
    {
        $template = $this->getHelper('Data\GlobalData')->getValue('ebay_template_return_policy');

        $default = $this->getDefault();
        if ($template === null || $template->getId() === null) {
            return $default;
        }

        return array_merge($default, $template->getData());
    }

    public function getDefault()
    {
        return $this->modelFactory->getObject('Ebay_Template_ReturnPolicy_Builder')->getDefaultData();
    }

    public function getMarketplaceData()
    {
        $marketplace = $this->getHelper('Data\GlobalData')->getValue('ebay_marketplace');

        if (!$marketplace instanceof \Ess\M2ePro\Model\Marketplace) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Marketplace is required for editing Return Policy.');
        }

        $data = [
            'id' => $marketplace->getId(),
            'info' => $marketplace->getChildObject()->getReturnPolicyInfo()
        ];

        foreach ($this->getDictionaryInfo('returns_within', $marketplace) as $key => $item) {
            $data['info']['returns_within'][$key]['title'] = $this->__($item['title']);
        }

        foreach ($this->getDictionaryInfo('returns_accepted', $marketplace) as $key => $item) {
            $data['info']['returns_accepted'][$key]['title'] = $this->__($item['title']);
        }

        foreach ($this->getDictionaryInfo('refund', $marketplace) as $key => $item) {
            $data['info']['refund'][$key]['title'] = $this->__($item['title']);
        }

        foreach ($this->getDictionaryInfo('shipping_cost_paid_by', $marketplace) as $key => $item) {
            $data['info']['shipping_cost_paid_by'][$key]['title'] = $this->__($item['title']);
        }

        //----------------------------------------

        foreach ($this->getInternationalDictionaryInfo('returns_within', $marketplace) as $key => $item) {
            $data['info']['international_returns_within'][$key]['ebay_id'] = $item['ebay_id'];
            $data['info']['international_returns_within'][$key]['title'] = $this->__($item['title']);
        }

        foreach ($this->getInternationalDictionaryInfo('returns_accepted', $marketplace) as $key => $item) {
            $data['info']['international_returns_accepted'][$key]['ebay_id'] = $item['ebay_id'];
            $data['info']['international_returns_accepted'][$key]['title'] = $this->__($item['title']);
        }

        foreach ($this->getInternationalDictionaryInfo('refund', $marketplace) as $key => $item) {
            $data['info']['international_refund'][$key]['ebay_id'] = $item['ebay_id'];
            $data['info']['international_refund'][$key]['title'] = $this->__($item['title']);
        }

        foreach ($this->getInternationalDictionaryInfo('shipping_cost_paid_by', $marketplace) as $key => $item) {
            $data['info']['international_shipping_cost_paid_by'][$key]['ebay_id'] = $item['ebay_id'];
            $data['info']['international_shipping_cost_paid_by'][$key]['title'] = $this->__($item['title']);
        }

        return $data;
    }

    //########################################

    public function canShowGeneralBlock()
    {
        $marketplace = $this->getHelper('Data\GlobalData')->getValue('ebay_marketplace');

        if (!$marketplace instanceof \Ess\M2ePro\Model\Marketplace) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Marketplace is required for editing Return Policy.');
        }

        return $marketplace->getChildObject()->isReturnDescriptionEnabled();
    }

    //########################################

    protected function getDictionaryInfo($key, \Ess\M2ePro\Model\Marketplace $marketplace)
    {
        $returnPolicyInfo = $marketplace->getChildObject()->getReturnPolicyInfo();
        return !empty($returnPolicyInfo[$key]) ? $returnPolicyInfo[$key] : [];
    }

    protected function getInternationalDictionaryInfo($key, \Ess\M2ePro\Model\Marketplace $marketplace)
    {
        $returnPolicyInfo = $marketplace->getChildObject()->getReturnPolicyInfo();

        if (!empty($returnPolicyInfo['international_' . $key])) {
            return $returnPolicyInfo['international_' . $key];
        }

        return $this->getDictionaryInfo($key, $marketplace);
    }

    //########################################

    protected function _toHtml()
    {
        $this->jsPhp->addConstants(
            $this->getHelper('Data')->getClassConstants(\Ess\M2ePro\Model\Ebay\Template\ReturnPolicy::class)
        );

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
}
