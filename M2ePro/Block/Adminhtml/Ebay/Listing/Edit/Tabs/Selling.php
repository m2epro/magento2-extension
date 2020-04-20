<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Edit\Tabs;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Edit\Tabs\Selling
 */
class Selling extends \Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('ebayListingTemplateEditTabsSelling');
        // ---------------------------------------
    }

    //########################################

    protected function _prepareLayout()
    {
        parent::_prepareLayout();

        // ---------------------------------------
        $helpBlock = $this->createBlock('HelpBlock');
        $helpBlock->setData([
            'content' => $this->__(
                '<p>On this step you should specify main settings according to which your Items will be sold - Price-,
                Quantity-related configurations, etc.</p><br>
                <p>More detailed information you can find
                <a href="%url%" target="_blank" class="external-link">here</a>.</p>',
                $this->getHelper('Module\Support')->getDocumentationArticleUrl('x/9gItAQ')
            )
        ]);
        $this->setChild('help', $helpBlock);
        // ---------------------------------------

        // ---------------------------------------
        $data = [
            'template_nick' => \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_SELLING_FORMAT,
        ];
        $switcherBlock = $this->createBlock('Ebay_Listing_Template_Switcher');
        $switcherBlock->setData($data);

        $this->setChild('selling_format', $switcherBlock);
        // ---------------------------------------

        // ---------------------------------------
        $data = [
            'template_nick' => \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_DESCRIPTION,
        ];
        $switcherBlock = $this->createBlock('Ebay_Listing_Template_Switcher');
        $switcherBlock->setData($data);

        $this->setChild('description', $switcherBlock);
        // ---------------------------------------

        return $this;
    }

    //########################################

    protected function _toHtml()
    {
        return parent::_toHtml()
            . $this->getChildHtml('help')
            . $this->getChildHtml('selling_format')
            . $this->getChildHtml('description');
    }

    //########################################
}
