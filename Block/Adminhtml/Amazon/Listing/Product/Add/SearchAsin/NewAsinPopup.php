<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Product\Add\SearchAsin;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Product\Add\SearchAsin\NewAsinPopup
 */
class NewAsinPopup extends \Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('searchAsinNewAsinPopup');
        // ---------------------------------------

        $this->setTemplate('amazon/listing/product/add/search_asin/new_asin_popup.phtml');
    }

    //########################################

    protected function _prepareLayout()
    {
        $helpBlock = $this->createBlock('HelpBlock')->setData([
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

    //########################################

    public function getTitleHelpTipsHtml()
    {
        $helpLinkBlock = $this->createBlock('PageHelpLink')->setData([
            'page_help_link' => $this->getHelper('Module\Support')->getDocumentationArticleUrl(
                'x/SwctAQ'
            )
        ]);

        return $helpLinkBlock->toHtml();
    }

    //########################################
}
