<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2016 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Template\ShippingTemplate\Edit;

use Ess\M2ePro\Model\Amazon\Template\ShippingTemplate;

class Form extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm
{
    protected $formData;

    //########################################

    protected function _prepareForm()
    {
        /** @var \Ess\M2ePro\Model\Amazon\Template\ShippingTemplate $model */
        $model = $this->getHelper('Data\GlobalData')->getValue('tmp_template');

        $this->formData = array();
        if ($model) {
            $this->formData = $model->toArray();
        }

        $default = array(
            'title'         => '',

            'template_name_mode' => '',
            'template_name_value' => '',
            'template_name_attribute' => '',
        );

        $this->formData = array_merge($default, $this->formData);

        $form = $this->_formFactory->create([
            'data' => [
                'id'      => 'edit_form',
                'method'  => 'post',
                'action'  => $this->getUrl('*/*/save'),
                'enctype' => 'multipart/form-data',
                'class' => 'admin__scope-old'
            ]
        ]);

        /** @var \Ess\M2ePro\Helper\Magento\Attribute $magentoAttributeHelper */
        $magentoAttributeHelper = $this->getHelper('Magento\Attribute');
        $attributes = $magentoAttributeHelper->getGeneralFromAllAttributeSets();
        $attributesByInputTypes = array(
            'text_select' => $magentoAttributeHelper->filterByInputTypes($attributes, ['text', 'select'])
        );

        $fieldset = $form->addFieldset(
            'magento_block_amazon_template_shipping_template_general',
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
                'value' => $this->formData['title'],
                'class' => 'M2ePro-shipping-tpl-title',
                'tooltip' => $this->__('Short meaningful Policy Title for your internal use.'),
                'required' => true,
            ]
        );

        $fieldset = $form->addFieldset(
            'magento_block_amazon_template_shipping_template_channel',
            [
                'legend' => $this->__('Channel'),
                'collapsable' => false
            ]
        );

        $preparedAttributes = [];
        foreach ($attributesByInputTypes['text_select'] as $attribute) {
            $attrs = ['attribute_code' => $attribute['code']];
            if (
                $this->formData['template_name_mode'] == ShippingTemplate::TEMPLATE_NAME_ATTRIBUTE
                && $this->formData['template_name_attribute'] == $attribute['code']
            ) {
                $attrs['selected'] = 'selected';
            }
            $preparedAttributes[] = [
                'attrs' => $attrs,
                'value' => ShippingTemplate::TEMPLATE_NAME_ATTRIBUTE,
                'label' => $attribute['label'],
            ];
        }

        $fieldset->addField('template_name_mode',
            self::SELECT,
            [
                'container_id' => 'template_name_mode_tr',
                'label'        => $this->__('Template Name'),
                'class'        => 'select-main',
                'name'         => 'template_name_mode',
                'values' => [
                    ShippingTemplate::TEMPLATE_NAME_VALUE => $this->__('Custom Value'),
                    [
                        'label' => $this->__('Magento Attributes'),
                        'value' => $preparedAttributes,
                        'attrs' => [
                            'is_magento_attribute' => true
                        ]
                    ]
                ],
                'create_magento_attribute' => true,
                'tooltip' => $this->__('Template Name which you would like to be used.')
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text,select');

        $fieldset->addField('template_name_attribute',
            'hidden',
            [
                'name' => 'template_name_attribute',
            ]
        );

        $fieldset->addField('template_name_value',
            'text',
            [
                'container_id' => 'template_name_custom_value_tr',
                'label'        => $this->__('Template Name Value'),
                'name'         => 'template_name_value',
                'value'        => $this->formData['template_name_value'],
                'required'     => true
            ]
        );

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    protected function _prepareLayout()
    {
        $this->appendHelpBlock([
            'content' => $this->__('
        The Shipping Template Policy allows to provide Shipping Settings for the Items being listed to Amazon.
        So you should provide a Channel Template Name which you would like to be used.<br />
        More detailed information about ability to work with this Page
        you can find <a target="_blank" href="%url%">here</a>',
                $this->getHelper('Module\Support')->getDocumentationArticleUrl('x/wwA9AQ')
            )
        ]);

        return parent::_prepareLayout();
    }

    protected function _beforeToHtml()
    {
        $this->jsPhp->addConstants(
            $this->getHelper('Data')->getClassConstants('\Ess\M2ePro\Helper\Component\Amazon')
        );

        $this->jsPhp->addConstants(
            $this->getHelper('Data')->getClassConstants('\Ess\M2ePro\Model\Amazon\Template\ShippingTemplate')
        );

        $this->jsUrl->addUrls([
            'formSubmit' => $this->getUrl('*/amazon_template_shippingTemplate/save', [
                '_current' => $this->getRequest()->getParam('id'),
                'close_on_save' => $this->getRequest()->getParam('close_on_save')
            ]),
            'formSubmitNew' => $this->getUrl('*/amazon_template_shippingTemplate/save'),
            'deleteAction'  => $this->getUrl('*/amazon_template_shippingTemplate/delete', [
                'id' => $this->getRequest()->getParam('id'),
                'close_on_save' => $this->getRequest()->getParam('close_on_save')
            ])
        ]);

        $this->jsTranslator->addTranslations([
            'Add Shipping Template Policy' => $this->__('Add Shipping Template Policy'),
            'Add Shipping Override Policy' => $this->__('Add Shipping Override Policy'),
            'The specified Title is already used for other Policy. Policy Title must be unique.' =>
                $this->__('The specified Title is already used for other Policy. Policy Title must be unique.'),
        ]);

        $title = $this->getHelper('Data')->escapeJs($this->getHelper('Data')->escapeHtml($this->formData['title']));

        $this->js->add(<<<JS
M2ePro.formData.id = '{$this->getRequest()->getParam('id')}';
M2ePro.formData.title = '{$title}';

require(['M2ePro/Amazon/Template/ShippingTemplate'], function() {
    window.AmazonTemplateShippingTemplateObj = new AmazonTemplateShippingTemplate();
    window.AmazonTemplateShippingTemplateObj.initObservers();
});
JS
        );

        return parent::_beforeToHtml();
    }

    //########################################
}