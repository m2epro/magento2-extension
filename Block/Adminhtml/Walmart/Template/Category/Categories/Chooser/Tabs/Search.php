<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Template\Category\Categories\Chooser\Tabs;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Walmart\Template\Category\Categories\Chooser\Tabs\Search
 */
class Search extends \Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock
{
    protected $_template = 'walmart/template/category/categories/chooser/tabs/search.phtml';

    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('walmartTemplateCategoryCategoriesChooserSearch');
        // ---------------------------------------
    }

    public function isWizardActive()
    {
        return $this->getHelper('Module\Wizard')->isActive(\Ess\M2ePro\Helper\View\Walmart::WIZARD_INSTALLATION_NICK);
    }

    //########################################
}
