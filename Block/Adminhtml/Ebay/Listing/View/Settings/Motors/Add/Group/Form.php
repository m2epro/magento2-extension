<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\View\Settings\Motors\Add\Group;

class Form extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm
{
    //########################################

    protected function _prepareForm()
    {
        $form = $this->_formFactory->create(['data' => [
            'id' => 'motors_group',
            'action'  => 'javascript:void(0)',
            'method' => 'post'
        ]]);

        $form->addField('filter_form_add_group_help_block',
            self::HELP_BLOCK,
            [
                'content' => $this->__('
                    This Option allows you to Save the preselected <strong>Grid Filters</strong>
                    or <strong>ePIDs/kTypes</strong> as a Group and reapply them for Further Usage.
                    Thus, you will not need to Search for and Choose the same Compatible Vehicles again.
                ')
            ]
        );

        $fieldset = $form->addFieldset(
            'filter_general',
            [
                'legend' => '',
            ]
        );

        $fieldset->addField('title',
            'text',
            [
                'name' => 'title',
                'label' => $this->__('Title'),
                'title' => $this->__('Title'),
                'class' => 'M2ePro-group-title',
                'required' => true
            ]
        );

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    //########################################
}