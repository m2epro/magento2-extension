<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\AutoAction\Mode;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\AutoAction\Mode\GlobalMode
 */
class GlobalMode extends \Ess\M2ePro\Block\Adminhtml\Listing\AutoAction\Mode\GlobalMode
{

    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('ebayListingAutoActionModeGlobal');
        // ---------------------------------------
    }

    //########################################

    protected function _prepareForm()
    {
        parent::_prepareForm();
        $form = $this->getForm();

        $autoGlobalAddingMode = $form->getElement('auto_global_adding_mode');
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
                    '<p>These Rules of the automatic product adding and removal act globally for all Magento Catalog.
                    When a new Magento Product is added to Magento Catalog, it will be automatically added to
                    the current M2E Pro Listing if the settings are enabled.</p><br>
                    <p>Accordingly, if a Magento Product present in the M2E Pro Listing is removed from
                    Magento Catalog,  the Item will be removed from the Listing and its sale
                    will be stopped on Channel.</p><br>
                    <p>More detailed information you can find
                    <a href="%url%" target="_blank" class="external-link">here</a>.</p>',
                    $this->getHelper('Module\Support')->getDocumentationArticleUrl('x/_QItAQ')
                )
            ]
        );

        $breadcrumb = $this->createBlock('Ebay_Listing_AutoAction_Mode_Breadcrumb', '', ['data' => [
            'id_prefix' => 'global'
        ]]);
        $breadcrumb->setSelectedStep(1);

        return $helpBlock->toHtml() . $breadcrumb->toHtml() . parent::_toHtml();
    }

    //########################################
}
