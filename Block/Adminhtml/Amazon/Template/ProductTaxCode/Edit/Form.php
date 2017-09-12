<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  2011-2017 ESS-UA [M2E Pro]
 * @license    Any usage is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Template\ProductTaxCode\Edit;

use Ess\M2ePro\Model\Amazon\Template\ProductTaxCode;

class Form extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm
{
    //########################################

    protected function _prepareForm()
    {
        /** @var \Ess\M2ePro\Model\Amazon\Template\ShippingTemplate $model */
        $model = $this->getHelper('Data\GlobalData')->getValue('tmp_template');

        $formData = array();
        if ($model) {
            $formData = $model->toArray();
        }

        $default = array(
            'title'         => '',

            'product_tax_code_mode' => '',
            'product_tax_code_value' => '',
            'product_tax_code_attribute' => '',
        );

        $formData = array_merge($default, $formData);

        /** @var \Ess\M2ePro\Helper\Magento\Attribute $magentoAttributeHelper */
        $magentoAttributeHelper = $this->getHelper('Magento\Attribute');

        $attributes = $magentoAttributeHelper->getGeneralFromAllAttributeSets();

        $attributesByInputTypes = array(
            'text_select' => $magentoAttributeHelper->filterByInputTypes($attributes, array('text', 'select'))
        );

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
            if (
                $formData['product_tax_code_mode'] == ProductTaxCode::PRODUCT_TAX_CODE_MODE_ATTRIBUTE
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

        $fieldset->addField('product_tax_code_mode',
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
                'tooltip' => $this->__('Specify Amazon Tax Code value or select Magento Attribute
                                        that contains appropriate Tax Code values.
                                        Only <strong>common</strong> Attributes are available for the selection.')
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
            $this->getHelper('Data')->getClassConstants('\Ess\M2ePro\Helper\Component\Amazon')
        );
        $this->jsPhp->addConstants(
            $this->getHelper('Data')->getClassConstants('\Ess\M2ePro\Model\Amazon\Template\ProductTaxCode')
        );

        $this->jsUrl->addUrls([
            'formSubmit'    => $this->getUrl(
                '*/amazon_template_productTaxCode/save', array('id' => $this->getRequest()->getParam('id'))
            ),
            'formSubmitNew' => $this->getUrl('*/amazon_template_productTaxCode/save'),
            'deleteAction'  => $this->getUrl(
                '*/amazon_template_productTaxCode/delete', array('id' => $this->getRequest()->getParam('id'))
            )
        ]);

        $this->jsTranslator->addTranslations([
            'Add Product Tax Code Policy' => $this->__('Add Product Tax Code Policy'),
            'The specified Title is already used for other Policy. Policy Title must be unique.' =>
                $this->__('The specified Title is already used for other Policy. Policy Title must be unique.'),
        ]);

        $title = $this->getHelper('Data')->escapeJs($this->getHelper('Data')->escapeHtml($formData['title']));

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

    //########################################
}