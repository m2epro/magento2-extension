<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Product\Variation\Manage\Tabs\Settings;

use Ess\M2ePro\Model\Amazon\Listing\Product\Variation\Manager\Type\Relation\ChildRelation;
use Ess\M2ePro\Model\Amazon\Listing\Product\Variation\Manager\Type\Relation\ParentRelation;

class Form extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm
{
    const MESSAGE_TYPE_ERROR = 'error';
    const MESSAGE_TYPE_WARNING = 'warning';

    protected $warningsCalculated = false;

    protected $channelThemes = null;
    protected $childListingProducts = null;
    protected $currentProductVariations = null;

    /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
    protected $listingProduct;

    /** @var \Ess\M2ePro\Model\Amazon\Listing\Product\Variation\Matcher\Attribute $matcherAttribute */
    protected $matcherAttributes;

    protected $messages = array();

    //########################################

    protected function _prepareForm()
    {
        $form = $this->_formFactory->create(
            [
                'data' => [
                    'id' => 'variation_settings_form',
                    'method' => 'post',
                    'action' => 'javascript:void(0)',
                ]
            ]
        );

        $magentoProductVariations = $this->getListingProduct()
            ->getMagentoProduct()
            ->getVariationInstance()
            ->getVariationsTypeStandard();

        $fieldset = $form->addFieldset(
            'general_id_fieldset',
            [
                'legend' => $this->__('Parent Product'),
                'collapsable' => true,
                'direction_class' => 'to-right',
                'tooltip' => $this->__(
                    'The fact that you are the Creator of this Amazon Parent Product influences on the work with
                     its Child Products. <br/><br/>

                    In case you create New Amazon Parent-Child Product using M2E Pro you will be considered as a
                    Creator of this Product. <br/><br/>

                    In case ASIN/ISBN was found using M2E Pro Search Tool, you can specify that you created this
                    Amazon Parent Product earlier by clicking <i class="underline">"I am the Creator"</i> Button.
                    You should enter SKU of the Amazon Parent Product from your Amazon Inventory at the next step.
                    <br/><br/>

                    What you should know if you are the Creator of the Parent Product:
                    <ul class="list">
                        <li>Only you have an ability to add new Options of Attributes (for example,
                        Option Red of Attribute Color) and Child Products for them;</li>
                        <li>New Amazon Child Product will be created using a Description Policy assigned to the
                        Amazon Parent Product.</li>
                    </ul>

                    If you are not the Creator of this Product, you can only sell existing Child Products of the
                    Parent Product.'
                )
            ]
        );

        $html = '';
        if ($this->hasGeneralId() && !$this->isGeneralIdOwner()) {

            $html = <<<HTML
{$this->__('You are not the Creator of Amazon Parent Product: %asin%. It is not allowed to you to create
           New Amazon Child Products.', $this->getGeneralIdLink())}
HTML;
            if ($this->showGeneralIdActions()) {
                $generalIdOwnerYes = \Ess\M2ePro\Model\Amazon\Listing\Product::IS_GENERAL_ID_OWNER_YES;

                $html .= <<<HTML
<br/>
<button class="action primary"
        style="margin-top: 10px;"
        onclick="ListingGridHandlerObj.variationProductManageHandler.setGeneralIdOwner({$generalIdOwnerYes})">
        {$this->__('I am the Creator')}</button>
<div class="m2epro-field-tooltip m2epro-field-tooltip-right admin__field-tooltip" style="padding-top: 10px;">
    <a class="admin__field-tooltip-action" href="javascript://"></a>
    <div class="admin__field-tooltip-content">
        {$this->__(
        'In case your Amazon Parent Product is in your Amazon Inventory and has SKU there, you can specify
         for M2E Pro that you created this Product earlier.<br/>
         To do that just enter Amazon Parent Product SKU.')}
    </div>
</div>
HTML;
            }

        } elseif ($this->hasGeneralId() && $this->isGeneralIdOwner()) {

            $html .= <<<HTML
        <p>{$this->__('You are the Creator of Amazon Parent Product %asin%. It is allowed to you to create
                       New Amazon Child Products. <br/><br/><b>Please Note:</b> New Amazon Child Products will be
                       created based on Description Policy %template_link%.',
                array('asin' => $this->getGeneralIdLink(), 'template_link' => $this->getDescriptionTemplateLink()))}
HTML;
            if ($this->showGeneralIdActions()) {
                $generalIdOwnerNo = \Ess\M2ePro\Model\Amazon\Listing\Product::IS_GENERAL_ID_OWNER_NO;
                $html .= <<<HTML
        <a href="javascript:void(0);"
           onclick="ListingGridHandlerObj.variationProductManageHandler.setGeneralIdOwner({$generalIdOwnerNo})">
           {$this->__('I am not Creator')}</a>
HTML;
            }

            $html .= '</p>';
        } elseif (!$this->hasGeneralId() && $this->isGeneralIdOwner()) {

            $html .= <<<HTML
        <p>{$this->__('New Amazon Parent Product will be created based on %desctemplate% Description Policy.<br/>
                      You will be able to create New Amazon Child Products.', $this->getDescriptionTemplateLink())}</p>
HTML;
        }

        $fieldset->addField(
            'general_id_container',
            self::CUSTOM_CONTAINER,
            [
                'text' => $html,
                'css_class' => 'm2epro-custom-container-full-width'
            ]
        );

        if ($this->isGeneralIdOwner()) {
            $channelThemeNote = $this->getChannelThemeNote();

            $fieldset = $form->addFieldset(
                'theme_fieldset',
                [
                    'legend' => $this->__('Variation Theme'),
                    'collapsable' => true,
                    'tooltip' => $this->__(
                        'Variation Theme is a combination of Attributes based on which the Parent-Child relation
                         of Amazon Products is realized.<br/><br/>
                         Variation Theme is required to create New Amazon Relation Products (both: Parent and Child).
                         The list of available Variation Themes depends on the Category chosen in
                         the Description Policy. <br/>
                         Variation Themes are not available for the Categories which do not support
                         Amazon Parent/Child Products.<br/>
                         Variation Theme cannot be changed Parent Product receives an ASIN/ISBN. <br/><br/>
                         <b>Note:</b> The list of Variation Themes is provided by Amazon and cannot be changed or added.
                         '
                    )
                ]
            );

            $html = '';

            if ($this->hasGeneralId()) {

                if (!$this->hasChannelTheme()) {
                    $html = <<<HTML
<span style="color: #ea7601; ">{$this->__('Not Available')}</span>
HTML;
                } else {
                    $html .= <<<HTML
<span style="color: grey;">{$this->getChannelThemeAttrString()}</span>&nbsp;&nbsp;&nbsp;
HTML;

                    if (!empty($channelThemeNote)) {
                        $html .= <<<HTML
<div class="m2epro-field-tooltip m2epro-field-tooltip-right admin__field-tooltip" style="margin-top: -1px;">
    <a class="admin__field-tooltip-action" href="javascript://"></a>
    <div class="admin__field-tooltip-content">
        {$channelThemeNote}
    </div>
</div>
HTML;
                    }
                }

                $fieldset->addField(
                    'theme_container',
                    self::CUSTOM_CONTAINER,
                    [
                        'label' => $this->__('Variation Theme'),
                        'text' => $html
                    ]
                );
            } else {

                $style = !$this->hasChannelTheme() ? 'color: red;' : 'color: initial;';
                $text = $this->hasChannelTheme() ? $this->getChannelThemeAttrString() : $this->__('Not Set');

                $html = <<<HTML
<span id="variation_manager_theme_attributes" style="line-height: 33px; {$style}">{$text}</span>&nbsp;
HTML;

                $channelThemes = $this->getChannelThemes();
                $channelThemesOptions = [];
                foreach($channelThemes as $key => $theme) {
                    $channelThemesOptions[] = [
                        'value' => $key,
                        'label' => implode(', ', $theme['attributes'])
                    ];
                }

                $themeSelect = $this->elementFactory->create('select', [
                    'data' => [
                        'html_id' => 'variation_manager_theme',
                        'name' => 'variation_manager_theme',
                        'style' => 'display: none; width: 50%;',
                        'no_span' => true,
                        'value' => $this->getChannelTheme(),
                        'values' => $channelThemesOptions
                    ]
                ]);
                $themeSelect->setForm($form);

                $html .= $themeSelect->toHtml();

                if (!empty($channelThemeNote)) {
                    $html .= <<<HTML
<div id="channel_variation_theme_note"
     class="m2epro-field-tooltip m2epro-field-tooltip-right admin__field-tooltip">
    <a class="admin__field-tooltip-action" href="javascript://"></a>
    <div class="admin__field-tooltip-content">
        {$channelThemeNote}
    </div>
</div>
HTML;
                }

                if (!$this->isInAction()) {
                    $html .= $this->createBlock('Magento\Button')->setData([
                        'class' => 'action primary',
                        'style' => '    margin-left: 60px;',
                        'label' => $this->hasChannelTheme() ? $this->__('Change') : $this->__('Set Theme'),
                        'onclick' => 'ListingGridHandlerObj.variationProductManageHandler.changeVariationTheme(this)'
                    ])->toHtml();

                    $confirmBtn = $this->createBlock('Magento\Button')->setData([
                        'class' => 'action primary',
                        'label' => $this->__('Confirm'),
                        'onclick' => 'ListingGridHandlerObj.variationProductManageHandler.setVariationTheme()'
                    ])->toHtml();

                    $html .= <<<HTML
<span id="edit_variation_btns" style="display: none;">
    <div class="m2epro-field-tooltip admin__field-tooltip">
        <a class="admin__field-tooltip-action" href="javascript://"></a>
        <div class="admin__field-tooltip-content">
            {$this->__('Some Variation Themes cannot be used because number of Attributes in Variation Theme is
                       not equal to number of Magento Product Attributes.')}
        </div>
    </div>
    &nbsp;&nbsp;
    <a href="javascript:void(0);"
       style="margin-left: 40px;"
       onclick="ListingGridHandlerObj.variationProductManageHandler.cancelVariationTheme(this);">
       {$this->__('Cancel')}</a>
    &nbsp;&nbsp;
    {$confirmBtn}
</span>
HTML;
                }

                $fieldset->addField(
                    'theme_container',
                    self::CUSTOM_CONTAINER,
                    [
                        'label' => $this->__('Variation Theme'),
                        'style' => 'padding-top: 0;',
                        'required' => true,
                        'text' => $html
                    ]
                );

            }
        }

        if ($this->hasGeneralId() ||
            (!$this->hasGeneralId() && $this->isGeneralIdOwner() && $this->hasChannelTheme())) {

            $this->js->add(
<<<JS
    ListingGridHandlerObj.variationProductManageHandler.virtualAmazonMatchedAttributes = false;
    ListingGridHandlerObj.variationProductManageHandler.amazonVariationSet = false;
JS
            );

            $this->jsTranslator->addTranslations([
                'help_icon_magento_greater_left' =>
                    $this->__('This Amazon Attribute and its Value are virtualized based on the selected Magento ' .
                        'Variational Attribute and its Value as physically this Amazon Attribute does not exist.'),
                'help_icon_magento_greater_right' =>
                    $this->__('Select a particular Option of the Attribute to fix it for virtualized Amazon ' .
                        'Attribute. Please, be thoughtful as only those Variations of Magento Product which ' .
                        'contains the selected Option can be sold on Amazon.'),

                'help_icon_amazon_greater_left' =>
                    $this->__('This Magento Attribute and its Value are virtualized based on the selected Amazon ' .
                        'Variational Attribute and its Value as physically this Magento Attribute does not exist.'),
                'help_icon_amazon_greater_right' =>
                    $this->__('Select a particular Option of the Attribute to fix it for virtualized Magento ' .
                        'Attribute. Please, be thoughtful as your offer will be available only for those Buyers who ' .
                        'selected the same Option.'),

                'duplicate_magento_attribute_error' =>
                    $this->__('The Magento Attributes which you selected in your settings have the same Labels. Such ' .
                        'combination is invalid. Please, add the valid combination of Attributes.'),
                'duplicate_amazon_attribute_error' =>
                    $this->__('The Amazon Attributes which you selected in your settings have the same Labels. Such ' .
                        'combination is invalid. Please, add the valid combination of Attributes.'),

                'change_option' => $this->__('Change option')
            ]);

            $fieldset = $form->addFieldset(
                'attributes_fieldset',
                [
                    'legend' => $this->__('Variation Attributes'),
                    'collapsable' => true,
                    'tooltip' => $this->__('
                        To sell Magento Variational Product as Amazon Parent-Child Product it is necessary
                        to set correspondence of Magento and Amazon Variation Attributes.
                        Prerequisite to set correspondence is equal number of Magento Product Attributes and
                        Amazon Parent-Child Product Attributes. <br/><br/>

                        You can always change this correspondence manually by clicking
                        <i class="underline">"Change"</i> Button. <br/><br/>

                        <b>Note:</b> In case correspondence between Amazon and Magento Variation Attributes
                        is not set adding and selling of Amazon Child Products is impossible.'
                    )
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
                    {$this->__('Amazon Attributes')}
                </td>
            </tr>
        </thead>
        <tbody></tbody>
        <tfoot></tfoot>
    </table>
HTML;

                if ($this->getMatcherAttributes()->isSourceAmountGreater()) {

                    $matchedAttriutes = json_encode($this->getMatchedAttributes(), JSON_FORCE_OBJECT);
                    $productAttributes = $this->getHelper('Data')->jsonEncode($this->getProductAttributes());
                    $destinationAttributes = $this->getHelper('Data')->jsonEncode($this->getDestinationAttributes());
                    $magentoVariationSet = $this->getHelper('Data')->jsonEncode($magentoProductVariations['set']);

                    $this->js->add(
<<<JS
    ListingGridHandlerObj.variationProductManageHandler.matchingType = ListingGridHandlerObj
        .variationProductManageHandler.MATCHING_TYPE_VIRTUAL_AMAZON;
    ListingGridHandlerObj.variationProductManageHandler.matchedAttributes = {$matchedAttriutes};
    ListingGridHandlerObj.variationProductManageHandler.productAttributes = {$productAttributes};
    ListingGridHandlerObj.variationProductManageHandler.destinationAttributes = {$destinationAttributes};
    ListingGridHandlerObj.variationProductManageHandler.magentoVariationSet = {$magentoVariationSet};

    ListingGridHandlerObj.variationProductManageHandler.renderMatchedAttributesNotSetView();
JS
                    );

                } elseif ($this->getMatcherAttributes()->isDestinationAmountGreater()) {

                    $matchedAttriutes = json_encode($this->getMatchedAttributes(), JSON_FORCE_OBJECT);
                    $destinationAttributes = $this->getHelper('Data')->jsonEncode($this->getDestinationAttributes());
                    $amazonVariationSet = $this->getHelper('Data')->jsonEncode($this->getAmazonVariationsSet());

                    $this->js->add(
<<<JS
    ListingGridHandlerObj.variationProductManageHandler.matchingType = ListingGridHandlerObj
        .variationProductManageHandler.MATCHING_TYPE_VIRTUAL_MAGENTO;

    ListingGridHandlerObj.variationProductManageHandler.matchedAttributes = {$matchedAttriutes};
    ListingGridHandlerObj.variationProductManageHandler.destinationAttributes = {$destinationAttributes};
    ListingGridHandlerObj.variationProductManageHandler.amazonVariationSet = {$amazonVariationSet};

    ListingGridHandlerObj.variationProductManageHandler.renderMatchedAttributesNotSetView();
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
                {$this->__('Amazon Attributes')}
            </td>
        </tr>
HTML;

                $attrId = 0;
                $virtualAttributes = $this->getVirtualAttributes();
                $virtualProductAttributes = $this->getVirtualProductAttributes();
                $virtualChannelAttributes = $this->getVirtualChannelAttributes();

                foreach ($this->getMatchedAttributes() as $magentoAttr => $amazonAttr) {

                    $isVirtual = ($magentoAttr == $amazonAttr)
                        && in_array($magentoAttr, array_keys($virtualAttributes));

                    $html .= <<<HTML
        <tr><td class="label" style="border-right: 1px solid #D6D6D6 !important;">
HTML;

                    if (!$isVirtual) {
                        $html .= <<<HTML
            <label for="variation_manager_attributes_amazon_{$attrId}">{$magentoAttr}</label>
HTML;
                    } else {
                        $style = in_array($magentoAttr, array_keys($virtualProductAttributes))
                            ? 'border-bottom: 2px dotted grey;' : '';

                        $html .= <<<HTML
            <label for="variation_manager_attributes_amazon_{$attrId}">
                <span style="{$style}">
                    {$magentoAttr} ({$virtualAttributes[$magentoAttr]})
                </span>
            </label>
HTML;
                    }

                    $html .= <<<HTML
        </td><td class="value">
        <input type="hidden"
               value="{$this->getHelper('Data')->escapeHtml($magentoAttr)}"
               name="variation_attributes[magento_attributes][]">

HTML;

                    if (!$isVirtual) {
                        $style = $this->hasMatchedAttributes() ? '' : 'style="color: red;"';
                        $text = $this->hasMatchedAttributes() ? $amazonAttr : $this->__('Not Set');
                        $html .= <<<HTML
        <span class="variation_manager_attributes_amazon_value" {$style}>{$text}</span>
HTML;

                        $options = [];

                        $destinationAttributes = $this->getDestinationAttributes();

                        if (empty($amazonAttr)) {
                            $options[] = ['value' => '', 'label' => ''];
                        }

                        foreach ($destinationAttributes as $attr) {
                            if (in_array($attr, array_keys($virtualAttributes))) {
                                continue;
                            }

                            $options[] = [
                                'value' => $attr,
                                'label' => $attr
                            ];
                        }

                        $attributesSelect = $this->elementFactory->create('select', [
                            'data' => [
                                'html_id' => 'variation_manager_attributes_amazon_'.$attrId,
                                'name' => 'variation_attributes[amazon_attributes][]',
                                'style' => 'display: none;',
                                'class' =>'variation_manager_attributes_amazon_select',
                                'value' => $amazonAttr,
                                'values' => $options,
                                'required' => true
                            ]
                        ]);
                        $attributesSelect->setForm($form);

                        $html .= $attributesSelect->toHtml();
                    } else {
                        $style = in_array($amazonAttr, array_keys($virtualChannelAttributes))
                            ? 'border-bottom: 2px dotted grey;' : '';

                        $html .= <<<HTML
        <span style="{$style}">{$amazonAttr} ({$virtualAttributes[$amazonAttr]})</span>
        <input type="hidden" name="variation_attributes[amazon_attributes][]" value="{$amazonAttr}" />
HTML;

                    }

                    $html .= <<<HTML
        <label class="mage-error" id="variation_manager_attributes_error_{$attrId}" style="display: none;"></label>
        </td></tr>
HTML;
                    $attrId++;

                }

                $style = $this->isChangeMatchedAttributesAllowed() ? '' : 'display: none;';

                $changeButton = $this->createBlock('Magento\Button')->setData([
                    'class' => 'action primary',
                    'label' => $this->hasMatchedAttributes() ? $this->__('Change') : $this->__('Set Attributes'),
                    'onclick' => 'ListingGridHandlerObj.variationProductManageHandler.changeMatchedAttributes(this)'
                ])->toHtml();

                $confirmButton = $this->createBlock('Magento\Button')->setData([
                    'class' => 'action primary',
                    'style' => 'display: none;',
                    'label' => $this->__('Confirm'),
                    'onclick' => 'ListingGridHandlerObj.variationProductManageHandler.setMatchedAttributes()'
                ])->toHtml();

                $html .= <<<HTML
        <tr>
            <td class="label" colspan="2" style="border: none; text-align: right; {$style}">
                $changeButton
                <a href="javascript:void(0);"
                   onclick="ListingGridHandlerObj.variationProductManageHandler.cancelMatchedAttributes(this);"
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
                    'css_class' => 'm2epro-custom-container-full-width'
                ]
            );

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
        }

        if ($this->hasGeneralId() && $this->hasMatchedAttributes()) {
            $fieldset = $form->addFieldset(
                'child_products_fieldset',
                [
                    'legend' => $this->__('Child Products'),
                    'collapsable' => true,
                    'tooltip' => $this->__(
                        'In case of successful Setting of correspondence between Variation Attributes of
                        Magento Product and Variation Attributes of Amazon Product you can work
                        with Amazon Child Products. <br/><br/>
                        To add Child Product to your Amazon Inventory means to set correspondence
                        between available Magento Product Variation and Amazon Parent Product Variation.
                        To do that it is necessary to click <i class="underline">"Add New Child Product"</i>
                        Button on the Child Products Tab. <br/><br/>
                        In case you are the Creator of this Product, you can create New Amazon Child Products
                        for unused Magento Product Variations.',
                        implode(', ', $this->getProductAttributes()), implode(', ', $this->getDestinationAttributes())
                    )
                ]
            );

            $html = '';

            if (!$this->hasUnusedProductVariation()) {
                $html = $this->__('All the possible Variations of Magento Product are being sold.');
            } elseif ($this->hasChildWithEmptyProductOptions()) {

                $html = $this->__(
                    'There is Amazon Child Product, you are selling,
                    for which Magento Variation was not set for some reasons. <br/><br/>
                    Adding or changing other Amazon Child Products is suspended.  <br/><br/>
                    To continue full work with all your Amazon Child Products you should specify
                    Magento Product Variation for that Amazon Child Product on Child Products Tab.'
                );

            } elseif (!$this->isGeneralIdOwner() && !$this->hasUnusedChannelVariations()) {
                $html = $this->__('All the possible Variations of Amazon Product are being sold.');
            } elseif (!$this->isGeneralIdOwner() && $this->hasChildWithEmptyChannelOptions()) {
                $html = $this->__(
                    'There is Amazon Child Product, you are selling,
                    for which Amazon Variation was not set for some reasons. <br/><br/>
                    Adding or changing other Amazon Child Products is suspended. <br/><br/>
                    To continue full work with all your Amazon Child Products you should specify
                    Amazon Product Variation for that Amazon Child Product on Child Products Tab.'
                );
            } else {
                $html = $this->__(
                    'To sell existing Amazon Child Products or to add New Child Products (if you are the Creator) click
                    <a href="%url%" onclick="%onclick%">"Add New Child Product"</a> Button on Child Products Tab.',
                    [
                        'url' => 'javascript:void(0);',
                        'onclick' => 'ListingGridHandlerObj.variationProductManageHandler.openVariationsTab(' .
                            var_export(!$this->hasUnusedChannelVariations(), true) .
                            ', ' . $this->getListingProduct()->getId() .
                        ');'
                    ]
                );
            }

            $fieldset->addField(
                'child_products_container',
                self::CUSTOM_CONTAINER,
                [
                    'text' => $html,
                    'css_class' => 'm2epro-custom-container-full-width'
                ]
            );
        }

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
        $this->messages[] = array(
            'type' => $type,
            'text' => $message
        );
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
            if ($message['type'] === self::MESSAGE_TYPE_ERROR)     {
                $type = $message['type'];
                break;
            }
        }

        return $type;
    }

    // ---------------------------------------

    /**
     * @param \Ess\M2ePro\Model\Listing\Product $listingProduct
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
     * @return \Ess\M2ePro\Model\Amazon\Listing\Product\Variation\Manager\Type\Relation\ParentRelation
     */
    public function getListingProductTypeModel()
    {
        if (is_null($this->listingProductTypeModel)) {
            /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
            $amazonListingProduct = $this->getListingProduct()->getChildObject();
            /** @var ParentRelation $typeModel */
            $this->listingProductTypeModel = $amazonListingProduct->getVariationManager()->getTypeModel();
        }

        return $this->listingProductTypeModel;
    }

    // ---------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Amazon\Listing\Product\Variation\Matcher\Attribute
     */
    public function getMatcherAttributes()
    {
        if (empty($this->matcherAttributes)) {
            $this->matcherAttributes = $this->modelFactory->getObject(
                'Amazon\Listing\Product\Variation\Matcher\Attribute'
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
        $messages = $this->getLayout()->createBlock('\Magento\Framework\View\Element\Messages');

        foreach ($this->getMessages() as $message) {
            $addMethod = 'add'.ucfirst($message['type']);
            $messages->$addMethod($message['text']);

        }
        return $messages->toHtml();
    }

    public function calculateWarnings()
    {
        if (!$this->warningsCalculated) {

            $this->warningsCalculated = true;

            if (!$this->hasGeneralId() && $this->isGeneralIdOwner()) {
                if (!$this->hasChannelTheme() || !$this->hasMatchedAttributes()) {
                    $this->addMessage(
                        $this->__(
                            'Creation of New Parent-Child Product is impossible because Variation Theme
                            or correspondence between Magento Product Attributes and Amazon Product Attributes
                            was not set. Please, specify a Variation Theme or correspondence between
                            Variation Attributes.'
                        ),
                        self::MESSAGE_TYPE_ERROR
                    );
                }
            } elseif ($this->hasGeneralId()) {
                if (!$this->hasMatchedAttributes()) {
                    $this->addMessage(
                        $this->__(
                            'Selling of existing Child Products on Amazon is impossible because correspondence
                             between Magento Product Attributes and Amazon Product Attributes was not set.
                             Please, specify correspondence between Variation Attributes.'
                        ),
                        self::MESSAGE_TYPE_ERROR
                    );
                }
                if ($this->isGeneralIdOwner() && !$this->hasChannelTheme()) {
                    $this->addMessage(
                        $this->__(
                            'Creation of New Amazon Child Products feature is temporary unavailable because
                             Variation Theme was not set. Please, specify Variation Theme.'
                        ),
                        self::MESSAGE_TYPE_WARNING
                    );
                }
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

    public function showGeneralIdActions()
    {
        return !$this->getListingProduct()->getMagentoProduct()->isBundleType() &&
               !$this->getListingProduct()->getMagentoProduct()->isSimpleTypeWithCustomOptions() &&
               !$this->getListingProduct()->getMagentoProduct()->isDownloadableTypeWithSeparatedLinks();
    }

    // ---------------------------------------

    public function hasGeneralId()
    {
        return $this->getListingProduct()->getChildObject()->getGeneralId() !== NULL;
    }

    public function getGeneralId()
    {
        return $this->getListingProduct()->getChildObject()->getGeneralId();
    }

    public function getGeneralIdLink()
    {
        $url = $this->getHelper('Component\Amazon')->getItemUrl(
            $this->getGeneralId(),
            $this->getListingProduct()->getListing()->getMarketplaceId()
        );

        return <<<HTML
<a href="{$url}" target="_blank" title="{$this->getGeneralId()}" >{$this->getGeneralId()}</a>
HTML;
    }

    public function isGeneralIdOwner()
    {
        return $this->getListingProduct()->getChildObject()->isGeneralIdOwner();
    }

    // ---------------------------------------

    public function getDescriptionTemplateLink()
    {
        $url = $this->getUrl('*/amazon_template_description/edit', array(
            'id' => $this->getListingProduct()->getChildObject()->getTemplateDescriptionId()
        ));

        $templateTitle = $this->getListingProduct()->getChildObject()->getDescriptionTemplate()->getTitle();

        return <<<HTML
<a href="{$url}" target="_blank" title="{$templateTitle}" >{$templateTitle}</a>
HTML;
    }

    // ---------------------------------------

    public function hasChannelTheme()
    {
        return $this->getListingProductTypeModel()->hasChannelTheme();
    }

    public function getChannelTheme()
    {
        return $this->getListingProductTypeModel()->getChannelTheme();
    }

    public function getChannelThemes()
    {
        if (!is_null($this->channelThemes)) {
            return $this->channelThemes;
        }

        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
        $amazonListingProduct = $this->getListingProduct()->getChildObject();
        $descriptionTemplate = $amazonListingProduct->getAmazonDescriptionTemplate();

        if (!$descriptionTemplate) {
            return array();
        }

        $marketPlaceId = $this->getListingProduct()->getListing()->getMarketplaceId();

        $detailsModel = $this->modelFactory->getObject('Amazon\Marketplace\Details');
        $detailsModel->setMarketplaceId($marketPlaceId);

        $channelThemes = $detailsModel->getVariationThemes($descriptionTemplate->getProductDataNick());

        $variationHelper = $this->getHelper('Component\Amazon\Variation');
        $themesUsageData = $variationHelper->getThemesUsageData();
        $usedThemes = array();

        if (!empty($themesUsageData[$marketPlaceId])) {
            foreach ($themesUsageData[$marketPlaceId] as $theme => $count) {
                if (!empty($channelThemes[$theme])) {
                    $usedThemes[$theme] = $channelThemes[$theme];
                }
            }
        }

        return $this->channelThemes = array_merge($usedThemes, $channelThemes);
    }

    public function getChannelThemeAttr()
    {
        $theme = $this->getChannelTheme();
        $themes = $this->getChannelThemes();

        if (!empty($themes[$theme])) {
            return $themes[$theme]['attributes'];
        }

        return null;
    }

    public function getChannelThemeNote()
    {
        $theme = $this->getChannelTheme();
        $themes = $this->getChannelThemes();

        if (!empty($themes[$theme])) {
            return $themes[$theme]['note'];
        }

        return null;
    }

    public function getChannelThemeAttrString()
    {
        $themesAttributes = $this->getChannelThemeAttr();

        if (!empty($themesAttributes)) {
            return implode(', ', $themesAttributes);
        }

        return $this->__('Variation Theme not found.');
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
        if (!$this->hasGeneralId() && $this->isGeneralIdOwner() && $this->hasChannelTheme()) {
            return $this->getChannelThemeAttr();
        }
        return array_keys($this->getListingProductTypeModel()->getChannelAttributesSets());
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

        return array();
    }

    public function getVirtualProductAttributes()
    {
        $typeModel = $this->getListingProductTypeModel();

        if ($virtualProductAttributes = $typeModel->getVirtualProductAttributes()) {
            return $virtualProductAttributes;
        }

        return array();
    }

    public function getVirtualChannelAttributes()
    {
        $typeModel = $this->getListingProductTypeModel();

        if ($virtualChannelAttributes = $typeModel->getVirtualChannelAttributes()) {
            return $virtualChannelAttributes;
        }

        return array();
    }

    // ---------------------------------------

    public function isChangeMatchedAttributesAllowed()
    {
        if ($this->isInAction() ) {
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
        if (!is_null($this->childListingProducts)) {
            return $this->childListingProducts;
        }

        return $this->childListingProducts = $this->getListingProductTypeModel()->getChildListingsProducts();
    }

    public function getCurrentProductVariations()
    {
        if (!is_null($this->currentProductVariations)) {
            return $this->currentProductVariations;
        }

        $magentoProductVariations = $this->getListingProduct()
            ->getMagentoProduct()
            ->getVariationInstance()
            ->getVariationsTypeStandard();

        $productVariations = array();

        foreach ($magentoProductVariations['variations'] as $option) {
            $productOption = array();

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

    public function getAmazonVariationsSet()
    {
        $variations = $this->getCurrentChannelVariations();

        if (empty($variations)) {
            return false;
        }

        $attributesOptions = array();

        foreach ($variations as $variation) {
            foreach ($variation as $attr => $option) {
                if (!isset($attributesOptions[$attr])) {
                    $attributesOptions[$attr] = array();
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

    public function getUnusedChannelVariations()
    {
        return $this->getListingProductTypeModel()->getUnusedChannelOptions();
    }

    // ---------------------------------------

    public function hasUnusedProductVariation()
    {
        return (bool)$this->getUnusedProductVariations();
    }

    public function hasUnusedChannelVariations()
    {
        return (bool)$this->getUnusedChannelVariations();
    }

    // ---------------------------------------

    public function hasChildWithEmptyProductOptions()
    {
        foreach ($this->getChildListingProducts() as $childListingProduct) {
            /** @var \Ess\M2ePro\Model\Listing\Product $childListingProduct */

            /** @var \Ess\M2ePro\Model\Amazon\Listing\Product\Variation\Manager\Type\Relation\Child $childTypeModel */
            $childTypeModel = $childListingProduct->getChildObject()->getVariationManager()->getTypeModel();

            if (!$childTypeModel->isVariationProductMatched()) {
                return true;
            }
        }

        return false;
    }

    public function hasChildWithEmptyChannelOptions()
    {
        foreach ($this->getChildListingProducts() as $childListingProduct) {
            /** @var \Ess\M2ePro\Model\Listing\Product $childListingProduct */

            /** @var \Ess\M2ePro\Model\Amazon\Listing\Product\Variation\Manager\Type\Relation\Child $childTypeModel */
            $childTypeModel = $childListingProduct->getChildObject()->getVariationManager()->getTypeModel();

            if (!$childTypeModel->isVariationChannelMatched()) {
                return true;
            }
        }

        return false;
    }

    //########################################
}