<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Any usage is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Template\ProductTaxCode\Edit;

use Ess\M2ePro\Model\Amazon\Template\ProductTaxCode;

class Form extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm
{
    /** @var \Ess\M2ePro\Helper\Magento\Attribute */
    protected $magentoAttributeHelper;

    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;

    /** @var \Ess\M2ePro\Helper\Data\GlobalData */
    private $globalDataHelper;

    /** @var \Ess\M2ePro\Helper\Module\Support */
    private $supportHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Magento\Attribute $magentoAttributeHelper,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Ess\M2ePro\Helper\Data $dataHelper,
        \Ess\M2ePro\Helper\Data\GlobalData $globalDataHelper,
        \Ess\M2ePro\Helper\Module\Support $supportHelper,
        array $data = []
    ) {
        $this->magentoAttributeHelper = $magentoAttributeHelper;
        $this->dataHelper = $dataHelper;
        $this->globalDataHelper = $globalDataHelper;
        $this->supportHelper = $supportHelper;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    protected function _prepareForm()
    {
        /** @var \Ess\M2ePro\Model\Amazon\Template\ProductTaxCode $model */
        $model = $this->globalDataHelper->getValue('tmp_template');

        $formData = [];
        if ($model) {
            $formData = $model->toArray();
        }

        $default = $this->modelFactory->getObject('Amazon_Template_ProductTaxCode_Builder')->getDefaultData();

        $formData = array_merge($default, $formData);

        $attributes = $this->magentoAttributeHelper->getAll();
        $attributesByInputTypes = [
            'text_select' => $this->magentoAttributeHelper->filterByInputTypes($attributes, ['text', 'select'])
        ];

        $form = $this->_formFactory->create([
            'data' => [
                'id'      => 'edit_form',
                'method'  => 'post',
                'action'  => $this->getUrl('*/*/save'),
                'enctype' => 'multipart/form-data',
                'class' => 'admin__scope-old'
            ]
        ]);

        $fieldset = $form->addFieldset(
            'magento_block_amazon_template_product_tax_code_general',
            [
                'legend' => $this->__('General'),
                'collapsable' => false
            ]
        );

        $fieldset->addField(
            'title',
            'text',
            [
                'name' => 'title',
                'label' => $this->__('Title'),
                'value' => $formData['title'],
                'class' => 'M2ePro-tpl-title',
                'tooltip' => $this->__('Short meaningful Policy Title for your internal use.'),
                'required' => true,
            ]
        );

        $fieldset = $form->addFieldset(
            'magento_block_amazon_template_product_tax_code_channel',
            [
                'legend' => $this->__('Product Tax Code'),
                'collapsable' => false
            ]
        );

        $fieldset->addField(
            'product_tax_code_attribute',
            'hidden',
            [
                'name' => 'product_tax_code_attribute',
                'value' => $formData['product_tax_code_attribute'],
            ]
        );

        $preparedAttributes = [];

        foreach ($attributesByInputTypes['text_select'] as $attribute) {
            $attrs = ['attribute_code' => $attribute['code']];
            if ($formData['product_tax_code_mode'] == ProductTaxCode::PRODUCT_TAX_CODE_MODE_ATTRIBUTE
                && $formData['product_tax_code_attribute'] == $attribute['code']
            ) {
                $attrs['selected'] = 'selected';
            }
            $preparedAttributes[] = [
                'attrs' => $attrs,
                'value' => ProductTaxCode::PRODUCT_TAX_CODE_MODE_ATTRIBUTE,
                'label' => $attribute['label'],
            ];
        }

        $customValueOption = [
            'html_id' => 'product_tax_code_mode_cv',
            'value' => ProductTaxCode::PRODUCT_TAX_CODE_MODE_VALUE,
            'label' => $this->__('Custom Value')
        ];

        if ($formData['product_tax_code_mode'] == ProductTaxCode::PRODUCT_TAX_CODE_MODE_VALUE) {
            $customValueOption['attrs']['selected'] = 'selected';
        }

        $fieldset->addField(
            'product_tax_code_mode',
            self::SELECT,
            [
                'label' => $this->__('Tax Code'),
                'name' => 'product_tax_code_mode',
                'values' => [
                    [
                        'label' => '', 'value' => '',
                        'attrs' => ['style' => 'display: none']
                    ],
                    $customValueOption,
                    [
                        'label' => $this->__('Magento Attributes'),
                        'value' => $preparedAttributes,
                        'attrs' => [
                            'new_option_value' => ProductTaxCode::PRODUCT_TAX_CODE_MODE_ATTRIBUTE
                        ]
                    ]
                ],
                'create_magento_attribute' => true,
                'required' => true,
                'tooltip' => $this->__(
                    'Apply Amazon Product Tax Codes to display VAT-exclusive prices to B2B customers. Find more info in
                    <a href="%url%" target="_blank">this article</a>.',
                    $this->supportHelper->getDocumentationArticleUrl('x/-A03B')
                )
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text,select');

        $fieldset->addField(
            'product_tax_code_value',
            'text',
            [
                'container_id' => 'product_tax_code_custom_value_tr',
                'label' => $this->__('Tax Code Value'),
                'name' => 'product_tax_code_value',
                'value' => $formData['product_tax_code_value'],
                'required' => true,
            ]
        );

        $this->jsPhp->addConstants(
            $this->dataHelper->getClassConstants(\Ess\M2ePro\Helper\Component\Amazon::class)
        );
        $this->jsPhp->addConstants(
            $this->dataHelper->getClassConstants(\Ess\M2ePro\Model\Amazon\Template\ProductTaxCode::class)
        );

        $this->jsUrl->addUrls([
            'formSubmit'    => $this->getUrl(
                '*/amazon_template_productTaxCode/save',
                ['id' => $this->getRequest()->getParam('id')]
            ),
            'formSubmitNew' => $this->getUrl('*/amazon_template_productTaxCode/save'),
            'deleteAction'  => $this->getUrl(
                '*/amazon_template_productTaxCode/delete',
                ['id' => $this->getRequest()->getParam('id')]
            )
        ]);

        $this->jsTranslator->addTranslations([
            'Add Product Tax Code Policy' => $this->__('Add Product Tax Code Policy'),
            'The specified Title is already used for other Policy. Policy Title must be unique.' =>
                $this->__('The specified Title is already used for other Policy. Policy Title must be unique.'),
        ]);

        $title = $this->dataHelper->escapeJs($this->dataHelper->escapeHtml($formData['title']));

        $this->js->add(<<<JS
    require([
        'M2ePro/Amazon/Template/ProductTaxCode',
    ], function(){

        M2ePro.formData.id = '{$this->getRequest()->getParam('id')}';
        M2ePro.formData.title = '{$title}';

        window.AmazonTemplateProductTaxCodeObj = new AmazonTemplateProductTaxCode();
        AmazonTemplateProductTaxCodeObj.initObservers();
    });
JS
        );

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }
}
