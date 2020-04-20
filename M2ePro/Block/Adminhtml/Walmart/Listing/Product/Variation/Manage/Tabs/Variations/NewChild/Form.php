<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product\Variation\Manage\Tabs\Variations\NewChild;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product\Variation\Manage\Tabs\Variations\NewChild\Form
 */
class Form extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm
{
    protected $childListingProducts = null;
    protected $currentProductVariations = null;
    protected $productVariationsTree = [];
    protected $channelVariationsTree = [];

    /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
    protected $listingProduct;

    //########################################

    protected function _prepareForm()
    {
        $matchedAttributes = $this->getMatchedAttributes();

        $virtualProductAttributes = $this->getVirtualProductAttributes();
        $virtualChannelAttributes = $this->getVirtualChannelAttributes();

        $form = $this->_formFactory->create(
            [
                'data' => [
                    'id' => 'variation_manager_product_options_form',
                    'method' => 'post',
                    'action' => 'javascript:void(0)',
                ]
            ]
        );

        $form->addField(
            'product_id',
            'hidden',
            [
                'name' => 'product_id',
                'value' => $this->getListingProduct()->getId()
            ]
        );

        $fieldset = $form->addFieldset(
            'general_fieldset',
            [
                'collapsable' => false
            ]
        );
        $html = <<<HTML
<table id="manage_variations_new_child_product_variation"
       class="data-grid data-grid-not-hovered"
       style="width: 50%;
              float: left;
              padding-right: 25px;
              border-right: 1px solid #D6D6D6 !important;"
       cellspacing="0" cellpadding="0">
    <tr>
        <td class="label" colspan="2" style="border-bottom: 1px solid #D6D6D6 !important;">
            <b>{$this->__('Magento Variation')}</b>
        </td>
    </tr>
HTML;

        $i = 0;
        foreach ($matchedAttributes as $magentoAttr => $walmartAttr) {
            $style = array_key_exists($magentoAttr, $virtualProductAttributes) ? 'border-bottom: 2px dotted grey;' : '';

            $html .= <<<HTML
    <tr>
        <td class="label" style="width: auto; min-width: 148px;">
            <label style="width: auto; max-width: 148px;">
                <span style="{$style}">{$magentoAttr}</span>: <span class="required">*</span>
            </label>
        </td>
        <td class="value">
            <input type="hidden"
                   value="{$this->getHelper('Data')->escapeHtml($magentoAttr)}"
                   name="new_child_product[product][attributes][]" class="new-child-product-attribute">
            <select name="new_child_product[product][options][]"
                class="new-child-product-option select admin__control-select"
                disabled="disabled"
                onchange="ListingProductVariationManageVariationsGridObj.validateNewChildAttributeOptions('product')">
                <option value=""></option>
            </select>
        </td>
    </tr>
HTML;
            $i++;
        }

        $productVariationsTree = $this->getHelper('Data')->jsonEncode($this->getProductVariationsTree());

        $html .= <<<HTML
    <tr id="new_child_product_product_options_error_row">
        <td class="label" style="width: auto; min-width: 75px; border-bottom: none;"></td>
        <td class="value" style="border-bottom: none;">
            <div id="new_child_product_product_options_error" class="error" style="display: none">
                {$this->__('Please select Magento Variation')}
            </div>
        </td>
    </tr>

</table>
<div id="variation_manager_unused_product_variations_tree" style="display: none;">{$productVariationsTree}</div>

<table id="manage_variations_new_child_channel_variation"
       class="data-grid data-grid-not-hovered"
       style="width: 50%; float: left; padding-left: 25px;" cellspacing="0" cellpadding="0">
    <tr>
        <td class="label manage-variations-new-child-channel-options"
            colspan="2"
            style="border-right: none; border-bottom: 1px solid #D6D6D6 !important;">
            <b>{$this->__('Walmart Variation')}</b>&nbsp;
            <span id="manage_variations_create_new_product_title" style="display: none; color: #808080">
                ({$this->__('New Product Type will be created')})
            </span>
        </td>
    </tr>
HTML;

        $i = 0;
        foreach ($matchedAttributes as $magentoAttr => $walmartAttr) {
            $style = array_key_exists($walmartAttr, $virtualChannelAttributes) ? 'border-bottom: 2px dotted grey;' : '';

            $html .= <<<HTML
    <tr class="manage-variations-new-child-channel-options">
        <td class="label" style="width: auto; min-width: 148px;">
            <label style="width: auto; max-width: 148px;">
                <span style="{$style}">{$walmartAttr}</span>: <span class="required">*</span>
            </label>
        </td>
        <td class="value">
            <input type="hidden"
                   value="{$this->getHelper('Data')->escapeHtml($walmartAttr)}"
                   name="new_child_product[channel][attributes][]" class="new-child-channel-attribute">
            <select id="new_child_product_channel_option_{$i}"
                    name="new_child_product[channel][options][]"
                    class="new-child-channel-option select admin__control-select"
                    disabled="disabled"
                    onchange="ListingProductVariationManageVariationsGridObj.validateNewChildAttributeOptions('channel')
                    ">
                <option value=""></option>
            </select>
        </td>
    </tr>
HTML;
            $i++;
        }

        $html .= <<<HTML
        <tr id="new_child_product_channel_options_error_row">
            <td class="label" style="width: auto; min-width: 75px;border-bottom: none;"></td>
            <td class="value" style="border-bottom: none;">
                <div id="new_child_product_channel_options_error" class="error" style="display: none">
                    {$this->__('Please select Walmart Variation')}
                </div>
            </td>
        </tr>

    </table>
    <div style="clear:both"></div>
HTML;

        $this->css->add(
            <<<CSS
.variation_new_child_form-full-width .admin__field-control {
    width: 100% !important;
    margin-left: 0px !important;
}

#variation_manager_product_options_form td {
    vertical-align: inherit;
}

#variation_manager_product_options_form .data-grid.data-grid-not-hovered td.label {
    border-left: none;
    border-right: none;
}

#variation_manager_product_options_form .data-grid.data-grid-not-hovered td.value {
    border-left: none;
    border-right: none;
}
CSS
        );

        $fieldset->addField(
            'general_container',
            self::CUSTOM_CONTAINER,
            [
                'text' => $html,
                'field_extra_attributes' => 'style="margin-left: 0px;"',
                'css_class' => 'variation_new_child_form-full-width'
            ]
        );

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    //########################################

    protected function _toHtml()
    {
        $helpBlock = $this->createBlock('HelpBlock')->setData([
            'content' => $this->__(
                'To sell Walmart Child Products it is necessary to set correspondence between Magento Variations and
                Walmart Variations. <br/><br/>

                For that you should select available unused Options of Attributes of Magento Product and available
                unused Options of Walmart Parent Product. After clicking of <i class="underline">"Confirm"</i> Button,
                Child Product will be added to the Grid and ready for List Action. <br/><br/>

                In case you are the Creator of Walmart Parent Product, you will be able to create New Child Product
                Type for this Parent Product. To do this it is just enough to select available Options of Magento
                Product Attributes, New Child Product Type will be created for. <br/><br/>

                <b>Note:</b> You can set matching of Magento Product Attributes and Walmart Parent Product Attributes
                in Settings Tab\'s Block Variation Attributes.'
            )
        ]);

        return '<div id="variation_manager_product_options_form_container">' .
            $helpBlock->toHtml() .
            parent::_toHtml() . '</div>';
    }

    //########################################

    /**
     * @param \Ess\M2ePro\Model\Listing\Product $listingProduct
     */
    public function setListingProduct(\Ess\M2ePro\Model\Listing\Product $listingProduct)
    {
        $this->listingProduct = $listingProduct;
    }

    /**
     * @return \Ess\M2ePro\Model\Listing\Product
     */
    protected function getListingProduct()
    {
        return $this->listingProduct;
    }

    //########################################

    public function hasChannelAttributes()
    {
        return $this->getListingProduct()->getChildObject()
            ->getVariationManager()->getTypeModel()->hasChannelAttributes();
    }

    // ---------------------------------------

    public function getMatchedAttributes()
    {
        return $this->getListingProduct()->getChildObject()
            ->getVariationManager()->getTypeModel()->getMatchedAttributes();
    }

    public function getVirtualProductAttributes()
    {
        return $this->getListingProduct()->getChildObject()
            ->getVariationManager()->getTypeModel()->getVirtualProductAttributes();
    }

    public function getVirtualChannelAttributes()
    {
        return $this->getListingProduct()->getChildObject()
            ->getVariationManager()->getTypeModel()->getVirtualChannelAttributes();
    }

    // ---------------------------------------

    public function getUnusedProductVariations()
    {
        return $this->getListingProduct()
            ->getChildObject()
            ->getVariationManager()
            ->getTypeModel()
            ->getUnusedProductOptions();
    }

    // ---------------------------------------

    public function getChildListingProducts()
    {
        if ($this->childListingProducts !== null) {
            return $this->childListingProducts;
        }

        return $this->childListingProducts = $this->getListingProduct()->getChildObject()
            ->getVariationManager()->getTypeModel()->getChildListingsProducts();
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
        return $this->getListingProduct()->getChildObject()
            ->getVariationManager()->getTypeModel()->getChannelVariations();
    }

    // ---------------------------------------

    public function getAttributesOptionsFromVariations($variations)
    {
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
        return $this->getListingProduct()
            ->getChildObject()
            ->getVariationManager()
            ->getTypeModel()
            ->getUsedChannelOptions();
    }

    public function getUsedProductVariations()
    {
        return $this->getListingProduct()
            ->getChildObject()
            ->getVariationManager()
            ->getTypeModel()
            ->getUsedProductOptions();
    }

    // ---------------------------------------

    public function getProductVariationsTree()
    {
        if (empty($this->productVariationsTree)) {
            $matchedAttributes = $this->getMatchedAttributes();
            $unusedVariations = $this->sortVariationsAttributes(
                $this->getUnusedProductVariations(),
                array_keys($matchedAttributes)
            );
            $variationsSets = $this->sortVariationAttributes(
                $this->getAttributesOptionsFromVariations($unusedVariations),
                array_keys($matchedAttributes)
            );

            $firstAttribute = key($matchedAttributes);

            $this->productVariationsTree = $this->prepareVariations(
                $firstAttribute,
                $unusedVariations,
                $variationsSets
            );
        }

        return $this->productVariationsTree;
    }

    private function sortVariationsAttributes($variations, $sortTemplate)
    {
        foreach ($variations as $key => $variation) {
            $variations[$key] = $this->sortVariationAttributes($variation, $sortTemplate);
        }

        return $variations;
    }

    private function sortVariationAttributes($variation, $sortTemplate)
    {
        $sortedData = [];

        foreach ($sortTemplate as $attr) {
            $sortedData[$attr] = $variation[$attr];
        }

        return $sortedData;
    }

    private function prepareVariations($currentAttribute, $magentoVariations, $variationsSets, $filters = [])
    {
        $return = false;

        $temp = array_flip(array_keys($variationsSets));

        $lastAttributePosition = count($variationsSets) - 1;
        $currentAttributePosition = $temp[$currentAttribute];

        if ($currentAttributePosition != $lastAttributePosition) {
            $temp = array_keys($variationsSets);
            $nextAttribute = $temp[$currentAttributePosition + 1];

            foreach ($variationsSets[$currentAttribute] as $option) {
                $filters[$currentAttribute] = $option;

                $result = $this->prepareVariations(
                    $nextAttribute,
                    $magentoVariations,
                    $variationsSets,
                    $filters
                );

                if (!$result) {
                    continue;
                }

                $return[$currentAttribute][$option] = $result;
            }

            if ($return !== false) {
                ksort($return[$currentAttribute]);
            }

            return $return;
        }

        $return = false;
        foreach ($magentoVariations as $key => $magentoVariation) {
            foreach ($magentoVariation as $attribute => $option) {
                if ($attribute == $currentAttribute) {
                    if (count($variationsSets) != 1) {
                        continue;
                    }

                    $values = array_flip($variationsSets[$currentAttribute]);
                    $return = [$currentAttribute => $values];

                    foreach ($return[$currentAttribute] as &$option) {
                        $option = true;
                    }

                    return $return;
                }

                if ($option != $filters[$attribute]) {
                    unset($magentoVariations[$key]);
                    continue;
                }

                foreach ($magentoVariation as $tempAttribute => $tempOption) {
                    if ($tempAttribute == $currentAttribute) {
                        $option = $tempOption;
                        $return[$currentAttribute][$option] = true;
                    }
                }
            }
        }

        if (count($magentoVariations) < 1) {
            return false;
        }

        if ($return !== false) {
            ksort($return[$currentAttribute]);
        }

        return $return;
    }

    //########################################
}
