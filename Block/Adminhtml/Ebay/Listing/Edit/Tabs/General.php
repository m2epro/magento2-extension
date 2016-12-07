<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Edit\Tabs;

class General extends \Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('ebayListingTemplateEditTabsGeneral');
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
                '<p>You should specify settings for Payment, Shipping and Return configurations for current
                Listing.</p><br>
                More detailed information you can find <a href="%url%" target="_blank" class="external-link">here</a>.',
                $this->getHelper('Module\Support')->getDocumentationArticleUrl('x/7QItAQ')
            )
        ]);
        $this->setChild('help', $helpBlock);
        // ---------------------------------------

        // ---------------------------------------
        $parameters = [
            'template_nick' => \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_PAYMENT,
            'policy_localization' => $this->getData('policy_localization')
        ];
        $switcherBlock = $this->createBlock('Ebay\Listing\Template\Switcher','',['data' => $parameters]);

        $this->setChild('payment', $switcherBlock);
        // ---------------------------------------

        // ---------------------------------------
        $parameters = [
            'template_nick' => \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_SHIPPING,
            'policy_localization' => $this->getData('policy_localization')
        ];
        $switcherBlock = $this->createBlock('Ebay\Listing\Template\Switcher','', ['data' => $parameters]);

        $this->setChild('shipping', $switcherBlock);
        // ---------------------------------------

        // ---------------------------------------
        $parameters = [
            'template_nick' => \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_RETURN_POLICY,
            'policy_localization' => $this->getData('policy_localization')
        ];
        $switcherBlock = $this->createBlock('Ebay\Listing\Template\Switcher','',['data' => $parameters]);

        $this->setChild('return', $switcherBlock);
        // ---------------------------------------

        return $this;
    }

    //########################################

    protected function _toHtml()
    {
        return parent::_toHtml()
            . $this->getChildHtml('help')
            . $this->getChildHtml('payment')
            . $this->getChildHtml('shipping')
            . $this->getChildHtml('return')
        ;
    }

    //########################################
}