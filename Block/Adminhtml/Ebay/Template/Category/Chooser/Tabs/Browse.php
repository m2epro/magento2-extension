<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Template\Category\Chooser\Tabs;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Ebay\Template\Category\Chooser\Tabs\Browse
 */
class Browse extends \Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        $this->setId('ebayCategoryChooserCategoryBrowse');
        $this->setTemplate('ebay/template/category/chooser/tabs/browse.phtml');
    }

    public function isWizardActive()
    {
        return $this->getHelper('Module\Wizard')->isActive(\Ess\M2ePro\Helper\View\Ebay::WIZARD_INSTALLATION_NICK);
    }

    //########################################
}
