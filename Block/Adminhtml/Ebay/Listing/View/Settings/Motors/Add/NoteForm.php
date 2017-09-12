<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\View\Settings\Motors\Add;

class NoteForm extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm
{
    //########################################

    protected function _prepareForm()
    {
        $form = $this->_formFactory->create(['data' => [
            'id' => 'motors_note'
        ]]);

        $form->addField('filter_form_help_block',
            self::HELP_BLOCK,
            [
                'content' => $this->__('
                    This Option allows you to <strong>Add a Note</strong> to the selected
                    Compatible Vehicle where you can provide Additional Details.
                ')
            ]
        );

        $fieldset = $form->addFieldset(
            'filter_general',
            [
                'legend' => '',
            ]
        );

        $fieldset->addField('note',
            'textarea',
            [
                'name' => 'note',
                'label' => $this->__('Note'),
                'title' => $this->__('Note'),
                'required' => true
            ]
        );

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }
    //########################################
}