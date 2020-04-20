<?php
/**
 * Created by PhpStorm.
 * User: myown
 * Date: 3/7/19
 * Time: 1:07 PM
 */

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Template\SellingFormat\Edit\Form;

use \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm as Form;
use \Ess\M2ePro\Model\Walmart\Template\SellingFormat\ShippingOverride;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Walmart\Template\SellingFormat\Edit\Form\ShippingOverrideRules
 */
class ShippingOverrideRules extends \Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock
{
    private $elementFactory;

    protected $_template = 'walmart/template/sellingFormat/form/shipping_override_rules.phtml';

    private $parentForm;
    private $renderer;
    public $generalFromAllAttributeSets = [];

    //########################################

    public function __construct(
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        array $data = []
    ) {
        $this->elementFactory = $context->getElementFactory();
        parent::__construct($context, $data);
    }

    //########################################

    public function setParentForm($form)
    {
        $this->parentForm = $form;
        return $this;
    }

    public function setGeneralFromAllAttributeSets(array $attributes)
    {
        $this->generalFromAllAttributeSets = $attributes;
        return $this;
    }

    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('walmartTemplateDescriptionEditFormShippingOverrideRules');
        // ---------------------------------------
    }

    //########################################

    protected function _beforeToHtml()
    {
        // ---------------------------------------

        $addShippingOverrideRuleButton = $this
            ->createBlock('Magento\Button')
            ->setData([
                'onclick' => 'WalmartTemplateSellingFormatObj.addRow();',
                'class'   => 'add add_shipping_override_rule_button primary'
            ]);
        $this->setData('add_shipping_override_rule_button', $addShippingOverrideRuleButton);

        // ---------------------------------------

        $shippingOverrideRuleService = $this->createElement(
            Form::SELECT,
            [
                'html_id' => 'shipping_override_rule_service_%i%',
                'name'    => 'shipping_override_rule[%i%][method]',
                'values'  => $this->getShippingOverrideRuleSeviceOptions(),
                'value'   => '',
                'required' => true,
                'disabled' => true,
                'class'   => 'shipping-override-service',
                'style'    => 'display: none;'
            ]
        );
        $this->setData('shipping_override_rule_service', $shippingOverrideRuleService);

        // ---------------------------------------

        $shippingOverrideRuleLocation = $this->createElement(
            Form::SELECT,
            [
                'html_id'  => 'shipping_override_rule_location_%i%',
                'name'     => 'shipping_override_rule[%i%][region]',
                'values'   => $this->getShippingOverrideRuleLocationOptions(),
                'value'    => '',
                'disabled' => true,
                'class'    => 'shipping-override-location',
                'style'    => 'display: none;'
            ]
        );
        $this->setData('shipping_override_rule_location', $shippingOverrideRuleLocation);

        // ---------------------------------------

        $shippingOverrideRuleAction = $this->createElement(
            Form::SELECT,
            [
                'html_id'  => 'shipping_override_rule_action_%i%',
                'name'     => 'shipping_override_rule[%i%][is_shipping_allowed]',
                'values'   => [
                    [
                        'value' => '',
                        'label' => '',
                        'attrs' => ['class' => 'empty']
                    ],
                    [
                        'value' => ShippingOverride::IS_SHIPPING_ALLOWED_ADD_OR_OVERRIDE,
                        'label' => $this->__('Add/Override'),
                    ],
                    [
                        'value' => ShippingOverride::IS_SHIPPING_ALLOWED_REMOVE,
                        'label' => $this->__('Remove'),
                    ],
                ],
                'value'    => '',
                'required' => true,
                'disabled' => true,
                'class'    => 'shipping-override-action',
                'style'    => 'display: none;'
            ]
        );
        $this->setData('shipping_override_rule_action', $shippingOverrideRuleAction);

        // ---------------------------------------

        $shippingOverrideRuleCostMode = $this->createElement(
            Form::SELECT,
            [
                'html_id'  => 'shipping_override_rule_cost_mode_%i%',
                'name'     => 'shipping_override_rule[%i%][cost_mode]',
                'values'   => [
                    [
                        'value' => '',
                        'label' => '',
                        'attrs' => ['class' => 'empty']
                    ],
                    [
                        'value' => ShippingOverride::COST_MODE_FREE,
                        'label' => $this->__('Free'),
                    ],
                    [
                        'value' => ShippingOverride::COST_MODE_CUSTOM_VALUE,
                        'label' => $this->__('Custom Value'),
                        'attrs' => ['class' => 'shipping-override-rule-cost-mode-custom-value']
                    ],
                    [
                        'value' => ShippingOverride::COST_MODE_CUSTOM_ATTRIBUTE,
                        'label' => $this->__('Custom Attribute'),
                        'attrs' => ['class' => 'shipping-override-rule-cost-mode-custom-attribute']
                    ],
                ],
                'value'    => '',
                'required' => true,
                'disabled' => true,
                'class'    => 'shipping-override-cost-mode',
                'style'    => 'display: none;'
            ]
        );
        $this->setData('shipping_override_rule_cost_mode', $shippingOverrideRuleCostMode);

        // ---------------------------------------

        $shippingOverrideRuleCostValue = $this->createElement(
            'text',
            [
                'html_id'  => 'shipping_override_rule_cost_value_%i%',
                'name'     => 'shipping_override_rule[%i%][cost_value]',
                'value'    => '',
                'required' => true,
                'disabled' => true,
                'class'    => 'M2ePro-validation-float shipping-override-cost-custom-value',
                'style'    => 'display: none;'
            ]
        );
        $this->setData('shipping_override_rule_cost_value', $shippingOverrideRuleCostValue);

        // ---------------------------------------

        $shippingOverrideRuleCostAttribute = $this->createElement(
            Form::SELECT,
            [
                'html_id'  => 'shipping_override_rule_cost_attribute_%i%',
                'name'     => 'shipping_override_rule[%i%][cost_attribute]',
                'values'   => [
                    [
                        'value' => '',
                        'label' => '',
                        'attrs' => ['class' => 'empty']
                    ],
                    $this->getMagentoAttributesOptions()
                ],
                'value'    => '',
                'required' => true,
                'disabled' => true,
                'class'    => 'shipping-override-cost-custom-attribute',
                'style'    => 'display: none;'
            ]
        );
        $this->setData('shipping_override_rule_cost_attribute', $shippingOverrideRuleCostAttribute);

        // ---------------------------------------

        $this->setData('marketplaces', $this->getHelper('Component\Walmart')->getMarketplacesAvailableForApiCreation());

        // ---------------------------------------

        $removeButton = $this
            ->createBlock('Magento\Button')
            ->addData([
                'label'   => $this->__('Remove'),
                'onclick' => 'WalmartTemplateSellingFormatObj.removeRow(this);',
                'class'   => 'delete icon-btn remove_shipping_override_rule_button'
            ]);
        $this->setData('remove_shipping_override_rule_button', $removeButton);

        // ---------------------------------------

        return parent::_beforeToHtml();
    }

    //########################################

    public function getShippingOverrideRuleSeviceOptions()
    {
        $options = [
            [
                'value' => '',
                'label' => '',
                'attrs' => ['class' => 'empty']
            ]
        ];

        foreach ($this->getShippingOverrideMethodsUs() as $code => $label) {
            $options[] = [
                'value' => $code,
                'label' => $this->__($label),
                'attrs' => [
                    'marketplace_id' => \Ess\M2ePro\Helper\Component\Walmart::MARKETPLACE_US,
                    'class'          => 'm2epro-marketplace-depended-option'
                ]
            ];
        }

        foreach ($this->getShippingOverrideMethodsCanada() as $code => $label) {
            $options[] = [
                'value' => $code,
                'label' => $this->__($label),
                'attrs' => [
                    'marketplace_id' => \Ess\M2ePro\Helper\Component\Walmart::MARKETPLACE_CA,
                    'class'          => 'm2epro-marketplace-depended-option'
                ]
            ];
        }

        return $options;
    }

    public function getShippingOverrideRuleLocationOptions()
    {
        $options = [
            [
                'value' => '',
                'label' => '',
                'attrs' => ['class' => 'empty']
            ]
        ];

        foreach ($this->getShippingOverrideRegionsUs() as $code => $label) {
            $options[] = [
                'value' => $code,
                'label' => $this->__($label),
                'attrs' => [
                    'marketplace_id' => \Ess\M2ePro\Helper\Component\Walmart::MARKETPLACE_US,
                    'class'          => 'm2epro-marketplace-depended-option'
                ]
            ];
        }

        foreach ($this->getShippingOverrideRegionsCanada() as $code => $label) {
            $options[] = [
                'value' => $code,
                'label' => $this->__($label),
                'attrs' => [
                    'marketplace_id' => \Ess\M2ePro\Helper\Component\Walmart::MARKETPLACE_CA,
                    'class'          => 'm2epro-marketplace-depended-option'
                ]
            ];
        }

        return $options;
    }

    //########################################

    public function getMagentoAttributesOptions()
    {
        $optionsResult = [];

        foreach ($this->generalFromAllAttributeSets as $attribute) {
            $optionsResult[] = [
                'value' => $attribute['code'],
                'label' => $this->escapeHtml($attribute['label'])
            ];
        }

        return [
            'value' => $optionsResult,
            'label' => 'Magento Attribute',
            'attrs' => ['is_magento_attribute' => true]
        ];
    }

    private function createElement($type, array $data)
    {
        $element = $this->elementFactory->create(
            $type,
            [
                'data' => $data
            ]
        );
        $element->setForm($this->parentForm);

        if ($this->renderer === null) {
            $this->renderer = $this->createBlock('Magento_Form_Renderer_Element');
        }

        $element->setRenderer($this->renderer);
        return $element;
    }

    //########################################

    public function getShippingOverrideRegionsUs()
    {
        return [
            'STREET_48_STATES'        => 'Street 48 States',
            'PO_BOX_48_STATES'        => 'PO Box 48 States',
            'STREET_AK_AND_HI'        => 'Street AK and HI',
            'PO_BOX_AK_AND_HI'        => 'PO Box AK and HI',
            'STREET_US_PROTECTORATES' => 'Street US Protectorates',
            'PO_BOX_US_PROTECTORATES' => 'PO Box US Protectorates',
            'APO_FPO'                 => 'APO FPO'
        ];
    }

    public function getShippingOverrideMethodsUs()
    {
        return [
            'VALUE'                    => 'Value',
            'STANDARD'                 => 'Standard',
            'EXPEDITED'                => 'Expedited',
            'FREIGHT'                  => 'Freight',
            'ONE_DAY'                  => 'One day',
            'FREIGHT_WITH_WHITE_GLOVE' => 'Freight with white glove'
        ];
    }

    //########################################

    public function getShippingOverrideRegionsCanada()
    {
        return [
            'STREET_URBAN_ONTEAST' => 'Street Urban Ontario East',
            'POBOX_URBAN_ONTEAST'  => 'PO Box Urban Ontario East',
            'STREET_URBAN_QUEBEC'  => 'Street Urban Quebec',
            'POBOX_URBAN_QUEBEC'   => 'PO Box Urban Quebec',
            'STREET_URBAN_WEST'    => 'Street Urban West',
            'POBOX_URBAN_WEST'     => 'PO Box Urban West',
            'STREET_REMOTE_QUEBEC' => 'Street Remote Quebec',
            'POBOX_REMOTE_QUEBEC'  => 'PO Box Remote Quebec',
            'STREET_REMOTE_CANADA' => 'Street Remote Canada',
            'POBOX_REMOTE_CANADA'  => 'PO Box Remote Canada',
        ];
    }

    public function getShippingOverrideMethodsCanada()
    {
        return [
            'STANDARD'  => 'Standard',
            'EXPEDITED' => 'Expedited',
        ];
    }

    //########################################
}
