<?php

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Edit\Tab;

use Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Edit\Tabs;

class General extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractContainer
{
    protected $tabs;
    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;
    /** @var \Ess\M2ePro\Model\ListingFactory */
    protected $listingFactory;
    public function __construct(
        \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Edit\Tabs $tabs,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Widget $context,
        \Ess\M2ePro\Helper\Data $dataHelper,
        \Ess\M2ePro\Model\ListingFactory $listingFactory,
        array $data = []
    ) {
        $this->listingFactory = $listingFactory;
        $this->tabs = $tabs;
        $this->dataHelper = $dataHelper;
        parent::__construct($context, $data);
    }
    public function _construct()
    {
        parent::_construct();

        $this->setId('ebayListingEditPolicy');
        $this->_controller = 'adminhtml_ebay_listing';
        $this->_mode = 'edit';

        $this->removeButton('reset');
        $this->removeButton('delete');
        $this->removeButton('back');

        $url = $this->dataHelper->getBackUrl();
        $this->addButton(
            'back',
            [
                'label' => $this->__('Back'),
                'onclick' => "setLocation('" . $url . "')",
                'class' => 'back',
            ]
        );

        $this->addButton(
            'auto_action',
            [
                'label' => $this->__('Auto Add/Remove Rules'),
                'onclick' => 'ListingAutoActionObj.loadAutoActionHtml();',
                'class' => 'action-primary',
            ]
        );
    }
    protected function _prepareLayout()
    {
        $this->css->addFile('listing/autoAction.css');

        $this->jsPhp->addConstants(
            $this->dataHelper->getClassConstants(\Ess\M2ePro\Model\Listing::class)
        );

        $this->jsUrl->addUrls(
            $this->dataHelper->getControllerActions(
                'Ebay_Listing_AutoAction',
                ['listing_id' => $this->getListing()->getId()]
            )
        );

        $this->jsTranslator->addTranslations(
            [
                'Remove Category' => $this->__('Remove Category'),
                'Add New Rule' => $this->__('Add New Rule'),
                'Add/Edit Categories Rule' => $this->__('Add/Edit Categories Rule'),
                'Auto Add/Remove Rules' => $this->__('Auto Add/Remove Rules'),
                'Based on Magento Categories' => $this->__('Based on Magento Categories'),
                'You must select at least 1 Category.' => $this->__('You must select at least 1 Category.'),
                'Rule with the same Title already exists.' => $this->__('Rule with the same Title already exists.'),
                'Compatibility Attribute' => $this->__('Compatibility Attribute'),
                'Sell on Another Marketplace' => $this->__('Sell on Another Marketplace'),
                'Create new' => $this->__('Create new'),
            ]
        );

        $this->js->addOnReadyJs(
            <<<JS
    require([
        'M2ePro/Ebay/Listing/AutoAction'
    ], function(){
        window.ListingAutoActionObj = new EbayListingAutoAction();
    });
JS
        );

        return parent::_prepareLayout();
    }

    protected function getListing()
    {
        $id = $this->getRequest()->getParam('id');
        if ($id) {
            $listing = $this->listingFactory->create()->load($id);
        }

        return $listing;
    }

    protected function _toHtml()
    {
        /** @var \Ess\M2ePro\Model\Listing $listing */
        $listing = $this->parentFactory->getCachedObjectLoaded(
            \Ess\M2ePro\Helper\Component\Ebay::NICK,
            'Listing',
            $this->getRequest()->getParam('id')
        );

        /** @var \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Edit\Tabs $tabsBlock */
        $tabsBlock = $this->getLayout()->createBlock(Tabs::class);
        $tabsBlock->activateGeneralTab();
        $tabsBlockHtml = $tabsBlock->toHtml();
        $viewHeaderBlock = $this->getLayout()->createBlock(
            \Ess\M2ePro\Block\Adminhtml\Listing\View\Header::class,
            '',
            ['data' => ['listing' => $this->getListing()]]
        );

        return $viewHeaderBlock->toHtml() . $tabsBlockHtml . parent::_toHtml();
    }
}
