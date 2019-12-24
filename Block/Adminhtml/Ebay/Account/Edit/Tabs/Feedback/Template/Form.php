<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Account\Edit\Tabs\Feedback\Template;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Ebay\Account\Edit\Tabs\Feedback\Template\Form
 */
class Form extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm
{
    protected function _prepareForm()
    {
        $template = $this->getHelper('Data\GlobalData')->getValue('edit_template');

        $form = $this->_formFactory->create(
            ['data' => [
                'id' => 'edit_feedback_template_form',
                'action' => 'javascript:void(0)',
                'method' => 'post',
            ]]
        );

        $form->addField(
            'id',
            'hidden',
            [
                'name' => 'id'
            ]
        );

        $form->addField(
            'account_id',
            'hidden',
            [
                'name' => 'account_id',
                'value' => $this->getRequest()->getParam('account_id')
            ]
        );

        $fieldset = $form->addFieldset(
            'edit_feedback_template',
            []
        );

        $fieldset->addField(
            'body',
            'textarea',
            [
                'name' => 'body',
                'required' => true,
                'label' => $this->__('Message'),
                'field_extra_attributes' => 'style="margin-top: 30px;"'
            ]
        );

        if ($template) {
            $form->addValues($template->getData());
        }

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }
}
