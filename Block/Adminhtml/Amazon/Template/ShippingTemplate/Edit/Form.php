<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2016 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Template\ShippingTemplate\Edit;

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
            'template_name' => '',
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

        $fieldset->addField(
            'template_name',
            'text',
            [
                'name' => 'template_name',
                'label' => $this->__('Template Name'),
                'value' => $this->formData['template_name'],
                'tooltip' => $this->__('Template Name which you would like to be used.'),
                'required' => true,
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
});
JS
        );

        return parent::_beforeToHtml();
    }

    //########################################
}