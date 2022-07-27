<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Template\Description\Category\Chooser\Tabs;

class Browse extends \Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock
{
    /** @var string */
    protected $_template = 'amazon/template/description/category/chooser/tabs/browse.phtml';

    /** @var \Ess\M2ePro\Helper\Module\Wizard */
    private $wizardHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Module\Wizard $wizardHelper,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        array $data = []
    ) {
        $this->wizardHelper = $wizardHelper;
        parent::__construct($context, $data);
    }

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('amazonTemplateDescriptionCategoryChooserBrowse');
        // ---------------------------------------
    }

    protected function _beforeToHtml()
    {
        $this->js->add("AmazonTemplateDescriptionCategoryChooserObj.renderTopLevelCategories('chooser_browser');");

        if ($this->wizardHelper->isActive(\Ess\M2ePro\Helper\View\Amazon::WIZARD_INSTALLATION_NICK)) {
            return parent::_beforeToHtml();
        }

        $helpBlock = $this->getLayout()->createBlock(
            \Ess\M2ePro\Block\Adminhtml\HelpBlock::class,
            '',
            ['data' => [
            'content' => $this->__(
                'If you cannot find necessary Category, try to
                <a href="javascript:void(0)"
                   onclick="AmazonTemplateDescriptionCategoryChooserObj.refreshAmazonCategories()">
                Update Amazon Marketplaces data</a>.'
            ),
            'no_collapse' => true,
            ]]
        );

        $this->setChild('help_block', $helpBlock);

        return parent::_beforeToHtml();
    }
}
