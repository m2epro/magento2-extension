<?php

declare(strict_types=1);

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Edit\Tab;

use Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Edit\Tabs;

class Categories extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractContainer
{
    protected Tabs $tabs;
    private \Ess\M2ePro\Helper\Data $dataHelper;
    protected \Ess\M2ePro\Model\ListingFactory $listingFactory;

    public function __construct(
        \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Edit\Tabs $tabs,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Widget $context,
        \Ess\M2ePro\Helper\Data $dataHelper,
        \Ess\M2ePro\Model\ListingFactory $listingFactory,
        array $data = []
    ) {
        $this->listingFactory = $listingFactory;
        $this->dataHelper = $dataHelper;
        $this->tabs = $tabs;
        parent::__construct($context, $data);
    }

    public function _construct()
    {
        parent::_construct();

        $this->setId('ebayListingEdit');

        $this->removeButton('delete');
        $this->removeButton('back');
        $this->removeButton('reset');
        $this->removeButton('save');

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
        $url = $this->getUrl('*/ebay_listing_edit/saveCategory', ['_current' => true]);
        $this->addButton('save', [
            'label' => $this->__('Save'),
            'class' => 'action-primary',
            'onclick' => "
        var customActionUrl = '{$url}';
        $('edit_form').setAttribute('action', customActionUrl);
        $('edit_form').submit();
    ",
        ]);
    }

    protected function _prepareLayout()
    {
        $formBlock = $this->getLayout()->createBlock(
            \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Wizard\Category\ModeSame\Form::class,
            '',
            [
                'selectedMode' => $this->getMode(),
                'blockTitle' => (string)__('To change the eBay category settings mode for this Listing, ' .
                    'please click one of the available options below and save:'),
            ]
        );
        $this->setChild('form', $formBlock);

        return parent::_prepareLayout();
    }

    protected function getListing(): \Ess\M2ePro\Model\Listing
    {
        return $this->listingFactory->create()->load($this->getRequest()->getParam('id'));
    }

    protected function getMode()
    {
        $mode = $this->getListing()->getChildObject()->getAddProductMode();
        if (empty($mode)) {
            $mode = \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Wizard\SelectMode::MODE_SAME;
        }

        return $mode;
    }

    protected function _toHtml()
    {
        $tabsBlock = $this->getLayout()->createBlock(Tabs::class);
        $tabsBlock->activateCategoriesTab();
        $tabsBlockHtml = $tabsBlock->toHtml();
        $viewHeaderBlock = $this->getLayout()->createBlock(
            \Ess\M2ePro\Block\Adminhtml\Listing\View\Header::class,
            '',
            ['data' => ['listing' => $this->getListing()]]
        );

        return $viewHeaderBlock->toHtml()
            . $tabsBlockHtml
            . parent::_toHtml();
    }
}
