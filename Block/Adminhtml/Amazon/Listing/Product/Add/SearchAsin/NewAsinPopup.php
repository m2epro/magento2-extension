<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Product\Add\SearchAsin;

class NewAsinPopup extends \Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock
{
    /** @var \Ess\M2ePro\Helper\Module\Support */
    private $supportHelper;

    /**
     * @param \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context
     * @param \Ess\M2ePro\Helper\Module\Support $supportHelper
     * @param array $data
     */
    public function __construct(
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Ess\M2ePro\Helper\Module\Support $supportHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->supportHelper = $supportHelper;
    }

    /**
     * @return void
     */
    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('searchAsinNewAsinPopup');
        // ---------------------------------------

        $this->setTemplate('amazon/listing/product/add/search_asin/new_asin_popup.phtml');
    }

    protected function _prepareLayout()
    {
        $helpBlock = $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\HelpBlock::class)->setData([
            'no_collapse' => true,
            'no_hide' => true,
            'style' => 'margin-bottom: 0px',
            'content' => <<<HTML
<h3>{$this->__(
                'Do you want to create New Amazon Products for Magento Products
                which do not have ASIN/ISBN assigned? '
            )}</h3>
<br/>
<p>{$this->__(
                'Not for all Magento Products ASIN/ISBN was found in Amazon Catalog.
                If you want M2E Pro can create New ASIN(s)/ISBN(s) for such Magento Products. <br/><br/>
                <b>Note:</b> You can use the Search of Amazon Products or the feature of Creation of
                New Amazon Products later in M2E Pro Listing.'
            )}</p>

HTML
        ]);

        $this->setChild('popup_help_block', $helpBlock);

        return parent::_prepareLayout();
    }

    /**
     * @return string
     */
    public function getTitleHelpTipsHtml(): string
    {
        $helpLinkBlock = $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\PageHelpLink::class)->setData([
            'page_help_link' => $this->supportHelper->getDocumentationArticleUrl('x/1QkVB')
        ]);

        return $helpLinkBlock->toHtml();
    }
}
