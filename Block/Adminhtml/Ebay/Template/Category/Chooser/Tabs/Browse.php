<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Template\Category\Chooser\Tabs;

class Browse extends \Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock
{
    /** @var \Ess\M2ePro\Helper\View\Ebay */
    public $ebayViewHelper;
    /** @var \Ess\M2ePro\Helper\Module\Wizard */
    private $wizardHelper;

    public function __construct(
        \Ess\M2ePro\Helper\View\Ebay $ebayViewHelper,
        \Ess\M2ePro\Helper\Module\Wizard $wizardHelper,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->ebayViewHelper = $ebayViewHelper;
        $this->wizardHelper = $wizardHelper;
    }

    public function _construct()
    {
        parent::_construct();

        $this->setId('ebayCategoryChooserCategoryBrowse');
        $this->setTemplate('ebay/template/category/chooser/tabs/browse.phtml');
    }

    public function isWizardActive()
    {
        return $this->wizardHelper->isActive(\Ess\M2ePro\Helper\View\Ebay::WIZARD_INSTALLATION_NICK);
    }
}
