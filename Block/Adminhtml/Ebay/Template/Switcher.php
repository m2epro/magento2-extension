<?php

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Template;

use Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock;

class Switcher extends AbstractBlock
{
    protected $_template = 'ebay/template/switcher.phtml';

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
        $title = '';

        switch ($this->getTemplateNick()) {
            case \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_PAYMENT:
                $title = $this->getHelper('Module\Translation')->__('Payment');
                break;
            case \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_SHIPPING:
                $title = $this->getHelper('Module\Translation')->__('Shipping');
                break;
            case \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_RETURN_POLICY:
                $title = $this->getHelper('Module\Translation')->__('Return');
                break;
            case \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_SELLING_FORMAT:
                $title = $this->getHelper('Module\Translation')->__('Price, Quantity and Format');
                break;
            case \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_DESCRIPTION:
                $title = $this->getHelper('Module\Translation')->__('Description');
                break;
            case \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_SYNCHRONIZATION:
                $title = $this->getHelper('Module\Translation')->__('Synchronization');
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
                $width = 70;
                break;

            case \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_SELLING_FORMAT:
            case \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_DESCRIPTION:
                $width = 200;
                break;

            case \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_SYNCHRONIZATION:
                $width = 140;
                break;

            default:
                $width = 100;
                break;
        }

        return $width;
    }

    //########################################

    public static function getSwitcherUrlHtml()
    {
        //TODO
        $params = array();

        // initiate account param
        // ---------------------------------------
        $account = Mage::helper('M2ePro/Data\Global')->getValue('ebay_account');
        $params['account_id'] = $account->getId();
        // ---------------------------------------

        // initiate marketplace param
        // ---------------------------------------
        $marketplace = Mage::helper('M2ePro/Data\Global')->getValue('ebay_marketplace');
        $params['marketplace_id'] = $marketplace->getId();
        // ---------------------------------------

        // initiate attribute sets param
        // ---------------------------------------
        $attributeSets = Mage::helper('M2ePro/Data\Global')->getValue('ebay_attribute_sets');
        $params['attribute_sets'] = implode(',', $attributeSets);
        // ---------------------------------------

        // initiate display use default option param
        // ---------------------------------------
        $displayUseDefaultOption = Mage::helper('M2ePro/Data\Global')->getValue('ebay_display_use_default_option');
        $params['display_use_default_option'] = (int)(bool)$displayUseDefaultOption;
        // ---------------------------------------

        $url = Mage::helper('adminhtml')->getUrl('M2ePro/adminhtml_ebay_template/getTemplateHtml', $params);

        $urls = json_encode(array(
            'adminhtml_ebay_template/getTemplateHtml' => $url
        ));

        return <<<HTML
<script type="text/javascript">
    M2ePro.url.add({$urls});
</script>
HTML;
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

        return $this->getLayout()->createBlock($blockName,'', ['data' => $parameters]);
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
        if ($this->getHelper('View\Ebay')->isSimpleMode()) {
            return false;
        }

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
        if ($this->getHelper('M2ePro/View\Ebay')->isSimpleMode()) {
            return '';
        }

        $html = $this->getChildHtml('save_custom_as_template');
        $nick = $this->getTemplateNick();

        return <<<HTML
<div id="template_{$nick}_buttons_container" class="entry-edit">
    <div class="fieldset">
        <div class="hor-scroll" style="padding-right: 1px;">{$html}</div>
    </div>
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
            'class'   => 'save-custom-template-' . $nick,
            'label'   => $this->getHelper('Module\Translation')->__('Save as New Policy'),
            'onclick' => 'EbayListingTemplateSwitcherHandlerObj.customSaveAsTemplate(\''. $nick .'\');',
        );
        $buttonBlock = $this->getLayout()->createBlock('adminhtml/widget_button')->setData($data);
        $this->setChild('save_custom_as_template', $buttonBlock);
        // ---------------------------------------
    }

    //########################################

    protected function _toHtml()
    {
        $this->addRequireJs([
            'switcherObj' => 'M2ePro/Ebay/Template/Switcher',
            '$' => 'jquery',
            '_' => 'underscore'
        ], <<<JS
        var init = function() {

            switcherObj.updateEditVisibility('{$this->getTemplateNick()}');
            switcherObj.updateButtonsVisibility('{$this->getTemplateNick()}');
            switcherObj.updateTemplateLabelVisibility('{$this->getTemplateNick()}');

            $('#{$this->getSwitcherId()}').on('change', _.bind(switcherObj.change, switcherObj));

            if ({$this->isTemplateModeTemplate()}) {
                $('#{$this->getSwitcherId()}').trigger('change');
            }
        };

        if ({$this->getRequest()->isXmlHttpRequest()}) {
            init();
        } else {
            $(init);
        }
JS
        );

//        $this->initKnockout('M2ePro/Ebay/Template/Switcher');

        return parent::_toHtml() . $this->getFormDataBlockHtml() . $this->getButtonsHtml();
    }

    //########################################
}