<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product\Category\Settings;

class Specific extends \Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock
{
    protected $_marketplaceId = null;
    protected $_categoryMode = null;
    protected $_categoryValue = null;

    protected $_internalData = array();
    protected $_uniqueId = '';
    protected $_isCompactMode = false;

    protected $_attributes = array();
    protected $_selectedSpecifics = array();

    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('ebayListingCategorySpecific');
        // ---------------------------------------

        $this->setTemplate('ebay/listing/product/category/settings/specific.phtml');

        $this->_isAjax = $this->getRequest()->isXmlHttpRequest();
    }

    protected function _beforeToHtml()
    {
        $uniqueId = $this->getUniqueId();

        // ---------------------------------------
        $buttonBlock = $this->createBlock('Magento\Button')
            ->setData(array(
                'label'   => $this->__('Remove'),
                'onclick' => 'EbayListingProductCategorySettingsSpecific'.$uniqueId.'Obj.removeSpecific(this);',
                'class'   => 'scalable delete remove_custom_specific_button',
                'style' => 'display: none'
            ));
        $this->setChild('remove_custom_specific_button', $buttonBlock);
        // ---------------------------------------

        // ---------------------------------------
        $buttonBlock = $this->createBlock('Magento\Button')
            ->setData(array(
                'label'   => $this->__('Add Custom Specific'),
                'onclick' => 'EbayListingProductCategorySettingsSpecific'.$uniqueId.'Obj.addCustomSpecificRow();',
                'class' => 'action primary add_custom_specific_button'
            ));
        $this->setChild('add_custom_specific_button', $buttonBlock);
        // ---------------------------------------

        // ---------------------------------------
        $buttonBlock = $this->createBlock('Magento\Button')
            ->setData(array(
                'label'   => $this->__('Remove'),
                'onclick' => 'EbayListingProductCategorySettingsSpecific'
                             .$uniqueId.'Obj.removeItemSpecificsCustomValue(this);',
                'class'   => 'action remove_item_specifics_custom_value_button',
            ));
        $this->setChild('remove_item_specifics_custom_value_button', $buttonBlock);
        // ---------------------------------------

        $this->setChild('messages', $this->getLayout()->createBlock('Magento\Framework\View\Element\Messages'));

        $this->setChild('specifics_help_block', $this->createBlock('HelpBlock'));
    }

    //########################################

    protected function _toHtml()
    {
        $this->jsTranslator->add(
            'The Category <b>%cat%</b> doesn\'t have Item Specific',
            $this->__('The Category <b>%cat%</b> doesn\'t have Item Specific.')
        );

        $this->jsUrl->addUrls($this->getHelper('Data')->getControllerActions('Ebay\Category'));

        $this->jsPhp->addConstants($this->getHelper('Data')->getClassConstants('\Ess\M2ePro\Helper\View\Ebay'));
        $this->jsPhp->addConstants($this->getHelper('Data')->getClassConstants('\Ess\M2ePro\Helper\Component\Ebay'));
        $this->jsPhp->addConstants(
            $this->getHelper('Data')->getClassConstants('\Ess\M2ePro\Model\Ebay\Template\Category')
        );
        $this->jsPhp->addConstants(
            $this->getHelper('Data')->getClassConstants('\Ess\M2ePro\Model\Ebay\Template\Category\Specific')
        );

        $uniqueId = $this->getUniqueId();

        $attributes = $this->getHelper('Data')->jsonEncode($this->getAttributes());

        $dictionarySpecifics       = $this->getHelper('Data')->jsonEncode($this->getDictionarySpecifics());
        $ebaySelectedSpecifics     = $this->getHelper('Data')->jsonEncode($this->getEbaySelectedSpecifics());
        $customSelectedSpecifics   = $this->getHelper('Data')->jsonEncode($this->getCustomSelectedSpecifics());

        $this->js->add(<<<JS

require([
    'jquery',
    'mage/backend/form',
    'mage/backend/validation',
    'M2ePro/Ebay/Listing/Product/Category/Settings/Specific',
    'M2ePro/Plugin/Magento/AttributeCreator'
], function(jQuery){

    jQuery('#{$uniqueId}category_specific_form').form()
        .validation({
            validationUrl: '',
            highlight: function(element) {
                var detailsElement = jQuery(element).closest('details');
                if (detailsElement.length && detailsElement.is('.details')) {
                    var summaryElement = detailsElement.find('summary');
                    if (summaryElement.length && summaryElement.attr('aria-expanded') === "false") {
                        summaryElement.trigger('click');
                    }
                }
                jQuery(element).trigger('highlight.validate');
            }
        });

    EbayListingProductCategorySettingsSpecific{$uniqueId}Obj = new EbayListingProductCategorySettingsSpecific(
        '{$this->getMarketplaceId()}',
        '{$this->getCategoryMode()}',
        '{$this->getCategoryValue()}',
        '{$uniqueId}'
    );

    EbayListingProductCategorySettingsSpecific{$uniqueId}Obj
        .setAttributes({$attributes})
        .setDictionarySpecifics({$dictionarySpecifics})
        .setEbaySelectedSpecifics({$ebaySelectedSpecifics})
        .setCustomSelectedSpecifics({$customSelectedSpecifics})
        .renderSpecifics();

});

JS
);

        return parent::_toHtml();
    }

    //########################################

    public function getMarketplaceId()
    {
        return $this->_marketplaceId;
    }

    public function setMarketplaceId($marketplaceId)
    {
        $this->_marketplaceId = $marketplaceId;
        return $this;
    }

    // ---------------------------------------

    public function getCategoryMode()
    {
        return $this->_categoryMode;
    }

    public function setCategoryMode($categoryMode)
    {
       $this->_categoryMode = $categoryMode;
        return $this;
    }

    // ---------------------------------------

    public function getCategoryValue()
    {
        return $this->_categoryValue;
    }

    public function setCategoryValue($categoryValue)
    {
        $this->_categoryValue = $categoryValue;
        return $this;
    }

    //########################################

    public function setInternalData(array $data)
    {
        $this->_internalData = $data;
        return $this;
    }

    public function getInternalData()
    {
        return $this->_internalData;
    }

    // ---------------------------------------

    public function setUniqueId($id)
    {
        $this->_uniqueId = $id;
        return $this;
    }

    public function getUniqueId()
    {
        return $this->_uniqueId;
    }

    // ---------------------------------------

    public function setCompactMode($isMode = true)
    {
        $this->_isCompactMode = $isMode;
        return $this;
    }

    public function isCompactMode()
    {
        return $this->_isCompactMode;
    }

    //########################################

    public function getAttributes()
    {
        return $this->getHelper('Magento\Attribute')->getAll();
    }

    // ---------------------------------------

    public function getSelectedSpecifics()
    {
        return $this->_selectedSpecifics;
    }

    public function setSelectedSpecifics(array $specifics)
    {
        foreach ($specifics as $specific) {

            if ($specific['mode'] == \Ess\M2ePro\Model\Ebay\Template\Category\Specific::MODE_CUSTOM_ITEM_SPECIFICS) {
                $specific['value_custom_value'] = $this->getHelper('Data')->jsonDecode($specific['value_custom_value']);
                $this->_selectedSpecifics[] = $specific;
                continue;
            }

            $temp = \Ess\M2ePro\Model\Ebay\Template\Category\Specific::VALUE_MODE_EBAY_RECOMMENDED;
            if ($specific['value_mode'] == $temp) {
                $specific['value_data'] = $this->getHelper('Data')->jsonDecode($specific['value_ebay_recommended']);
            }
            unset($specific['value_ebay_recommended']);

            if ($specific['value_mode'] == \Ess\M2ePro\Model\Ebay\Template\Category\Specific::VALUE_MODE_CUSTOM_VALUE) {
                $specific['value_data'] = $this->getHelper('Data')->jsonDecode($specific['value_custom_value']);
            }
            unset($specific['value_custom_value']);

            $temp = \Ess\M2ePro\Model\Ebay\Template\Category\Specific::VALUE_MODE_CUSTOM_ATTRIBUTE;
            if ($specific['value_mode'] == $temp) {
                $specific['value_data'] = $specific['value_custom_attribute'];
            }

            $temp = \Ess\M2ePro\Model\Ebay\Template\Category\Specific::VALUE_MODE_CUSTOM_LABEL_ATTRIBUTE;
            if ($specific['value_mode'] == $temp) {
                $specific['value_data'] = $specific['value_custom_attribute'];
            }
            unset($specific['value_custom_attribute']);

            unset($specific['id']);
            unset($specific['template_category_id']);
            unset($specific['update_date']);
            unset($specific['create_date']);

            $this->_selectedSpecifics[] = $specific;
        }

        return $this;
    }

    //########################################

    public function getDictionarySpecifics()
    {
        if ($this->getCategoryMode() == \Ess\M2ePro\Model\Ebay\Template\Category::CATEGORY_MODE_ATTRIBUTE) {
            return array();
        }

        $specifics = $this->getHelper('Component\Ebay\Category\Ebay')->getSpecifics(
            $this->getCategoryValue(), $this->getMarketplaceId()
        );

        return is_null($specifics) ? array() : $specifics;
    }

    public function getEbaySelectedSpecifics()
    {
        return $this->filterSelectedSpecificsByMode(
            \Ess\M2ePro\Model\Ebay\Template\Category\Specific::MODE_ITEM_SPECIFICS
        );
    }

    public function getCustomSelectedSpecifics()
    {
        return $this->filterSelectedSpecificsByMode(
            \Ess\M2ePro\Model\Ebay\Template\Category\Specific::MODE_CUSTOM_ITEM_SPECIFICS
        );
    }

    // ---------------------------------------

    public function getRequiredDetailsFields()
    {
        $features = $this->getHelper('Component\Ebay\Category\Ebay')->getFeatures(
            $this->getCategoryValue(), $this->getMarketplaceId()
        );

        if (empty($features)) {
            return array();
        }

        $statusRequired = \Ess\M2ePro\Helper\Component\Ebay\Category\Ebay::PRODUCT_IDENTIFIER_STATUS_REQUIRED;

        $requiredFields = array();
        foreach (array('ean','upc','isbn') as $identifier) {

            $key = $identifier.'_enabled';
            if (!isset($features[$key]) || $features[$key] != $statusRequired) {
                continue;
            }

            $requiredFields[] = strtoupper($identifier);
        }

        return $requiredFields;
    }

    //########################################

    protected function filterSelectedSpecificsByMode($mode)
    {
        if (count($this->getSelectedSpecifics()) == 0) {
            return array();
        }

        $customSpecifics = array();
        foreach ($this->getSelectedSpecifics() as $selectedSpecific) {
            if ($selectedSpecific['mode'] != $mode) {
                continue;
            }

            unset($selectedSpecific['mode']);
            $customSpecifics[] = $selectedSpecific;
        }

        return $customSpecifics;
    }

    //########################################
}