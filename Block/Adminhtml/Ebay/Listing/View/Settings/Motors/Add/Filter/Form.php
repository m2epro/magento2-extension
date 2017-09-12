<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\View\Settings\Motors\Add\Filter;

class Form extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm
{
    //########################################

    protected function _prepareForm()
    {
        $form = $this->_formFactory->create(['data' => [
            'id' => 'motors_filter',
            'action'  => $this->getUrl('*/ebay_listing_settings_motors/saveFilter'),
            'method' => 'post'
        ]]);

        $form->addField('filter_form_add_filter_help_block',
            self::HELP_BLOCK,
            [
                'content' => $this->__('
                    This Option allows you to Save the <strong>ePIDs/kTypes</strong> Grid Filter
                    and reapply it in future without Manually Setting up the Filter Parameters
                    again. If some ePID(s)/kType(s) are added or removed, according of the Filter,
                    their Values will <strong>Automatically</strong> be <strong>Added</strong>
                    to or <strong>Removed</strong> from of eBay Item.
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
                'class' => 'M2ePro-filter-title',
                'required' => true
            ]
        );

        $fieldset->addField('note',
            'textarea',
            [
                'name' => 'note',
                'label' => $this->__('Note'),
                'title' => $this->__('Note')
            ]
        );

        $fieldset->addField('filter',
            self::CUSTOM_CONTAINER,
            [
                'name' => 'title',
                'label' => $this->__('Filter'),
                'title' => $this->__('Filter'),
                'text' => <<<HTML
    <ul class="filter_conditions" style="list-style: none"></ul>
HTML
            ]
        );

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    //########################################
}