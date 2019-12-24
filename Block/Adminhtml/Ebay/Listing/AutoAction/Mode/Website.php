<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\AutoAction\Mode;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\AutoAction\Mode\Website
 */
class Website extends \Ess\M2ePro\Block\Adminhtml\Listing\AutoAction\Mode\Website
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('ebayListingAutoActionModeWebsite');
        // ---------------------------------------

        $this->jsPhp->addConstants(
            $this->getHelper('Data')->getClassConstants(\Ess\M2ePro\Model\Ebay\Listing::class)
        );
    }

    //########################################

    protected function _prepareForm()
    {
        parent::_prepareForm();
        $form = $this->getForm();

        $autoGlobalAddingMode = $form->getElement('auto_website_adding_mode');
        $autoGlobalAddingMode->addElementValues([
            \Ess\M2ePro\Model\Ebay\Listing::ADDING_MODE_ADD_AND_ASSIGN_CATEGORY => $this->__(
                'Add to the Listing and Assign eBay Category'
            )
        ]);

        return $this;
    }

    //########################################

    protected function _afterToHtml($html)
    {
        $this->jsPhp->addConstants(
            $this->getHelper('Data')->getClassConstants(\Ess\M2ePro\Model\Ebay\Listing::class)
        );

        return parent::_afterToHtml($html);
    }

    protected function _toHtml()
    {
        $helpBlock = $this->createBlock('HelpBlock')->setData(
            [
                'content' => $this->__(
                    '<p>These Rules of automatic product adding and removal come into action when a
                    Magento Product is added to the Website with regard to the Store View selected for
                    the M2E Pro Listing. In other words, after a Magento Product is added to the
                    selected Website, it can be automatically added to M2E Pro Listing if the settings
                    are enabled.</p><br>
                    <p>Accordingly, if a Magento Product present in the M2E Pro Listing is removed from
                    the Website, the Item will be removed from the Listing and its sale will be
                    stopped on Channel.</p><br>
                    <p>More detailed information you can find
                    <a href="%url%" target="_blank" class="external-link">here</a>.</p>',
                    $this->getHelper('Module\Support')->getDocumentationArticleUrl('x/_wItAQ')
                )
            ]
        );

        $breadcrumb = $this->createBlock('Ebay_Listing_AutoAction_Mode_Breadcrumb', '', ['data' => [
            'id_prefix' => 'website'
        ]]);
        $breadcrumb->setSelectedStep(1);

        return $helpBlock->toHtml() . $breadcrumb->toHtml() . parent::_toHtml();
    }

    //########################################
}
