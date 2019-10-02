<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Template\Description\Category\Chooser\Tabs;

/**
 * Class Search
 * @package Ess\M2ePro\Block\Adminhtml\Amazon\Template\Description\Category\Chooser\Tabs
 */
class Search extends \Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock
{
    protected $_template = 'amazon/template/description/category/chooser/tabs/search.phtml';

    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('amazonTemplateDescriptionCategoryChooserSearch');
        // ---------------------------------------
    }

    public function isWizardActive()
    {
        return $this->getHelper('Module\Wizard')->isActive(\Ess\M2ePro\Helper\View\Amazon::WIZARD_INSTALLATION_NICK);
    }

    //########################################
}
