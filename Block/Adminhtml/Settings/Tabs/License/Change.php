<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Settings\Tabs\License;

class Change extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm
{
    //########################################

    protected function _prepareForm()
    {
        $form = $this->_formFactory->create([
            'data' => [
                'id' => 'edit_form',
                'method' => 'post',
                'action' => 'javascript:void(0)'
            ]
        ]);

        $fieldSet = $form->addFieldset('change_license', ['legend' => '', 'collapsable' => false]);

        $key = $this->getHelper('Data')->escapeHtml($this->getHelper('Module\License')->getKey());
        $fieldSet->addField('new_license_key',
            'text',
            [
                'name' => 'new_license_key',
                'label' => $this->__('New License Key'),
                'title' => $this->__('New License Key'),
                'value' => $key,
                'required' => true,
            ]
        );

        $form->setUseContainer(true);
        $this->setForm($form);
        return parent::_prepareForm();
    }

    //########################################
}