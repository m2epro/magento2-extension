<?php

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product\Variation\Manage\Tabs\Settings;

use Ess\M2ePro\Model\Walmart\Listing\Product\Variation\Manager\Type\Relation\ParentRelation;

class Form extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm
{
    public const MESSAGE_TYPE_ERROR = 'error';
    public const MESSAGE_TYPE_WARNING = 'warning';

    protected $warningsCalculated = false;

    protected $channelThemes = null;
    protected $childListingProducts = null;
    protected $currentProductVariations = null;

    /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
    protected $listingProduct;

    /** @var \Ess\M2ePro\Model\Walmart\Listing\Product\Variation\Matcher\Attribute $matcherAttribute */
    protected $matcherAttributes;

    protected $messages = [];

    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;

    public function __construct(
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Ess\M2ePro\Helper\Data $dataHelper,
        array $data = []
    ) {
        $this->dataHelper = $dataHelper;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    protected function _prepareForm()
    {
        $form = $this->_formFactory->create(
            [
                'data' => [
                    'id' => 'variation_settings_form',
                    'method' => 'post',
                    'action' => 'javascript:void(0)',
                ],
            ]
        );

        $magentoProductVariations = $this->getListingProduct()
                                         ->getMagentoProduct()
                                         ->getVariationInstance()
                                         ->getVariationsTypeStandard();

        $fieldset = $form->addFieldset(
            'possible_attributes_fieldset',
            [
                'legend' => __('Walmart Variant Attributes'),
                'collapsable' => true,
                'direction_class' => 'to-right',
                'tooltip' => __(
                    'Select Walmart Variant Attribute you will use to vary your Item on the Channel. You can set more
                    than one Variant Attribute for Variational Item that varies by multiple attributes.<br><br>

                    <strong>Note:</strong> the list of Walmart Variant Attributes available for the selection is
                    determined by Product Type assigned to your Product.'
                ),
            ]
        );

        /** @var \Ess\M2ePro\Model\Walmart\Listing\Product $walmartListingProduct */
        $walmartListingProduct = $this->getListingProduct()->getChildObject();
        $possibleAttributes = $walmartListingProduct->isExistsProductType()
            ? $walmartListingProduct->getProductType()->getVariationAttributes()
            : [];

        $channelAttributes = $this->getListingProductTypeModel()->getChannelAttributes();

        $html = <<<HTML
<table id="channel_attributes_view" class="data-grid data-grid-not-hovered" cellspacing="0" cellpadding="0">
    <tbody>
HTML;

        if (!$this->getListingProductTypeModel()->hasChannelAttributes()) {
            $html .= <<<HTML
        <tr>
            <td class="label" style="border-right: none; border-top: 1px solid #D6D6D6 !important;">
                {$this->__('No Walmart Variant Attributes selected')}
            </td>
        </tr>
HTML;
        } else {
            $html .= <<<HTML
        <tr>
            <td class="label" style="font-weight: bold; border-right: none;
                border-bottom: 1px solid #D6D6D6 !important;">
                {$this->__('Walmart Variant Attributes')}
            </td>
        </tr>
HTML;

            foreach ($possibleAttributes as $attribute) {
                if (in_array($attribute, $channelAttributes)) {
                    $html .= <<<HTML
        <tr class="channel_attribute">
            <td class="label" style="border-right: none;">
                <input type="checkbox" class="admin__control-checkbox"
                    value="{$attribute}" checked="checked" disabled="disabled">
                <label>&nbsp;&nbsp;
                    {$this->__($attribute)}
                </label>
            </td>
        </tr>
HTML;
                }
            }
        }

        $html .= <<<HTML
    </tbody>
    <tfoot>
HTML;

        if (!$this->getListingProduct()->isSetProcessingLock('child_products_in_action')) {
            $button = $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Magento\Button::class)->setData([
                'class' => 'action primary',
                'label' => $this->getListingProductTypeModel()->hasChannelAttributes() ?
                    __('Change') : __('Set Attributes'),
                'onclick' => 'ListingGridObj.variationProductManageHandler.changeChannelAttributes(this)',
            ])->toHtml();

            $html .= <<<HTML
            <tr id="change_channel_attributes_btn">
                <td class="label" colspan="2" style="border: none; text-align: right;">
                    {$button}
                </td>
            </tr>
HTML;
        }

        $html .= <<<HTML
    </tfoot>

</table>

<form id="variation_manager_channel_attributes_form" action="javascript:void(0);" style="display: none;">

    <table class="data-grid data-grid-not-hovered" cellspacing="0" cellpadding="0">
        <tbody>
            <tr>
                <td class="label" style="font-weight: bold; border-bottom: 1px solid #D6D6D6 !important;">
                    {$this->__('Walmart Variant Attributes')}
                </td>
            </tr>
HTML;
        foreach ($possibleAttributes as $attribute) {
            $checked = '';
            if (in_array($attribute, $channelAttributes)) {
                $checked = 'checked="checked"';
            }

            $html .= <<<HTML
            <tr class="channel_attribute">
                <td class="label" style="border-right: none;">
                    <input name="channel_attribute[]"
                           class="M2ePro-walmart-required-channel-attribute admin__control-checkbox"
                           type="checkbox" value="{$attribute}" {$checked}
                           style="margin-top: 0 !important;">
                    <label>&nbsp;&nbsp;
                        {$this->__($attribute)}
                    </label>
                    <label class="mage-error"
                           id="M2ePro-walmart-required-channel-attribute-error" style="display: none;">
</label>
                </td>
            </tr>
HTML;
        }

        $buttonConfirm = $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Magento\Button::class)->setData([
            'class' => 'action primary',
            'label' => __('Confirm'),
            'onclick' => 'ListingGridObj.variationProductManageHandler.setChannelAttributes(this)',
        ])->toHtml();

        $html .= <<<HTML
        </tbody>

        <tfoot>
            <tr id="change_channel_attributes_action">
                <td class="label" style="border: none; text-align: right;">
                    <a href="javascript:void(0);"
                       onclick="ListingGridObj.variationProductManageHandler.cancelChannelAttributes(this);">
                       {$this->__('Cancel')}</a>&nbsp;&nbsp;
                    {$buttonConfirm}
                </td>
            </tr>
        </tfoot>

    </table>
</form>
HTML;

        $fieldset->addField(
            'channel_attributes_container',
            self::CUSTOM_CONTAINER,
            [
                'text' => $html,
                'css_class' => 'm2epro-custom-container-full-width',
            ]
        );

        if ($this->getListingProductTypeModel()->hasChannelAttributes()) {
            $this->js->add(
                <<<JS
    ListingGridObj.variationProductManageHandler.virtualWalmartMatchedAttributes = false;
    ListingGridObj.variationProductManageHandler.walmartVariationSet = false;
JS
            );

            $this->jsTranslator->addTranslations([
                'help_icon_magento_greater_left' =>
                    __(
                        'This Walmart Attribute and its Value are virtualized based on the selected Magento
                Variational Attribute and its Value as physically this Walmart Attribute does not exist.'
                    ),
                'help_icon_magento_greater_right' =>
                    __(
                        'Select a particular Option of the Attribute to fix it for virtualized Walmart Attribute.
                        Please, be thoughtful as only those Variations of Magento Product which contains the selected
                        Option can be sold on Walmart.'
                    ),

                'help_icon_walmart_greater_left' =>
                    __(
                        'This Magento Attribute and its Value are virtualized based on the selected Walmart
                        Variational Attribute and its Value as physically this Magento Attribute does not exist.'
                    ),
                'help_icon_walmart_greater_right' =>
                    __(
                        'Select a particular Option of the Attribute to fix it for virtualized Magento Attribute.
                        Please, be thoughtful as your offer will be available only for those Buyers who selected the
                        same Option.'
                    ),

                'duplicate_magento_attribute_error' =>
                    __(
                        'The Magento Attributes which you selected in your settings have the same Labels. Such
                        combination is invalid. Please, add the valid combination of Attributes.'
                    ),
                'duplicate_walmart_attribute_error' =>
                    __(
                        'The Walmart Attributes which you selected in your settings have the same Labels. Such
                        combination is invalid. Please, add the valid combination of Attributes.'
                    ),

                'change_option' => __('Change option'),
            ]);

            $fieldset = $form->addFieldset(
                'attributes_fieldset',
                [
                    'legend' => __('Variation Attributes'),
                    'collapsable' => true,
                    'tooltip' => __(
                        '
                        To sell Magento Variational Product as Walmart Variant Group, you need to set a correspondence
                        between Magento Variational Attribute(s) and Walmart Variant Attribute(s). Click
                        <i>Set Attributes</i> to match the related Attributes and <i>Confirm</i> your choice.<br><br>

                        <strong>Important:</strong> If you change the Variational Attribute or Variational Option names
                        in Magento, you will have to match Magento Variational Attribute(s) with Walmart Variant
                        Attribute(s) again.<br><br>

                        <strong>Note:</strong> Matching of Attributes is required. Otherwise, your Variational Item
                        cannot be listed on the Channel.'
                    ),
                ]
            );

            $html = <<<HTML
<div id="variation_settings_form_help_icon_tpl" style="display: none">
    <div class="m2epro-field-tooltip m2epro-field-tooltip-right admin__field-tooltip" style="margin-top: 0px;">
        <a class="admin__field-tooltip-action" href="javascript://"></a>
        <div class="admin__field-tooltip-content">
            <span class="tool-tip-message-text"></span>
         </div>
    </div>
</div>
<form id="variation_manager_attributes_form" action="javascript:void(0);">
HTML;

            if (!$this->hasMatchedAttributes() && !$this->getMatcherAttributes()->isAmountEqual()) {
                $html .= <<<HTML
    <table class="data-grid data-grid-not-hovered" cellspacing="0" cellpadding="0">
        <thead>
            <tr>
                <td class="label" style="width: 25%;
                                         font-weight: bold;
                                         border-bottom: 1px solid #D6D6D6 !important;
                                         border-right: 1px solid #D6D6D6 !important;">
                    {$this->__('Magento Attributes')}
                </td>
                <td class="value" style="font-weight: bold; border-bottom: 1px solid #D6D6D6 !important;">
                    {$this->__('Walmart Attributes')}
                </td>
            </tr>
        </thead>
        <tbody></tbody>
        <tfoot></tfoot>
    </table>
HTML;

                if ($this->getMatcherAttributes()->isSourceAmountGreater()) {
                    $matchedAttributes = json_encode($this->getMatchedAttributes(), JSON_FORCE_OBJECT);
                    $productAttributes = \Ess\M2ePro\Helper\Json::encode($this->getProductAttributes());
                    $destinationAttributes = \Ess\M2ePro\Helper\Json::encode($this->getDestinationAttributes());
                    $magentoVariationSet = \Ess\M2ePro\Helper\Json::encode($magentoProductVariations['set']);

                    $this->js->add(
                        <<<JS
    ListingGridObj.variationProductManageHandler.matchingType = ListingGridObj
        .variationProductManageHandler.MATCHING_TYPE_VIRTUAL_WALMART;
    ListingGridObj.variationProductManageHandler.matchedAttributes = {$matchedAttributes};
    ListingGridObj.variationProductManageHandler.productAttributes = {$productAttributes};
    ListingGridObj.variationProductManageHandler.destinationAttributes = {$destinationAttributes};
    ListingGridObj.variationProductManageHandler.magentoVariationSet = {$magentoVariationSet};

    ListingGridObj.variationProductManageHandler.renderMatchedAttributesNotSetView();
JS
                    );
                } elseif ($this->getMatcherAttributes()->isDestinationAmountGreater()) {
                    $matchedAttributes = json_encode($this->getMatchedAttributes(), JSON_FORCE_OBJECT);
                    $destinationAttributes = \Ess\M2ePro\Helper\Json::encode($this->getDestinationAttributes());

                    $this->js->add(
                        <<<JS
    ListingGridObj.variationProductManageHandler.matchingType = ListingGridObj
        .variationProductManageHandler.MATCHING_TYPE_VIRTUAL_MAGENTO;

    ListingGridObj.variationProductManageHandler.matchedAttributes = {$matchedAttributes};
    ListingGridObj.variationProductManageHandler.destinationAttributes = {$destinationAttributes};

    ListingGridObj.variationProductManageHandler.renderMatchedAttributesNotSetView();
JS
                    );
                }
            } else {
                $html .= <<<HTML
    <table class="data-grid data-grid-not-hovered" cellspacing="0" cellpadding="0">
        <tr>
            <td class="label" style="width: 25%;
                                     font-weight: bold;
                                     border-bottom: 1px solid #D6D6D6 !important;
                                     border-right: 1px solid #D6D6D6 !important;">
                {$this->__('Magento Attributes')}
            </td>
            <td class="value" style="font-weight: bold; border-bottom: 1px solid #D6D6D6 !important;">
                {$this->__('Walmart Attributes')}
            </td>
        </tr>
HTML;

                $attrId = 0;
                $virtualAttributes = $this->getVirtualAttributes();
                $virtualProductAttributes = $this->getVirtualProductAttributes();
                $virtualChannelAttributes = $this->getVirtualChannelAttributes();

                foreach ($this->getMatchedAttributes() as $magentoAttr => $walmartAttr) {
                    $isVirtual = ($magentoAttr == $walmartAttr)
                        && in_array($magentoAttr, array_keys($virtualAttributes));

                    $html .= <<<HTML
        <tr><td class="label" style="border-right: 1px solid #D6D6D6 !important;">
HTML;

                    if (!$isVirtual) {
                        $html .= <<<HTML
            <label for="variation_manager_attributes_walmart_{$attrId}">{$magentoAttr}</label>
HTML;
                    } else {
                        $style = in_array($magentoAttr, array_keys($virtualProductAttributes))
                            ? 'border-bottom: 2px dotted grey;' : '';

                        $html .= <<<HTML
            <label for="variation_manager_attributes_walmart_{$attrId}">
                <span style="{$style}">
                    {$magentoAttr} ({$virtualAttributes[$magentoAttr]})
                </span>
            </label>
HTML;
                    }

                    $html .= <<<HTML
        </td><td class="value">
        <input type="hidden"
               value="{$this->dataHelper->escapeHtml($magentoAttr)}"
               name="variation_attributes[magento_attributes][]">

HTML;

                    if (!$isVirtual) {
                        $style = $this->hasMatchedAttributes() ? '' : 'style="color: red;"';
                        $text = $this->hasMatchedAttributes() ? $walmartAttr : __('Not Set');
                        $html .= <<<HTML
        <span class="variation_manager_attributes_walmart_value" {$style}>{$text}</span>
HTML;

                        $options = [];

                        $destinationAttributes = $this->getDestinationAttributes();

                        if (empty($walmartAttr)) {
                            $options[] = ['value' => '', 'label' => ''];
                        }

                        foreach ($destinationAttributes as $attr) {
                            if (in_array($attr, array_keys($virtualAttributes))) {
                                continue;
                            }

                            $options[] = [
                                'value' => $attr,
                                'label' => $attr,
                            ];
                        }

                        $attributesSelect = $this->elementFactory->create('select', [
                            'data' => [
                                'html_id' => 'variation_manager_attributes_walmart_' . $attrId,
                                'name' => 'variation_attributes[walmart_attributes][]',
                                'style' => 'display: none;',
                                'class' => 'variation_manager_attributes_walmart_select',
                                'value' => $walmartAttr,
                                'values' => $options,
                                'required' => true,
                            ],
                        ]);
                        $attributesSelect->setForm($form);

                        $html .= $attributesSelect->toHtml();
                    } else {
                        $style = in_array($walmartAttr, array_keys($virtualChannelAttributes))
                            ? 'border-bottom: 2px dotted grey;' : '';

                        $html .= <<<HTML
        <span style="{$style}">{$walmartAttr} ({$virtualAttributes[$walmartAttr]})</span>
        <input type="hidden" name="variation_attributes[walmart_attributes][]" value="{$walmartAttr}" />
HTML;
                    }

                    $html .= <<<HTML
        <label class="mage-error" id="variation_manager_attributes_error_{$attrId}" style="display: none;"></label>
        </td></tr>
HTML;
                    $attrId++;
                }

                $style = $this->isChangeMatchedAttributesAllowed() ? '' : 'display: none;';

                $changeButton = $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Magento\Button::class)
                                     ->setData([
                                         'class' => 'action primary',
                                         'label' => $this->hasMatchedAttributes() ? __('Change') :
                                             __('Set Attributes'),
                                         'onclick' => 'ListingGridObj.variationProductManageHandler.changeMatchedAttributes(this)',
                                     ])->toHtml();

                $confirmButton = $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Magento\Button::class)
                                      ->setData([
                                          'class' => 'action primary',
                                          'style' => 'display: none;',
                                          'label' => __('Confirm'),
                                          'onclick' => 'ListingGridObj.variationProductManageHandler.setMatchedAttributes()',
                                      ])->toHtml();

                $html .= <<<HTML
        <tr>
            <td class="label" colspan="2" style="border: none; text-align: right; {$style}">
                $changeButton
                <a href="javascript:void(0);"
                   onclick="ListingGridObj.variationProductManageHandler.cancelMatchedAttributes(this);"
                   style="display: none;">{$this->__('Cancel')}</a>&nbsp;&nbsp;
                {$confirmButton}
            </td>
        </tr>
    </table>
HTML;
            }

            $html .= '</form>';

            $fieldset->addField(
                'attributes_container',
                self::CUSTOM_CONTAINER,
                [
                    'text' => $html,
                    'css_class' => 'm2epro-custom-container-full-width',
                ]
            );

            $fieldset = $form->addFieldset(
                'swatch_images__fieldset',
                [
                    'legend' => __('Swatch Variant Attribute'),
                    'collapsable' => true,
                    'direction_class' => 'to-right',
                    'tooltip' => __(
                        'Select Swatch Variant Attribute by which the Swatch Images will be shown on your Walmart
                     Item page.<br><br>
                     <strong>Note:</strong> In Description Policy, you may select Magento source where the Images for
                     Walmart Item Variations will be taken from.'
                    ),
                ]
            );

            $button = $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Magento\Button::class)->addData([
                'label' => __('Change'),
                'onclick' => 'ListingGridObj.variationProductManageHandler.setSwatchImagesAttribute()',
                'class' => 'action-primary',
                'style' => 'margin-left: 70px;',
            ]);

            $swatchImagesValues = [];
            foreach ($possibleAttributes as $attr) {
                $swatchImagesValues[$attr] = $attr;
            }
            $fieldset->addField(
                'swatch_images_attributes',
                'select',
                [
                    'label' => __('Swatch Variant Attribute'),
                    'name' => 'swatch_images',
                    'values' => $swatchImagesValues,
                    'value' => $this->getSwatchImagesAttribute(),
                    'after_element_html' => $button->toHtml(),
                ]
            );
        }

        $this->css->add(
            <<<CSS
.data-grid.data-grid-not-hovered td.label {
    border-left: none;
}

.data-grid.data-grid-not-hovered td.value {
    border-right: none;
}
CSS
        );

        $form->setUseContainer(false);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    //########################################

    /**
     * @param array $message
     * @param string $type
     */
    public function addMessage($message, $type = self::MESSAGE_TYPE_ERROR)
    {
        $this->messages[] = [
            'type' => $type,
            'text' => $message,
        ];
    }

    /**
     * @param array $messages
     */
    public function setMessages($messages)
    {
        $this->messages = $messages;
    }

    /**
     * @return array
     */
    public function getMessages()
    {
        return $this->messages;
    }

    public function getMessagesType()
    {
        $type = self::MESSAGE_TYPE_WARNING;
        foreach ($this->messages as $message) {
            if ($message['type'] === self::MESSAGE_TYPE_ERROR) {
                $type = $message['type'];
                break;
            }
        }

        return $type;
    }

    // ---------------------------------------

    /**
     * @param \Ess\M2ePro\Model\Listing\Product $listingProduct
     *
     * @return $this
     */
    public function setListingProduct(\Ess\M2ePro\Model\Listing\Product $listingProduct)
    {
        $this->listingProduct = $listingProduct;

        return $this;
    }

    /**
     * @return \Ess\M2ePro\Model\Listing\Product
     */
    public function getListingProduct()
    {
        return $this->listingProduct;
    }

    // ---------------------------------------

    /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
    protected $listingProductTypeModel;

    /**
     * @return \Ess\M2ePro\Model\Walmart\Listing\Product\Variation\Manager\Type\Relation\ParentRelation
     */
    public function getListingProductTypeModel()
    {
        if ($this->listingProductTypeModel === null) {
            /** @var \Ess\M2ePro\Model\Walmart\Listing\Product $walmartListingProduct */
            $walmartListingProduct = $this->getListingProduct()->getChildObject();
            /** @var ParentRelation $typeModel */
            $this->listingProductTypeModel = $walmartListingProduct->getVariationManager()->getTypeModel();
        }

        return $this->listingProductTypeModel;
    }

    // ---------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Walmart\Listing\Product\Variation\Matcher\Attribute
     */
    public function getMatcherAttributes()
    {
        if (empty($this->matcherAttributes)) {
            $this->matcherAttributes = $this->modelFactory->getObject(
                'Walmart_Listing_Product_Variation_Matcher_Attribute'
            );
            $this->matcherAttributes->setMagentoProduct($this->getListingProduct()->getMagentoProduct());
            $this->matcherAttributes->setDestinationAttributes($this->getDestinationAttributes());
        }

        return $this->matcherAttributes;
    }

    // ---------------------------------------

    public function getWarnings()
    {
        /** @var \Magento\Framework\View\Element\Messages $messages */
        $messages = $this->getLayout()->createBlock(\Magento\Framework\View\Element\Messages::class);

        foreach ($this->getMessages() as $message) {
            $addMethod = 'add' . ucfirst($message['type']);
            $messages->$addMethod($message['text']);
        }

        return $messages->toHtml();
    }

    public function calculateWarnings()
    {
        if (!$this->warningsCalculated) {
            $this->warningsCalculated = true;

            if (!$this->getListingProductTypeModel()->hasChannelAttributes()) {
                $this->addMessage(
                    __(
                        'Walmart Item Variations are not defined. To start configurations, click Set Attributes.'
                    ),
                    self::MESSAGE_TYPE_ERROR
                );
            } elseif (!$this->hasMatchedAttributes()) {
                $this->addMessage(
                    __(
                        'Item Variations cannot be added/updated on the Channel. The correspondence between Magento
                        Variational Attribute(s) and Walmart Variant Attribute(s) is not set.
                        Please complete the configurations.'
                    ),
                    self::MESSAGE_TYPE_ERROR
                );
            }
        }
    }

    // ---------------------------------------

    protected function _beforeToHtml()
    {
        $this->calculateWarnings();

        return parent::_beforeToHtml();
    }

    protected function _toHtml()
    {
        $this->css->add(
            <<<CSS
    #variation_manager_product_options_form select {
        min-width: 200px;
    }
CSS
        );

        return $this->getWarnings() . parent::_toHtml();
    }

    //########################################

    public function isInAction()
    {
        $processingLocks = $this->getListingProduct()->getProcessingLocks();

        return !empty($processingLocks);
    }

    // ---------------------------------------

    public function getProductAttributes()
    {
        return $this->getListingProductTypeModel()->getProductAttributes();
    }

    // ---------------------------------------

    public function getSwatchImagesAttribute()
    {
        return $this->getListingProduct()
                    ->getSetting('additional_data', 'variation_swatch_images_attribute');
    }

    // ---------------------------------------

    public function hasMatchedAttributes()
    {
        return $this->getListingProductTypeModel()->hasMatchedAttributes();
    }

    public function getMatchedAttributes()
    {
        if ($this->hasMatchedAttributes()) {
            return $this->getListingProductTypeModel()->getMatchedAttributes();
        }

        return $this->getMatcherAttributes()->getMatchedAttributes();
    }

    public function getDestinationAttributes()
    {
        return $this->getListingProductTypeModel()->getChannelAttributes();
    }

    // ---------------------------------------

    public function getVirtualAttributes()
    {
        $typeModel = $this->getListingProductTypeModel();

        if ($virtualProductAttributes = $typeModel->getVirtualProductAttributes()) {
            return $virtualProductAttributes;
        }

        if ($virtualChannelAttributes = $typeModel->getVirtualChannelAttributes()) {
            return $virtualChannelAttributes;
        }

        return [];
    }

    public function getVirtualProductAttributes()
    {
        $typeModel = $this->getListingProductTypeModel();

        if ($virtualProductAttributes = $typeModel->getVirtualProductAttributes()) {
            return $virtualProductAttributes;
        }

        return [];
    }

    public function getVirtualChannelAttributes()
    {
        $typeModel = $this->getListingProductTypeModel();

        if ($virtualChannelAttributes = $typeModel->getVirtualChannelAttributes()) {
            return $virtualChannelAttributes;
        }

        return [];
    }

    // ---------------------------------------

    public function isChangeMatchedAttributesAllowed()
    {
        if ($this->isInAction()) {
            return false;
        }
        if ($this->hasMatchedAttributes()) {
            $typeModel = $this->getListingProductTypeModel();

            $realMatchedAttributes = $typeModel->getRealMatchedAttributes();

            if (count($realMatchedAttributes) === 1) {
                return false;
            }
        }

        return true;
    }

    //########################################

    public function getChildListingProducts()
    {
        if ($this->childListingProducts !== null) {
            return $this->childListingProducts;
        }

        return $this->childListingProducts = $this->getListingProductTypeModel()->getChildListingsProducts();
    }

    public function getCurrentProductVariations()
    {
        if ($this->currentProductVariations !== null) {
            return $this->currentProductVariations;
        }

        $magentoProductVariations = $this->getListingProduct()
                                         ->getMagentoProduct()
                                         ->getVariationInstance()
                                         ->getVariationsTypeStandard();

        $productVariations = [];

        foreach ($magentoProductVariations['variations'] as $option) {
            $productOption = [];

            foreach ($option as $attribute) {
                $productOption[$attribute['attribute']] = $attribute['option'];
            }

            $productVariations[] = $productOption;
        }

        return $this->currentProductVariations = $productVariations;
    }

    public function getCurrentChannelVariations()
    {
        return $this->getListingProductTypeModel()->getChannelVariations();
    }

    // ---------------------------------------

    public function getWalmartVariationsSet()
    {
        $variations = $this->getCurrentChannelVariations();

        if (empty($variations)) {
            return false;
        }

        $attributesOptions = [];

        foreach ($variations as $variation) {
            foreach ($variation as $attr => $option) {
                if (!isset($attributesOptions[$attr])) {
                    $attributesOptions[$attr] = [];
                }
                if (!in_array($option, $attributesOptions[$attr])) {
                    $attributesOptions[$attr][] = $option;
                }
            }
        }

        ksort($attributesOptions);

        return $attributesOptions;
    }

    // ---------------------------------------

    public function getUsedChannelVariations()
    {
        return $this->getListingProductTypeModel()->getUsedChannelOptions();
    }

    public function getUsedProductVariations()
    {
        return $this->getListingProductTypeModel()->getUsedProductOptions();
    }

    // ---------------------------------------

    public function getUnusedProductVariations()
    {
        return $this->getListingProductTypeModel()->getUnusedProductOptions();
    }

    // ---------------------------------------

    public function hasUnusedProductVariation()
    {
        return (bool)$this->getUnusedProductVariations();
    }

    // ---------------------------------------

    public function hasChildWithEmptyProductOptions()
    {
        foreach ($this->getChildListingProducts() as $childListingProduct) {
            /** @var \Ess\M2ePro\Model\Listing\Product $childListingProduct */

            /** @var \Ess\M2ePro\Model\Walmart\Listing\Product\Variation\Manager\Type\Relation\Child $childTypeModel */
            $childTypeModel = $childListingProduct->getChildObject()->getVariationManager()->getTypeModel();

            if (!$childTypeModel->isVariationProductMatched()) {
                return true;
            }
        }

        return false;
    }

    //########################################
}
