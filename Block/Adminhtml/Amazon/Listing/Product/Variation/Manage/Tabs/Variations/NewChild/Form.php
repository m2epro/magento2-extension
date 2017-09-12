<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Product\Variation\Manage\Tabs\Variations\NewChild;

use Ess\M2ePro\Model\Amazon\Listing\Product\Variation\Manager\Type\Relation\ChildRelation;

class Form extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm
{
    protected $childListingProducts = null;
    protected $currentProductVariations = null;
    protected $productVariationsTree = array();
    protected $channelVariationsTree = array();

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
        $generalIdOwnerClass = $this->isGeneralIdOwner() ? 'manage-variations' : '';

        $html = <<<HTML
<table id="manage_variations_new_child_product_variation"
       class="{$generalIdOwnerClass} data-grid data-grid-not-hovered"
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
        foreach ($matchedAttributes as $magentoAttr => $amazonAttr) {
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
       class="{$generalIdOwnerClass} data-grid data-grid-not-hovered"
       style="width: 50%; float: left; padding-left: 25px;" cellspacing="0" cellpadding="0">
    <tr>
        <td class="label manage-variations-new-child-channel-options"
            colspan="2"
            style="border-right: none; border-bottom: 1px solid #D6D6D6 !important;">
            <b>{$this->__('Amazon Variation')}</b>&nbsp;
            <span id="manage_variations_create_new_asin_title" style="display: none; color: #808080">
                ({$this->__('New ASIN/ISBN will be created')})
            </span>
        </td>
HTML;

        if ($this->isGeneralIdOwner() && $this->hasChannelTheme()) {

            $html .= <<<HTML
        <td style="border-left: none; border-right: none; padding-left: 5px;
                   width: 185px; text-align: center; vertical-align: middle;" rowspan="6">
HTML;

            if ($this->hasUnusedChannelVariations()) {

                $html .= <<<HTML
        <div id="manage_variations_create_new_asin">
            {$this->__('or')}&nbsp;&nbsp;
            <a href="javascript:void(0);" onclick="ListingProductVariationManageVariationsGridObj.createNewAsinBtn()">
                {$this->__('Create New ASIN/ISBN')}
            </a>
        </div>
        <div id="manage_variations_select_options" style="display: none;">
            <input type="hidden" name="create_new_asin" disabled="disabled" value="1">
            {$this->__('or')}&nbsp;&nbsp;
            <a href="javascript:void(0);" onclick="ListingProductVariationManageVariationsGridObj.selectOptionsBtn()">
                {$this->__('Select Existing Variation')}
            </a>
        </div>
HTML;
            } else {

                $html .= <<<HTML
            <input type="hidden" name="create_new_asin" value="1">
HTML;
            }

            $html .= <<<HTML
        </td>
HTML;
        }

        $html .= '</tr>';

        $i = 0;
        foreach ($matchedAttributes as $magentoAttr => $amazonAttr) {

            $style = array_key_exists($amazonAttr, $virtualChannelAttributes) ? 'border-bottom: 2px dotted grey;' : '';

            $html .= <<<HTML
    <tr class="manage-variations-new-child-channel-options">
        <td class="label" style="width: auto; min-width: 148px;">
            <label style="width: auto; max-width: 148px;">
                <span style="{$style}">{$amazonAttr}</span>: <span class="required">*</span>
            </label>
        </td>
        <td class="value">
            <input type="hidden"
                   value="{$this->getHelper('Data')->escapeHtml($amazonAttr)}"
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

        $channelVariationsTree = $this->getHelper('Data')->jsonEncode($this->getChannelVariationsTree());

        $html .= <<<HTML
        <tr id="new_child_product_channel_options_error_row">
            <td class="label" style="width: auto; min-width: 75px;border-bottom: none;"></td>
            <td class="value" style="border-bottom: none;">
                <div id="new_child_product_channel_options_error" class="error" style="display: none">
                    {$this->__('Please select Amazon Variation')}
                </div>
            </td>
        </tr>

    </table>
    <div style="clear:both"></div>
    <div id="variation_manager_unused_channel_variations_tree" style="display: none;">{$channelVariationsTree}</div>
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
                'To sell Amazon Child Products it is necessary to set correspondence between Magento Variations
                and Amazon Variations. <br/><br/>

                For that you should select available unused Options of Attributes of Magento Product and available
                unused Options of Amazon Parent Product. After clicking of <i class="underline">"Confirm"</i> Button,
                Child Product will be added to the Grid and ready for List Action. <br/><br/>

                In case you are the Creator of Amazon Parent Product, you will be able to create New Child ASIN/ISBN
                for this Parent Product. To do this it is just enough to select available Options of Magento
                Product Attributes, New Child ASIN/ISBN will be created for. <br/><br/>

                <b>Note:</b> You can set matching of Magento Product Attributes and Amazon Parent Product Attributes
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

    public function isGeneralIdOwner()
    {
        return $this->getListingProduct()->getChildObject()->isGeneralIdOwner();
    }

    // ---------------------------------------

    public function hasChannelTheme()
    {
        return $this->getListingProduct()->getChildObject()->getVariationManager()->getTypeModel()->hasChannelTheme();
    }

    public function hasUnusedChannelVariations()
    {
        return (bool)$this->getUnusedChannelVariations();
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

    public function getUnusedChannelVariations()
    {
        return $this->getListingProduct()
            ->getChildObject()
            ->getVariationManager()
            ->getTypeModel()
            ->getUnusedChannelOptions();
    }

    private function isVariationExistsInArray(array $needle, array $haystack)
    {
        foreach ($haystack as $option) {
            if ($option != $needle) {
                continue;
            }

            return true;
        }

        return false;
    }

    // ---------------------------------------

    public function getChildListingProducts()
    {
        if (!is_null($this->childListingProducts)) {
            return $this->childListingProducts;
        }

        return $this->childListingProducts = $this->getListingProduct()->getChildObject()
            ->getVariationManager()->getTypeModel()->getChildListingsProducts();
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
        return $this->getListingProduct()->getChildObject()
            ->getVariationManager()->getTypeModel()->getChannelVariations();
    }

    // ---------------------------------------

    public function getAttributesOptionsFromVariations($variations)
    {
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
                $firstAttribute,$unusedVariations,$variationsSets
            );
        }

        return $this->productVariationsTree;
    }

    public function getChannelVariationsTree()
    {
        if (empty($this->channelVariationsTree)) {

            $matchedAttributes = $this->getMatchedAttributes();
            $unusedVariations = $this->sortVariationsAttributes(
                $this->getUnusedChannelVariations(),
                array_values($matchedAttributes)
            );

            if (empty($unusedVariations)) {
                $this->channelVariationsTree = new \stdClass();

                return $this->channelVariationsTree;
            }

            $variationsSets = $this->sortVariationAttributes(
                $this->getAttributesOptionsFromVariations($unusedVariations),
                array_values($matchedAttributes)
            );

            $firstAttribute = $matchedAttributes[key($matchedAttributes)];

            $this->channelVariationsTree = $this->prepareVariations(
                $firstAttribute,$unusedVariations,$variationsSets
            );
        }

        return $this->channelVariationsTree;
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
        $sortedData = array();

        foreach ($sortTemplate as $attr) {
            $sortedData[$attr] = $variation[$attr];
        }

        return $sortedData;
    }

    private function prepareVariations($currentAttribute,$magentoVariations,$variationsSets,$filters = array())
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
                    $nextAttribute,$magentoVariations,$variationsSets,$filters
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
                    $return = array($currentAttribute => $values);

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