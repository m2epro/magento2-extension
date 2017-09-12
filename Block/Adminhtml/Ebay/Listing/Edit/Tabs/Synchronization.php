<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Edit\Tabs;

class Synchronization extends \Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('ebayListingTemplateEditTabsSynchronization');
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
                '<p>You should configure rules for the automatic data update between a Magento Product and an eBay
                Item. More detailed information you can find <a href="%url%" target="_blank">here</a>.</p>',
                $this->getHelper("Module\\Support")->getDocumentationArticleUrl("x/OwItAQ")
            )
        ]);
        $this->setChild('help', $helpBlock);

        // ---------------------------------------
        $data = array(
            'template_nick' => \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_SYNCHRONIZATION,
        );
        $switcherBlock = $this->createBlock('Ebay\Listing\Template\Switcher');
        $switcherBlock->setData($data);

        $this->setChild('synchronization', $switcherBlock);
        // ---------------------------------------

        return $this;
    }

    //########################################

    protected function _toHtml()
    {
        $this->css->add(<<<CSS
#template_synchronization_buttons_container {
    margin-top: 15px;
}
CSS
        );

        return parent::_toHtml()
            . $this->getChildHtml('help')
            . $this->getChildHtml('synchronization')
        ;
    }

    //########################################
}