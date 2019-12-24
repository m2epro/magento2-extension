<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Template\Category\Categories\Chooser\Tabs;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Walmart\Template\Category\Categories\Chooser\Tabs\Browse
 */
class Browse extends \Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock
{
    protected $_template = 'walmart/template/category/categories/chooser/tabs/browse.phtml';

    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('walmartTemplateCategoryCategoriesChooserBrowse');
        // ---------------------------------------
    }

    //########################################

    protected function _beforeToHtml()
    {
        $this->js->add("WalmartTemplateCategoryCategoriesChooserObj.renderTopLevelCategories('chooser_browser');");

        if ($this->getHelper('Module\Wizard')->isActive(\Ess\M2ePro\Helper\View\Walmart::WIZARD_INSTALLATION_NICK)) {
            return parent::_beforeToHtml();
        }

        $helpBlock = $this->createBlock('HelpBlock', '', ['data' => [
            'content' => $this->__(
                'If you cannot find necessary Category, try to
                <a href="javascript:void(0)"
                   onclick="WalmartTemplateCategoryCategoriesChooserObj.refreshWalmartCategories()">
                Update Walmart Marketplaces data</a>.'
            ),
            'no_collapse' => true,
        ]]);

        $this->setChild('help_block', $helpBlock);

        return parent::_beforeToHtml();
    }

    //########################################
}
