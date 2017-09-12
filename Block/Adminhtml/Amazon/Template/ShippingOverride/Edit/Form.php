<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Template\ShippingOverride\Edit;

use \Ess\M2ePro\Model\Amazon\Template\ShippingOverride\Service;

class Form extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm
{
    public $formData = NULL;

    private $enabledMarketplaces = NULL;
    private $attributes = NULL;
    private $overrideDictionaryData = NULL;

    protected $_template = 'amazon/template/shipping_override/edit/form.phtml';

    protected $resourceConnection;

    //########################################

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        array $data = []
    )
    {
        $this->resourceConnection = $resourceConnection;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\ActiveRecord\Factory
     */
    public function getActiveRecordFactory()
    {
        return $this->activeRecordFactory;
    }

    //########################################

    protected function _prepareForm()
    {
        /** @var \Ess\M2ePro\Model\Amazon\Template\ShippingOverride $model */
        $model = $this->getHelper('Data\GlobalData')->getValue('tmp_template');

        $formData = array();
        if ($model) {
            $formData = $model->toArray();
        }

        if (!empty($formData)) {
            $formData['shipping_override_rule'] = $model->getServices();
        }

        $default = array(
            'id' => '',
            'title' => '',
            'marketplace_id' => '',
            'shipping_override_rule' => array()
        );

        $default['marketplace_id'] = $this->getRequest()->getParam('marketplace_id', '');

        $formData = array_merge($default, $formData);

        $this->formData = $formData;

        $form = $this->_formFactory->create(
            [
                'data' => [
                    'id'      => 'edit_form',
                    'method'  => 'post',
                    'action'  => $this->getUrl('*/*/save'),
                    'enctype' => 'multipart/form-data',
                    'class' => 'admin__scope-old'
                ]
            ]
        );

        $fieldset = $form->addFieldset(
            'magento_block_amazon_template_shipping_override_general',
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
                'class' => 'M2ePro-shipping-override-tpl-title',
                'tooltip' => $this->__('Short meaningful Policy Title for your internal use.'),
                'required' => true,
            ]
        );

        if (!empty($formData['marketplace_id'])) {
            $marketplace = $this->activeRecordFactory->getObjectLoaded('Marketplace', $formData['marketplace_id']);

            $marketplaceInput = $this->elementFactory->create('hidden', ['data' => [
                'html_id' => 'marketplace_id',
                'name' => 'marketplace_id',
                'no_span' => true,
                'value' => $formData['marketplace_id']
            ]]);
            $marketplaceInput->setForm($form);

            $fieldset->addField('marketplace_id_container',
                self::CUSTOM_CONTAINER,
                [
                    'label' => $this->__('Marketplace'),
                    'title' => $this->__('Marketplace'),
                    'text' => <<<HTML
{$marketplaceInput->toHtml()}
<span>{$marketplace->getTitle()}</span>
HTML
                ]
            );

        } else {
            $marketplaces = [['value' => '', 'label' => '']];
            foreach ($this->getEnabledMarketplaces() as $marketplace) {
                $marketplaces[] = [
                    'attrs' => [
                        'currency' => $marketplace->getChildObject()->getCurrency()
                    ],
                    'value' => $marketplace->getId(),
                    'label' => $marketplace->getTitle()
                ];
            }

            $fieldset->addField(
                'marketplace_id',
                self::SELECT,
                [
                    'name' => 'marketplace_id',
                    'label' => $this->__('Marketplace'),
                    'title' => $this->__('Marketplace'),
                    'values' => $marketplaces,
                    'value' => $formData['marketplace_id'],
                    'required' => true,
                ]
            );
        }

        $fieldset = $form->addFieldset(
            'magento_block_amazon_template_shipping_override_rules',
            [
                'legend' => $this->__('Overrides'),
                'collapsable' => false
            ]
        );

        $fieldset->addField(
            'reles_container',
            self::CUSTOM_CONTAINER,
            [
                'text' => '<div id="shipping_override_rule_table_container"></div>',
                'css_class' => 'm2epro-custom-container-full-width'
            ]
        );

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    protected function _prepareLayout()
    {
        $this->appendHelpBlock([
            'content' => $this->__('
        The Shipping Override Policy allows to override Shipping Settings provided in your Amazon Seller Central. So you
        can add/edit selected Shipping Service and  Locale as well as the Shipping Cost Settings.<br/><br/>

        <strong>Note:</strong> the Settings specified in Shipping Override Policy are not visible in Seller Central.
        They become available only after the Buyer add the Item with such Settings into a Cart in selected Locale.
        <br/><br/>

        More detailed information about ability to work with this Page you can find
        <a href="%url%" target="_blank" class="external-link">here</a>.',
                $this->getHelper('Module\Support')->getDocumentationArticleUrl('x/jAA0AQ')
            )
        ]);

        return parent::_prepareLayout();
    }

    protected function _beforeToHtml()
    {
        parent::_beforeToHtml();

        // ---------------------------------------
        $buttonBlock = $this->createBlock('Magento\Button')
            ->setData(array(
                'label'   => $this->__('Add Override'),
                'onclick' => 'AmazonTemplateShippingOverrideObj.addRow();',
                'class' => 'action primary add_shipping_override_rule_button'
            ));
        $this->setChild('add_shipping_override_rule_button', $buttonBlock);
        // ---------------------------------------

        // ---------------------------------------
        $buttonBlock = $this->createBlock('Magento\Button')
            ->setData(array(
                'label'   => $this->__('Remove'),
                'onclick' => 'AmazonTemplateShippingOverrideObj.removeRow(this);',
                'class' => 'delete icon-btn remove_shipping_override_rule_button'
            ));
        $this->setChild('remove_shipping_override_rule_button', $buttonBlock);
        // ---------------------------------------
    }

    public function _toHtml()
    {
        $this->jsPhp->addConstants(
            $this->getHelper('Data')->getClassConstants('\Ess\M2ePro\Helper\Component\Amazon')
        );

        $this->jsPhp->addConstants(
            $this->getHelper('Data')->getClassConstants('\Ess\M2ePro\Model\Amazon\Template\ShippingOverride\Service')
        );

        $this->jsUrl->addUrls([
            'formSubmit'    => $this->getUrl('*/amazon_template_shippingOverride/save', [
                '_current' => $this->getRequest()->getParam('id'),
                'close_on_save' => $this->getRequest()->getParam('close_on_save')
            ]),
            'formSubmitNew' => $this->getUrl('*/amazon_template_shippingOverride/save'),
            'deleteAction'  => $this->getUrl('*/amazon_template_shippingOverride/delete', [
                'id' => $this->getRequest()->getParam('id'),
                'close_on_save' => $this->getRequest()->getParam('close_on_save')
            ])
        ]);

        $this->jsTranslator->addTranslations([
            'Any' => $this->__('Any'),
            'Add Shipping Override Policy' => $this->__('Add Shipping Override Policy'),
            'The specified Title is already used for other Policy. Policy Title must be unique.' =>
                $this->__('The specified Title is already used for other Policy. Policy Title must be unique.'),
        ]);

        $title = $this->getHelper('Data')->escapeJs($this->getHelper('Data')->escapeHtml($this->formData['title']));
        $overrideServicesData = $this->getHelper('Data')->jsonEncode($this->getOverrideDictionaryData());

        if ($this->formData['id'] != '') {
            $rules = $this->getHelper('Data')->jsonEncode($this->formData['shipping_override_rule']);
            $rulesRenderJs = 'AmazonTemplateShippingOverrideObj.renderRules(' . $rules . ')';
        } else {
            $rulesRenderJs = <<<JS
    $('marketplace_id')
        .observe('change', AmazonTemplateShippingOverrideObj.marketplaceChange)
        .simulate('change');
JS;
        }

        $this->js->addOnReadyJs(<<<JS

    M2ePro.formData.id = '{$this->getRequest()->getParam('id')}';
    M2ePro.formData.title = '{$title}';

    document.getElementById('shipping_override_rule_table_container').appendChild(
        document.getElementById('shipping_override_rule_table')
    );

    $('magento_block_amazon_template_shipping_override_rules').hide();
    $('shipping_override_rule_table').show();

    require([
        'M2ePro/Amazon/Template/ShippingOverride',
    ], function(){

        AmazonTemplateShippingOverrideObj = new AmazonTemplateShippingOverride();

        AmazonTemplateShippingOverrideObj.overrideServicesData = {$overrideServicesData};

        {$rulesRenderJs}

        $('{$this->getForm()->getId()}').observe('change', function(e) {
            if (e.target.tagName != 'SELECT') {
                return;
            }

            $(e.target).select('.empty') &&
            $(e.target).select('.empty').length && $(e.target).select('.empty')[0].hide();
        });
    });
JS
        );

        return parent::_toHtml();
    }

    //########################################

    public function getAttributes()
    {
        if (is_null($this->attributes)) {

            /** @var \Ess\M2ePro\Helper\Magento\Attribute $magentoAttributeHelper */
            $magentoAttributeHelper = $this->getHelper('Magento\Attribute');
            $this->attributes = $magentoAttributeHelper->getGeneralFromAllAttributeSets();
            $this->attributes = $magentoAttributeHelper
                                    ->filterByInputTypes($this->attributes, array('text', 'select', 'price'));

            if ($this->formData['id']) {
                foreach ($this->formData['shipping_override_rule'] as $rule) {

                    if ($rule['cost_mode'] != Service::COST_MODE_CUSTOM_ATTRIBUTE ||
                        $magentoAttributeHelper->isExistInAttributesArray($rule['cost_value'], $this->attributes) ||
                        $rule['cost_value'] == '') {
                        continue;
                    }

                    $this->attributes[] = [
                        'code' => $rule['cost_value'],
                        'label' => $magentoAttributeHelper->getAttributeLabel($rule['cost_value'])
                    ];
                }
            }
        }

        return $this->attributes;
    }

    //########################################

    public function getEnabledMarketplaces()
    {
        if (is_null($this->enabledMarketplaces)) {
            $collection = $this->activeRecordFactory->getObject('Marketplace')->getCollection();
            $collection->addFieldToFilter('component_mode', \Ess\M2ePro\Helper\Component\Amazon::NICK);
            $collection->addFieldToFilter('status', \Ess\M2ePro\Model\Marketplace::STATUS_ENABLE);
            $collection->setOrder('sorder', 'ASC');

            $this->enabledMarketplaces = $collection;
        }

        return $this->enabledMarketplaces->getItems();
    }

    //########################################

    public function getOverrideDictionaryData()
    {
        if (is_null($this->overrideDictionaryData)) {
            $connection = $this->resourceConnection->getConnection();
            $table = $this->resourceConnection->getTableName('m2epro_amazon_dictionary_shipping_override');

            $this->overrideDictionaryData = $connection->select()->from($table)->query()->fetchAll();
        }

        return $this->overrideDictionaryData;
    }

    //########################################
}