<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\General\CreateAttribute;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm;
use Ess\M2ePro\Model\Magento\Attribute\Builder as AttributeBuilder;

class Form extends AbstractForm
{
    protected $handlerId;

    protected $allowedTypes = array();
    protected $applyToAllAttributeSets = true;

    //########################################

    protected function _prepareForm()
    {
        $this->handlerId($this->getParentBlock()->getData('handler_id'));
        $this->allowedTypes($this->getParentBlock()->getData('allowed_types'));
        $this->applyToAll($this->getParentBlock()->getData('apply_to_all'));

        $form = $this->_formFactory->create(['data' => [
            'id' => 'edit_form'
        ]]);

        $form->addField('create_attribute_help_block',
            self::HELP_BLOCK,
            [
                'content' => $this->__('
                This Tool allows you to quickly <strong>Create</strong> a new <strong>Magento Attribute</strong>
                for the selected Option. In order to Create an Attribute, you have to fill in the Attribute Label,
                Attribute Code, Catalog Input Type, Scope, Default Value and Attribute Sets fields.<br/><br/>

                <strong>Note:</strong> Some of the fields may not be available for selection. The availability
                depends on the Option for which the Attribute is being created.<br/>
                <strong>Note:</strong> This Option does not imply automatic Product Attribute Value set up.
                After the Attribute
                becomes available in Magento, you should Manually provide the Value for the Product.'
                )
            ]
        );

        $fieldset = $form->addFieldset('magento_create_custom_attribute', [
            'legend' => ' ', 'collapsable' => false
        ]);

        $fieldset->addField('store_label',
            'text',
            [
                'name' => 'store_label',
                'label' => $this->__('Default Label'),
                'required' => true
            ]
        );

        $classes  = 'validate-length maximum-length-30 M2ePro-validate-attribute-code ';
        $classes .= 'M2ePro-validate-attribute-code-to-be-unique';

        $fieldset->addField('code',
            'text',
            [
                'name' => 'code',
                'label' => $this->__('Attribute Code'),
                'class' => $classes,
                'required' => true
            ]
        );

        $inputTypes = [];
        foreach ($this->allowedTypes() as $type) {
            $inputTypes[] = [
                'value' => $type,
                'label' => $this->getTitleByType($type)
            ];
        }

        $fieldset->addField('input_type_select',
            self::SELECT,
            [
                'name' => 'input_type',
                'label' => $this->__('Catalog Input Type'),
                'values' => $inputTypes,
                'value' => '',
                'disabled' => $this->isOneOnlyTypeAllowed()
            ]
        );

        if ($this->isOneOnlyTypeAllowed()) {
            $fieldset->addField('input_type',
                'hidden',
                [
                    'name' => 'input_type',
                    'value' => $this->allowedTypes()[0]
                ]
            );
        }

        $fieldset->addField('scope',
            self::SELECT,
            [
                'name' => 'scope',
                'label' => $this->__('Scope'),
                'values' => [
                    ['value' => AttributeBuilder::SCOPE_STORE,
                     'label' => $this->__('Store View')],
                    ['value' => AttributeBuilder::SCOPE_WEBSITE,
                     'label' => $this->__('Website')],
                    ['value' => AttributeBuilder::SCOPE_GLOBAL,
                     'label' => $this->__('Global')],

                ],
                'value' => ''
            ]
        );

        $fieldset->addField('default_value',
            'text',
            [
                'name' => 'default_value',
                'label' => $this->__('Default Value')
            ]
        );

        $attributeSets = [];
        $values = [];
        foreach($this->getHelper('Magento\AttributeSet')->getAll() as $item) {
            $attributeSets[] = [
                'value' => $item['attribute_set_id'],
                'label' => $item['attribute_set_name']
            ];
            $values[] = $item['attribute_set_id'];
        }

        $fieldset->addField('attribute_sets_multiselect',
            'multiselect',
            [
                'name' => 'attribute_sets[]',
                'label' => $this->__('Attribute Sets'),
                'values' => $attributeSets,
                'value' => $values,
                'required' => true,
                'style' => 'width: 70%',
                'field_extra_attributes' => $this->applyToAll() ? 'style="display: none;"' : ''
            ]
        );

        if ($this->applyToAll()) {
            $fieldset->addField('attribute_sets_multiselect_note',
                'note',
                [
                    'label' => $this->__('Attribute Sets'),
                    'text' => '<strong>'. $this->__('Will be added to the all Attribute Sets.')
                              . '</strong>'
                ]
            );
        }

        $form->setUseContainer(true);
        $this->setForm($form);
        return $this;
    }

    //########################################

    protected function _toHtml()
    {
        $this->jsTranslator->addTranslations([
            'Invalid attribute code'                      => $this->__(
                'Please use only letters (a-z),
                numbers (0-9) or underscore(_) in this field, first character should be a letter.'
            ),
            'Attribute with the same code already exists' => $this->__('Attribute with the same code already exists.'),
            'Attribute has been created.'                 => $this->__('Attribute has been created.'),
            'Please enter a valid date.'                  => $this->__('Please enter a valid date.'),
        ]);

        $this->jsPhp->addConstants(
            $this->getHelper('Data')->getClassConstants('\Ess\M2ePro\Model\Magento\Attribute\Builder')
        );

        $this->jsUrl->addUrls([
            'general/generateAttributeCodeByLabel' => $this->getUrl('general/generateAttributeCodeByLabel'),
            'general/isAttributeCodeUnique' => $this->getUrl('general/isAttributeCodeUnique'),
            'general/createAttribute' => $this->getUrl('general/createAttribute'),
        ]);

        $this->js->addRequireJs(['jQuery' => 'jquery'], <<<JS

        var handler = window['{$this->handlerId()}'];

        jQuery.validator.addMethod('M2ePro-validate-attribute-code', function(value, element) {
            return handler.validateAttributeCode(value, element);
        }, M2ePro.translator.translate('Invalid attribute code'));

        jQuery.validator.addMethod('M2ePro-validate-attribute-code-to-be-unique', function(value, element) {
            return handler.validateAttributeCodeToBeUnique(value, element);
        }, M2ePro.translator.translate('Attribute with the same code already exists'));
JS
);

        return parent::_toHtml();
    }

    //########################################

    public function handlerId($value = null)
    {
        if (is_null($value)) {
            return $this->handlerId;
        }

        $this->handlerId = $value;
        return $this->handlerId;
    }

    public function applyToAll($value = null)
    {
        if (is_null($value)) {
            return $this->applyToAllAttributeSets;
        }

        $this->applyToAllAttributeSets = $value;
        return $this->applyToAllAttributeSets;
    }

    public function allowedTypes($value = null)
    {
        if (is_null($value)) {
            return count($this->allowedTypes) ? $this->allowedTypes : $this->getAllAvailableTypes();
        }

        $this->allowedTypes = $value;
        return $this->allowedTypes;
    }

    // ---------------------------------------

    public function getTitleByType($type)
    {
        $titles =  array(
            AttributeBuilder::TYPE_TEXT            => $this->__('Text Field'),
            AttributeBuilder::TYPE_TEXTAREA        => $this->__('Text Area'),
            AttributeBuilder::TYPE_PRICE           => $this->__('Price'),
            AttributeBuilder::TYPE_SELECT          => $this->__('Select'),
            AttributeBuilder::TYPE_MULTIPLE_SELECT => $this->__('Multiple Select'),
            AttributeBuilder::TYPE_DATE            => $this->__('Date'),
            AttributeBuilder::TYPE_BOOLEAN         => $this->__('Yes/No')
        );

        return isset($titles[$type]) ? $titles[$type] : $this->__('N/A');
    }

    public function getAllAvailableTypes()
    {
        return array(
            AttributeBuilder::TYPE_TEXT,
            AttributeBuilder::TYPE_TEXTAREA,
            AttributeBuilder::TYPE_PRICE,
            AttributeBuilder::TYPE_SELECT,
            AttributeBuilder::TYPE_MULTIPLE_SELECT,
            AttributeBuilder::TYPE_DATE,
            AttributeBuilder::TYPE_BOOLEAN
        );
    }

    public function isOneOnlyTypeAllowed()
    {
        return count($this->allowedTypes()) == 1;
    }

    //########################################
}