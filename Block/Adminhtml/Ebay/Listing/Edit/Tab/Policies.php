<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Edit\Tab;

use Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Edit\Tabs;

class Policies extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractContainer
{
    /** @var \Ess\M2ePro\Model\Listing */
    protected $listing;

    /** @var \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory */
    protected $ebayFactory;

    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Widget $context,
        \Ess\M2ePro\Helper\Data $dataHelper,
        array $data = []
    ) {
        $this->ebayFactory = $ebayFactory;
        $this->dataHelper = $dataHelper;
        parent::__construct($context, $data);
    }

    //########################################

    public function _construct()
    {
        parent::_construct();

        $this->setId('ebayListingEditPolicy');
        $this->_controller = 'adminhtml_ebay_listing';
        $this->_mode = 'create_templates';

        $this->removeButton('back');
        $this->removeButton('reset');
        $this->removeButton('delete');
        $this->removeButton('add');
        $this->removeButton('save');
        $this->removeButton('edit');

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

        $backUrl = $this->dataHelper->getBackUrlParam('list');

        $url = $this->getUrl(
            '*/ebay_listing/save',
            [
                'id' => $this->getListing()->getId(),
                'back' => $backUrl,
            ]
        );

        $editBackUrl = $this->dataHelper->makeBackUrlParam(
            $this->getUrl(
                '*/ebay_listing/edit',
                [
                    'id' => $this->listing['id'],
                    'back' => $backUrl,
                ]
            )
        );
        $url = $this->getUrl(
            '*/ebay_listing/save',
            [
                'id' => $this->listing['id'],
                'back' => $editBackUrl,
            ]
        );
        $saveButton = [
            'id' => 'save_and_continue',
            'label' => $this->__('Save'),
            'onclick' => 'EbayListingSettingsObj.saveAndEditClick(\'' . $url . '\', 1)',
            'class' => 'save primary',
        ];

        $this->addButton('save', $saveButton);
    }

    //########################################

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

    //########################################

    protected function getListing()
    {
        if ($this->listing === null && $this->getRequest()->getParam('id')) {
            $is = $this->getRequest()->getParam('id');
            $this->listing = $this->ebayFactory->getCachedObjectLoaded(
                'Listing',
                $this->getRequest()->getParam('id')
            );
        }

        return $this->listing;
    }
    protected function _toHtml()
    {

        /** @var \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Edit\Tabs $tabsBlock */
        $tabsBlock = $this->getLayout()->createBlock(Tabs::class);
        $tabsBlock->activatePoliciesTab();
        $tabsBlockHtml = $tabsBlock->toHtml();
        $viewHeaderBlock = $this->getLayout()->createBlock(
            \Ess\M2ePro\Block\Adminhtml\Listing\View\Header::class,
            '',
            [
                'data' => ['listing' => $this->getListing()],
            ]
        );

        return $viewHeaderBlock->toHtml() . $tabsBlockHtml . parent::_toHtml();
    }

    //########################################
}
