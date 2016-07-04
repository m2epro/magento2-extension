<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Template\Description\Category\Chooser\Tabs;

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
        $this->js->add("AmazonTemplateDescriptionCategoryChooserObj.renderTopLevelCategories('chooser_browser');");

        return parent::_beforeToHtml();
    }

    //########################################
}