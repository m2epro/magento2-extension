<?php

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Template\Synchronization\Edit;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm;

class Form extends AbstractForm
{
    //########################################

    protected function _prepareForm()
    {
        $template = $this->getHelper('Data\GlobalData')->getValue('tmp_template');
        $formData = !is_null($template)
                    ? array_merge($template->getData(), $template->getChildObject()->getData())
                    : ['title' => ''];

        $form = $this->_formFactory->create(
            [
                'data' => [
                    'id'      => 'edit_form',
                    'method'  => 'post',
                    'action'  => $this->getUrl('*/*/save'),
                    'enctype' => 'multipart/form-data'
                ]
            ]
        );

        $fieldset = $form->addFieldset(
            'magento_block_amazon_template_synchronization_general_general',
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
                'class' => 'M2ePro-synchronization-tpl-title',
                'tooltip' => $this->__('Short meaningful Policy Title for your internal use.'),
                'required' => true,
            ]
        );

        $dataBlock = $this->createBlock('Amazon\Template\Synchronization\Edit\Data');
        $form->addField('container_html',
            self::CUSTOM_CONTAINER,
            [
                'text' => $dataBlock->toHtml()
            ]
        );

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    //########################################
}