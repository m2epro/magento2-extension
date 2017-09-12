<?php

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Template;

use Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock;

class Switcher extends AbstractBlock
{
    const MODE_LISTING_PRODUCT = 1;
    const MODE_COMMON          = 2;

    protected $_template = 'ebay/listing/template/switcher.phtml';

    private $templates = NULL;

    //########################################

    public function _construct()
    {
        // Initialization block
        // ---------------------------------------
        $this->setId('ebayListingTemplateSwitcher');
        // ---------------------------------------

        parent::_construct();
    }

    //########################################

    public function getHeaderText()
    {
        if ($this->getData('custom_header_text')) {
            return $this->getData('custom_header_text');
        }

        $title = '';

        switch ($this->getTemplateNick()) {
            case \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_PAYMENT:
                $title = $this->__('Payment');
                break;
            case \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_SHIPPING:
                $title = $this->__('Shipping');
                break;
            case \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_RETURN_POLICY:
                $title = $this->__('Return');
                break;
            case \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_SELLING_FORMAT:
                $title = $this->__('Price, Quantity and Format');
                break;
            case \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_DESCRIPTION:
                $title = $this->__('Description');
                break;
            case \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_SYNCHRONIZATION:
                $title = $this->__('Synchronization');
                break;
        }

        return $title;
    }

    //########################################

    public function getHeaderWidth()
    {
        switch ($this->getTemplateNick()) {
            case \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_RETURN_POLICY:
            case \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_SHIPPING:
            case \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_PAYMENT:
                $width = 100;
                break;

            case \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_SELLING_FORMAT:
            case \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_DESCRIPTION:
                $width = 250;
                break;

            case \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_SYNCHRONIZATION:
                $width = 170;
                break;

            default:
                $width = 130;
                break;
        }

        return $width;
    }

    //########################################

    public function getTemplateNick()
    {
        if (!isset($this->_data['template_nick'])) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Template nick is not defined.');
        }

        return $this->_data['template_nick'];
    }

    public function getTemplateMode()
    {
        $templateMode = $this->getHelper('Data\GlobalData')->getValue(
            'ebay_template_mode_' . $this->getTemplateNick()
        );

        if (is_null($templateMode)) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Template Mode is not initialized.');
        }

        return $templateMode;
    }

    public function getTemplateId()
    {
        $template = $this->getTemplateObject();

        if (is_null($template)) {
            return NULL;
        }

        return $template->getId();
    }

    public function getTemplateObject()
    {
        $template = $this->getHelper('Data\GlobalData')->getValue('ebay_template_' . $this->getTemplateNick());

        if (!is_null($template) && !is_null($template->getId())) {
            return $template;
        }

        return NULL;
    }

    // ---------------------------------------

    public function isTemplateModeParentForced()
    {
        $key = 'ebay_template_force_parent_' . $this->getTemplateNick();
        $forcedParent = $this->getHelper('Data\GlobalData')->getValue($key);

        return (bool)$forcedParent;
    }

    public function isTemplateModeParent()
    {
        return $this->getTemplateMode() == \Ess\M2ePro\Model\Ebay\Template\Manager::MODE_PARENT;
    }

    public function isTemplateModeCustom()
    {
        return $this->getTemplateMode() == \Ess\M2ePro\Model\Ebay\Template\Manager::MODE_CUSTOM;
    }

    public function isTemplateModeTemplate()
    {
        return $this->getTemplateMode() == \Ess\M2ePro\Model\Ebay\Template\Manager::MODE_TEMPLATE;
    }

    //########################################

    public function getFormDataBlock()
    {
        $blockName = NULL;

        switch ($this->getTemplateNick()) {
            case \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_PAYMENT:
                $blockName = 'Ess\M2ePro\Block\Adminhtml\Ebay\Template\Payment\Edit\Form\Data';
                break;
            case \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_RETURN_POLICY:
                $blockName = 'Ess\M2ePro\Block\Adminhtml\Ebay\Template\ReturnPolicy\Edit\Form\Data';
                break;
            case \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_SHIPPING:
                $blockName = 'Ess\M2ePro\Block\Adminhtml\Ebay\Template\Shipping\Edit\Form\Data';
                break;
            case \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_SELLING_FORMAT:
                $blockName = 'Ess\M2ePro\Block\Adminhtml\Ebay\Template\SellingFormat\Edit\Form\Data';
                break;
            case \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_DESCRIPTION:
                $blockName = 'Ess\M2ePro\Block\Adminhtml\Ebay\Template\Description\Edit\Form\Data';
                break;
            case \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_SYNCHRONIZATION:
                $blockName = 'Ess\M2ePro\Block\Adminhtml\Ebay\Template\Synchronization\Edit\Form\Data';
                break;
        }

        if (is_null($blockName)) {
            throw new \Ess\M2ePro\Model\Exception\Logic(
                sprintf('Form data Block for Template nick "%s" is unknown.', $this->getTemplateNick())
            );
        }

        $parameters = array(
            'is_custom' => $this->isTemplateModeCustom(),
            'custom_title' => $this->getHelper('Data\GlobalData')->getValue('ebay_custom_template_title'),
            'policy_localization' => $this->getData('policy_localization')
        );

        return $this->getLayout()->createBlock($blockName,'',['data' => $parameters]);
    }

    public function getFormDataBlockHtml($templateDataForce = false)
    {
        $nick = $this->getTemplateNick();

        if ($this->isTemplateModeCustom() || $templateDataForce) {
            $html = $this->getFormDataBlock()->toHtml();
            $style = '';
        } else {
            $html = '';
            $style = 'display: none;';
        }

        return <<<HTML
<div id="template_{$nick}_data_container" class="template-data-container" style="{$style}">
    {$html}
</div>
HTML;
    }

    //########################################

    public function canDisplaySwitcher()
    {
        $templates = $this->getTemplates();

        if (count($templates) == 0 && !$this->canDisplayUseDefaultOption()) {
            return false;
        }

        return true;
    }

    public function canDisplayUseDefaultOption()
    {
        $displayUseDefaultOption = $this->getHelper('Data\GlobalData')->getValue('ebay_display_use_default_option');

        if (is_null($displayUseDefaultOption)) {
            return true;
        }

        return (bool)$displayUseDefaultOption;
    }

    //########################################

    public function getTemplates()
    {
        if (!is_null($this->templates)) {
            return $this->templates;
        }

        $manager = $this->modelFactory->getObject('Ebay\Template\Manager')->setTemplate($this->getTemplateNick());

        $collection = $manager->getTemplateModel()
            ->getCollection()
            ->addFieldToFilter('is_custom_template', 0)
            ->setOrder('title', 'ASC');

        if ($manager->isMarketplaceDependentTemplate()) {
            $marketplace = $this->getHelper('Data\GlobalData')->getValue('ebay_marketplace');
            $collection->addFieldToFilter('marketplace_id', $marketplace->getId());
        }

        $this->templates = $collection->getItems();

        return $this->templates;
    }

    //########################################

    public function getSwitcherJsObjectName()
    {
        $nick = ucfirst($this->getTemplateNick());
        return "ebayTemplate{$nick}SwitcherJsObject";
    }

    public function getSwitcherId()
    {
        $nick = $this->getTemplateNick();
        return "template_{$nick}";
    }

    public function getSwitcherName()
    {
        $nick = $this->getTemplateNick();
        return "template_{$nick}";
    }

    //########################################

    public function getButtonsHtml()
    {
        $html = $this->getChildHtml('save_custom_as_template');
        $nick = $this->getTemplateNick();

        return <<<HTML
<div id="template_{$nick}_buttons_container">
    {$html}
</div>
HTML;
    }

    //########################################

    protected function _beforeToHtml()
    {
        parent::_beforeToHtml();

        // ---------------------------------------
        $nick = $this->getTemplateNick();
        $data = array(
            'class'   => 'action primary save-custom-template-' . $nick,
            'label'   => $this->__('Save as New Policy'),
            'onclick' => 'EbayListingTemplateSwitcherObj.customSaveAsTemplate(\''. $nick .'\');',
        );
        $buttonBlock = $this->createBlock('Magento\Button')->setData($data);
        $this->setChild('save_custom_as_template', $buttonBlock);
        // ---------------------------------------
    }

    //########################################

    protected function _toHtml()
    {

        $isTemplateModeTemplate = (int)$this->isTemplateModeTemplate();

        $this->js->add(<<<JS
    require([
        'Switcher/Initialization',
        'M2ePro/Ebay/Listing/Template/Switcher'
    ], function(){

        EbayListingTemplateSwitcherObj.updateEditVisibility('{$this->getTemplateNick()}');
        EbayListingTemplateSwitcherObj.updateButtonsVisibility('{$this->getTemplateNick()}');
        EbayListingTemplateSwitcherObj.updateTemplateLabelVisibility('{$this->getTemplateNick()}');

        $('{$this->getSwitcherId()}').observe('change', EbayListingTemplateSwitcherObj.change);

        if ({$isTemplateModeTemplate}) {
            $('{$this->getSwitcherId()}').simulate('change');
        }
    });
JS
);

        return parent::_toHtml() .
            $this->getFormDataBlockHtml() .
            $this->getButtonsHtml();
    }

    //########################################
}