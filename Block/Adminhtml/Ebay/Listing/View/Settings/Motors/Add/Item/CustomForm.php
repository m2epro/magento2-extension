<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\View\Settings\Motors\Add\Item;

class CustomForm extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm
{
    //########################################

    protected function _prepareForm()
    {
        $form = $this->_formFactory->create(['data' => [
            'id' => 'motors_custom_item',
            'action' => $this->getUrl('*/*/saveCustomItem'),
        ]]);

        $motorsType = $this->getRequest()->getParam('motors_type');

        $itemTitle = $this->getHelper('Component\Ebay\Motors')->isTypeBasedOnEpids($motorsType)
            ? $this->__('ePID')
            : $this->__('kType');

        $form->addField('custom_motors_item_help_block',
            self::HELP_BLOCK,
            [
                'content' => $this->__('
                    You can add custom %item% value in case there are no suitable %items% available in the list.
                    In order to add it, you should fill in all the fields of the form below.<br/><br/>

                    <b>Please note</b>, the values you specified such as Make, Model, etc. will be sent to eBay in the
                    way you set them without any changes. So, please, ensure that the values you provided are valid
                    and correctly formatted.
                ', $itemTitle, $itemTitle . 's')
            ]
        );

        $form->addField('motors_type',
            'hidden',
            [
                'name' => 'motors_type',
                'value' => $motorsType
            ]
        );

        $this->buildFieldsByItemType($form);

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    //########################################

    public function buildFieldsByItemType(\Magento\Framework\Data\Form $form)
    {
        $this->getRequest()->getParam('motors_type') == \Ess\M2ePro\Helper\Component\Ebay\Motors::TYPE_KTYPE ?
            $this->buildKtypeFields($form) : $this->buildEpidFields($form);
    }

    private function buildEpidFields(\Magento\Framework\Data\Form $form)
    {
        $fieldset = $form->addFieldset(
            'filter_general',
            [
                'legend' => '',
            ]
        );

        $fieldset->addField('epid',
            'text',
            [
                'label' => 'ePID',
                'name' => 'item[epid]',
                'required' => true
            ]
        );

        $fieldset->addField('product_type',
            self::SELECT,
            [
                'name' => 'item[product_type]',
                'label' => $this->__('Type'),
                'required' => true,
                'values' => [
                    '' => '',
                    \Ess\M2ePro\Helper\Component\Ebay\Motors::PRODUCT_TYPE_VEHICLE => $this->__('Car / Truck'),
                    \Ess\M2ePro\Helper\Component\Ebay\Motors::PRODUCT_TYPE_MOTORCYCLE => $this->__('Motorcycle'),
                    \Ess\M2ePro\Helper\Component\Ebay\Motors::PRODUCT_TYPE_ATV => $this->__('ATV / Snowmobiles'),
                ]
            ]
        );

        $fieldset->addField('make',
            'text',
            [
                'label' => 'Make',
                'name' => 'item[make]',
                'required' => true
            ]
        );

        $fieldset->addField('model',
            'text',
            [
                'label' => 'Model',
                'name' => 'item[model]',
                'required' => true
            ]
        );

        $fieldset->addField('submodel',
            'text',
            [
                'label' => 'Submodel',
                'name' => 'item[submodel]'
            ]
        );

        $fieldset->addField('year',
            'text',
            [
                'label' => 'Year',
                'name' => 'item[year]',
                'class' => 'validate-digits',
                'required' => true
            ]
        );

        $fieldset->addField('trim',
            'text',
            [
                'label' => 'Trim',
                'name' => 'item[trim]'
            ]
        );

        $fieldset->addField('engine',
            'text',
            [
                'label' => 'Engine',
                'name' => 'item[engine]'
            ]
        );
    }

    private function buildKtypeFields(\Magento\Framework\Data\Form $form)
    {
        $fieldset = $form->addFieldset(
            'filter_general',
            [
                'legend' => '',
            ]
        );

        $fieldset->addField('ktype',
            'text',
            [
                'label' => 'kType',
                'name' => 'item[ktype]',
                'class' => 'validate-digits',
                'maxlength' => 10,
                'required' => true
            ]
        );

        $fieldset->addField('make',
            'text',
            [
                'label' => 'Make',
                'name' => 'item[make]'
            ]
        );

        $fieldset->addField('model',
            'text',
            [
                'label' => 'Model',
                'name' => 'item[model]'
            ]
        );

        $fieldset->addField('variant',
            'text',
            [
                'label' => 'Variant',
                'name' => 'item[variant]'
            ]
        );

        $fieldset->addField('body_style',
            'text',
            [
                'label' => 'Body Style',
                'name' => 'item[body_style]'
            ]
        );

        $fieldset->addField('type',
            'text',
            [
                'label' => 'Type',
                'name' => 'item[type]'
            ]
        );

        $fieldset->addField('from_year',
            'text',
            [
                'label' => 'Year From',
                'name' => 'item[from_year]',
                'class' => 'validate-digits',
            ]
        );

        $fieldset->addField('to_year',
            'text',
            [
                'label' => 'Year To',
                'name' => 'item[to_year]',
                'class' => 'validate-digits',
            ]
        );

        $fieldset->addField('engine',
            'text',
            [
                'label' => 'Engine',
                'name' => 'item[engine]'
            ]
        );
    }

    //########################################

}