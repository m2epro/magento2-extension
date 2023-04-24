<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Template\Shipping\Edit;

class Form extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm
{
    protected $formData;
    private $accountData = [];

    /** @var \Ess\M2ePro\Helper\Magento\Attribute */
    protected $magentoAttributeHelper;
    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;
    /** @var \Ess\M2ePro\Helper\Data\GlobalData */
    private $globalDataHelper;
    /** @var \Ess\M2ePro\Helper\Component\Amazon */
    private $amazonHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Component\Amazon $amazonHelper,
        \Ess\M2ePro\Helper\Magento\Attribute $magentoAttributeHelper,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Ess\M2ePro\Helper\Data $dataHelper,
        \Ess\M2ePro\Helper\Data\GlobalData $globalDataHelper,
        array $data = []
    ) {
        $this->magentoAttributeHelper = $magentoAttributeHelper;
        $this->dataHelper = $dataHelper;
        $this->globalDataHelper = $globalDataHelper;
        $this->amazonHelper = $amazonHelper;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * @return void
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function _construct()
    {
        parent::_construct();
        $this->setId('amazonTemplateShippingEditForm');

        $accounts = $this->amazonHelper->getAccounts();
        $accounts = $accounts->toArray();
        $this->accountData = $accounts['items'];
    }

    /**
     * @return \Ess\M2ePro\Block\Adminhtml\Amazon\Template\Shipping\Edit\Form
     * @throws \Magento\Framework\Exception\LocalizedException|\Ess\M2ePro\Model\Exception\Logic
     */
    protected function _prepareForm(): self
    {
        $button = $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Magento\Button::class)->addData(
            [
                'id' => 'refresh_templates',
                'label' => $this->__('Refresh Templates'),
                'onclick' => 'AmazonTemplateShippingObj.refreshTemplateShipping()',
                'class' => 'action-primary',
                'style' => 'margin-left: 70px;',
            ]
        );

        $form = $this->_formFactory->create(
            [
                'data' => [
                    'id' => 'edit_form',
                    'method' => 'post',
                    'action' => $this->getUrl('*/*/save'),
                    'enctype' => 'multipart/form-data',
                    'class' => 'admin__scope-old',
                ],
            ]
        );

        $formData = $this->getFormData();

        $templates = [];

        if ($formData['account_id']) {
            $templates = $this->amazonHelper->getTemplateShippingDictionary($formData['account_id']);
            $templates = $templates['items'];
        }

        $fieldset = $form->addFieldset(
            'magento_block_amazon_template_shipping_general',
            [
                'legend' => $this->__('General'),
                'collapsable' => false,
            ]
        );

        $fieldset->addField(
            'title',
            'text',
            [
                'name' => 'title',
                'label' => $this->__('Title'),
                'value' => $formData['title'],
                'class' => 'M2ePro-shipping-tpl-title',
                'tooltip' => $this->__('Short meaningful Policy Title for your internal use.'),
                'required' => true,
            ]
        );

        $fieldset->addField(
            'account_id',
            self::SELECT,
            [
                'name' => 'account_id',
                'label' => $this->__('Account'),
                'title' => $this->__('Account'),
                'values' => $this->getAccountDataOptions(),
                'value' => $this->formData['account_id'],
                'required' => true,
                'disabled' => !empty($this->formData['account_id']),
            ]
        );

        $fieldset = $form->addFieldset(
            'magento_block_amazon_template_shipping_channel',
            [
                'legend' => $this->__('Channel'),
                'collapsable' => false,
            ]
        );

        $fieldset->addField(
            'template_id',
            self::SELECT,
            [
                'name' => 'template_id',
                'label' => $this->__('Template'),
                'value' => $formData['template_id'],
                'values' => $this->getTemplateDataOptions($templates),
                'required' => true,
                'after_element_html' => $button->toHtml(),
            ]
        );

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    protected function _prepareLayout()
    {
        $this->jsUrl->addUrls(
            [
                'formSubmit' => $this->getUrl(
                    '*/amazon_template_shipping/save',
                    [
                        '_current' => $this->getRequest()->getParam('id'),
                        'close_on_save' => $this->getRequest()->getParam('close_on_save'),
                    ]
                ),
                'formSubmitNew' => $this->getUrl('*/amazon_template_shipping/save'),
                'deleteAction' => $this->getUrl(
                    '*/amazon_template_shipping/delete',
                    [
                        'id' => $this->getRequest()->getParam('id'),
                        'close_on_save' => $this->getRequest()->getParam('close_on_save'),
                    ]
                ),
                'amazon_template_shipping/refresh' => $this->getUrl(
                    '*/amazon_template_shipping/refresh/'
                ),
                'amazon_template_shipping/getTemplates' => $this->getUrl(
                    '*/amazon_template_shipping/getTemplates/'
                ),
            ]
        );

        $this->jsTranslator->addTranslations(
            [
                'Add Shipping Policy' => $this->__(
                    'Add Shipping Policy'
                ),

                'The specified Title is already used for other Policy. Policy Title must be unique.' =>
                    $this->__('The specified Title is already used for other Policy. Policy Title must be unique.'),
            ]
        );

        $formData = $this->getFormData();

        $title = $this->dataHelper->escapeJs($this->dataHelper->escapeHtml($formData['title']));

        $this->js->add(
            <<<JS
M2ePro.formData.id = '{$this->getRequest()->getParam('id')}';
M2ePro.formData.title = '{$title}';

require(['M2ePro/Amazon/Template/Shipping'], function() {
    window.AmazonTemplateShippingObj = new AmazonTemplateShipping();
    window.AmazonTemplateShippingObj.initObservers();
});
JS
        );

        return parent::_prepareLayout();
    }

    protected function getFormData()
    {
        if ($this->formData === null) {
            /** @var \Ess\M2ePro\Model\Amazon\Template\Shipping $model */
            $model = $this->globalDataHelper->getValue('tmp_template');

            $this->formData = [];
            if ($model) {
                $this->formData = $model->toArray();
            }

            $default = $this->modelFactory->getObject('Amazon_Template_Shipping_Builder')->getDefaultData();

            $this->formData = array_merge($default, $this->formData);
        }

        return $this->formData;
    }

    /**
     * @return array[]
     */
    public function getAccountDataOptions(): array
    {
        $optionsResult = [
            ['value' => '', 'label' => '', 'attrs' => ['style' => 'display: none;']],
        ];
        foreach ($this->accountData as $account) {
            $optionsResult[] = [
                'value' => $account['id'],
                'label' => $this->__($account['title']),
            ];
        }

        return $optionsResult;
    }

    /**
     * @param array $templates
     *
     * @return array[]
     */
    public function getTemplateDataOptions(array $templates): array
    {
        $optionsResult = [
            ['value' => '', 'label' => '', 'attrs' => ['style' => 'display: none;']],
        ];
        foreach ($templates as $template) {
            $optionsResult[] = [
                'value' => $template['template_id'],
                'label' => $this->__($template['title']),
            ];
        }

        return $optionsResult;
    }
}
