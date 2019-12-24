<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Template\Description\Category\Chooser\Tabs;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Amazon\Template\Description\Category\Chooser\Tabs\Browse
 */
class Browse extends \Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock
{
    protected $_template = 'amazon/template/description/category/chooser/tabs/browse.phtml';

    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('amazonTemplateDescriptionCategoryChooserBrowse');
        // ---------------------------------------
    }

    //########################################

    protected function _beforeToHtml()
    {
        $this->js->add("AmazonTemplateDescriptionCategoryChooserObj.renderTopLevelCategories('chooser_browser');");

        if ($this->getHelper('Module\Wizard')->isActive(\Ess\M2ePro\Helper\View\Amazon::WIZARD_INSTALLATION_NICK)) {
            return parent::_beforeToHtml();
        }

        $helpBlock = $this->createBlock('HelpBlock', '', ['data' => [
            'content' => $this->__(
                'If you cannot find necessary Category, try to
                <a href="javascript:void(0)"
                   onclick="AmazonTemplateDescriptionCategoryChooserObj.refreshAmazonCategories()">
                Update Amazon Marketplaces data</a>.'
            ),
            'no_collapse' => true,
        ]]);

        $this->setChild('help_block', $helpBlock);

        return parent::_beforeToHtml();
    }

    //########################################
}
