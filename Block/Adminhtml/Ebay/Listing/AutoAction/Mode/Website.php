<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\AutoAction\Mode;

class Website extends \Ess\M2ePro\Block\Adminhtml\Listing\AutoAction\Mode\AbstractWebsite
{
    /** @var \Ess\M2ePro\Helper\Module\Support */
    private $supportHelper;

    public function __construct(
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Ess\M2ePro\Helper\Module\Support $supportHelper,
        \Ess\M2ePro\Helper\Data $dataHelper,
        \Ess\M2ePro\Helper\Data\GlobalData $globalDataHelper,
        \Ess\M2ePro\Helper\Magento\Store $magentoStoreHelper,
        array $data = []
    ) {
        $this->supportHelper = $supportHelper;
        parent::__construct(
            $context,
            $registry,
            $formFactory,
            $dataHelper,
            $globalDataHelper,
            $magentoStoreHelper,
            $data
        );
    }

    public function _construct()
    {
        parent::_construct();

        $this->setId('ebayListingAutoActionModeWebsite');
    }

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
                    '<p>These Rules of automatic product adding and removal come into action when a Magento Product is
                    added to the Website with regard to the Store View selected for the M2E Pro Listing. In other
                    words, after a Magento Product is added to the selected Website, it can be automatically added to
                    M2E Pro Listing if the settings are enabled.</p><br>
                    <p>Please note if a product is already presented in another M2E Pro Listing with the related
                    Channel account and marketplace, the Item won’t be added to the Listing to prevent listing
                    duplicates on the Channel.</p><br>
                    <p>Accordingly, if a Magento Product presented in the M2E Pro Listing is removed from the Website,
                    the Item will be removed from the Listing and its sale will be stopped on Channel.</p><br>
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
