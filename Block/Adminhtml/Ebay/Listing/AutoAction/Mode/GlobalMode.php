<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\AutoAction\Mode;

class GlobalMode extends \Ess\M2ePro\Block\Adminhtml\Listing\AutoAction\Mode\AbstractGlobalMode
{
    /** @var \Ess\M2ePro\Helper\Module\Support */
    private $supportHelper;

    public function __construct(
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Ess\M2ePro\Helper\Module\Support $supportHelper,
        \Ess\M2ePro\Helper\Data $dataHelper,
        array $data = []
    ) {
        $this->supportHelper = $supportHelper;
        parent::__construct($context, $registry, $formFactory, $dataHelper, $data);
    }

    public function _construct()
    {
        parent::_construct();
        $this->setId('ebayListingAutoActionModeGlobal');
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
            $this->dataHelper->getClassConstants(\Ess\M2ePro\Model\Ebay\Listing::class)
        );

        return parent::_afterToHtml($html);
    }

    protected function _toHtml()
    {
        $helpBlock = $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\HelpBlock::class)->setData(
            [
                'content' => $this->__(
                    '<p>These Rules of the automatic product adding and removal act globally for all Magento Catalog.
                    When a new Magento Product is added to Magento Catalog, it will be automatically added to the
                    current M2E Pro Listing if the settings are enabled.</p><br>
                    <p>Please note if a product is already presented in another M2E Pro Listing with the related Channel
                    account and marketplace, the Item won’t be added to the Listing to prevent listing duplicates on
                    the Channel.</p><br>
                    <p>Accordingly, if a Magento Product presented in the M2E Pro Listing is removed from Magento
                    Catalog, the Item will be removed from the Listing and its sale will be stopped on Channel.</p><br>
                    <p>More detailed information you can find
                    <a href="%url%" target="_blank" class="external-link">here</a>.</p>',
                    $this->supportHelper->getDocumentationArticleUrl('x/uv8UB')
                )
            ]
        );

        return $helpBlock->toHtml() .
            parent::_toHtml() .
            '<div id="ebay_category_chooser"></div>';
    }
}
