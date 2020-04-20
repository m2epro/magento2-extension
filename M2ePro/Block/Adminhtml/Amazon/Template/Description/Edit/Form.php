<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Template\Description\Edit;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Amazon\Template\Description\Edit\Form
 */
class Form extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('amazonTemplateDescriptionEditForm');
        // ---------------------------------------
    }

    //########################################

    protected function _prepareForm()
    {
        $templateModel = $this->getHelper('Data\GlobalData')->getValue('tmp_template')->getData();
        $formData = !empty($templateModel) ? $templateModel : ['title' => ''];

        $form = $this->_formFactory->create(['data' => [
            'id'      => 'edit_form',
            'method'  => 'post',
            'action'  => $this->getUrl('*/*/save'),
            'enctype' => 'multipart/form-data'
        ]]);

        $fieldSet = $form->addFieldset('magento_block_template_description_edit_main_general', [
            'legend' => $this->__('General'), 'collapsable' => false
        ]);

        $fieldSet->addField(
            'title',
            'text',
            [
                'name' => 'general[title]',
                'label' => $this->__('Title'),
                'title' => $this->__('Title'),
                'value' => $formData['title'],
                'class' => 'input-text M2ePro-description-template-title',
                'required' => true,
                'tooltip' => $this->__('Short meaningful Policy Title for your internal use.')
            ]
        );

        $dataBlock = $this->createBlock('Amazon_Template_Description_Edit_Data');
        $form->addField(
            'content_html',
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
