<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Listing\Edit;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Listing\Edit\Form
 */
class Form extends AbstractForm
{
    protected function _prepareForm()
    {
        $global = $this->getHelper('Data\GlobalData');
        $listing = $global->getValue('edit_listing');

        $form = $this->_formFactory->create(
            ['data' => [
                'id' => 'edit_form',
                'action' => 'javascript:void(0)',
                'method' => 'post'
            ]]
        );

        $form->addField(
            'id',
            'hidden',
            [
                'name' => 'id'
            ]
        );

        $fieldset = $form->addFieldset(
            'edit_listing_fieldset',
            []
        );

        $fieldset->addField(
            'title',
            'text',
            [
                'name' => 'title',
                'class' => 'validate-no-empty M2ePro-listing-title',
                'label' => $this->__('Title'),
                'field_extra_attributes' => 'style="margin-bottom: 0;"'
            ]
        );

        if ($listing) {
            $form->addValues($listing->getData());
        }

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }
}
