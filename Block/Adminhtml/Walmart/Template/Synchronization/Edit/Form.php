<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Template\Synchronization\Edit;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Walmart\Template\Synchronization\Edit\Form
 */
class Form extends AbstractForm
{
    //########################################

    protected function _prepareForm()
    {
        $template = $this->getHelper('Data\GlobalData')->getValue('tmp_template');
        $formData = $template !== null
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
            'magento_block_walmart_template_synchronization_general_general',
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
                'tooltip' => $this->__('Policy Title for your internal use.'),
                'required' => true,
            ]
        );

        $dataBlock = $this->createBlock('Walmart_Template_Synchronization_Edit_Data');
        $form->addField(
            'container_html',
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
