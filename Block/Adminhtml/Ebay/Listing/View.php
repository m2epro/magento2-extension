<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing;

use Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractContainer;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\View
 */
class View extends AbstractContainer
{
    /** @var \Ess\M2ePro\Model\Listing */
    private $listing = null;

    //########################################

    public function _construct()
    {
        parent::_construct();

        $this->listing = $this->getHelper('Data\GlobalData')->getValue('view_listing');

        /** @var \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\View\Switcher $viewModeSwitcher */
        $viewModeSwitcher = $this->createBlock('Ebay_Listing_View_Switcher');

        // Initialization block
        // ---------------------------------------
        $this->setId('ebayListingView');
        $this->_controller = 'adminhtml_ebay_listing_view_' . $viewModeSwitcher->getSelectedParam();
        // ---------------------------------------

        // Set buttons actions
        // ---------------------------------------
        $this->removeButton('add');
        // ---------------------------------------
    }

    protected function _prepareLayout()
    {
        $this->css->addFile('listing/autoAction.css');
        $this->css->addFile('ebay/listing/view.css');

        $this->jsPhp->addConstants(
            $this->getHelper('Data')->getClassConstants(\Ess\M2ePro\Model\Listing::class)
        );
        $this->jsPhp->addConstants($this->getHelper('Data')->getClassConstants(
            '\Ess\M2ePro\Block\Adminhtml\Log\Listing\Product\AbstractGrid'
        ));

        if (!$this->getRequest()->isXmlHttpRequest()) {
            $this->appendHelpBlock([
                'content' => $this->__(
                    '<p>M2E Pro Listing is a group of Magento Products sold on a certain Marketplace
                    from a particular Account. M2E Pro has several options to display the content of
                    Listings referring to different data details. Each of the view options contains a
                    unique set of available Actions accessible in the Mass Actions drop-down.</p>'
                )
            ]);

            $this->setPageActionsBlock(
                'Ebay_Listing_View_Switcher',
                'ebay_listing_view_switcher'
            );
        }

        // ---------------------------------------
        $this->addButton('back', [
            'label'   => $this->__('Back'),
            'onclick' => 'setLocation(\''.$this->getUrl('*/ebay_listing/index') . '\');',
            'class'   => 'back'
        ]);
        // ---------------------------------------

        // ---------------------------------------
        $url = $this->getUrl(
            '*/ebay_log_listing_product',
            [
                \Ess\M2ePro\Block\Adminhtml\Log\Listing\Product\AbstractGrid::LISTING_ID_FIELD =>
                    $this->listing->getId()
            ]
        );
        $this->addButton('view_log', [
            'label'   => $this->__('View Log'),
            'onclick' => 'window.open(\'' . $url . '\',\'_blank\')',
        ]);
        // ---------------------------------------

        // ---------------------------------------
        if ($this->listing->getAccount()->getChildObject()->isPickupStoreEnabled() &&
            $this->listing->getMarketplace()->getChildObject()->isInStorePickupEnabled()) {
            $pickupStoreUrl = $this->getUrl('*/ebay_listing_pickupStore/index', ['id' => $this->listing->getId()]);
            $this->addButton('pickup_store_management', [
                'label' => $this->__('In-Store Pickup'),
                'onclick' => 'window.open(\'' . $pickupStoreUrl . '\',\'_blank\')',
                'class' => 'success primary'
            ]);
        }
        // ---------------------------------------

        // ---------------------------------------
        $this->addButton('edit_templates', [
            'label'   => $this->__('Edit Settings'),
            'onclick' => '',
            'class'   => 'drop_down edit_default_settings_drop_down primary',
            'class_name' => 'Ess\M2ePro\Block\Adminhtml\Magento\Button\DropDown',
            'options' => $this->getSettingsButtonDropDownItems()
        ]);
        // ---------------------------------------

        // ---------------------------------------
        $this->addButton('add_products', [
            'id'        => 'add_products',
            'label'     => $this->__('Add Products'),
            'class'     => 'add',
            'button_class' => '',
            'class_name' => 'Ess\M2ePro\Block\Adminhtml\Magento\Button\DropDown',
            'options' => $this->getAddProductsDropDownItems(),
        ]);
        // ---------------------------------------

        return parent::_prepareLayout();
    }

    //########################################

    protected function _toHtml()
    {
        return '<div id="listing_view_progress_bar"></div>' .
            '<div id="listing_container_errors_summary" class="errors_summary" style="display: none;"></div>' .
            '<div id="listing_view_content_container">' .
            parent::_toHtml() .
            '</div>';
    }

    //########################################

    public function getGridHtml()
    {
        if ($this->getRequest()->isXmlHttpRequest()) {
            return parent::getGridHtml();
        }

        $html = '';

        // ---------------------------------------
        $viewHeaderBlock = $this->createBlock('Listing_View_Header', '', [
            'data' => ['listing' => $this->listing]
        ]);
        $viewHeaderBlock->setListingViewMode(true);
        // ---------------------------------------

        /** @var $helper \Ess\M2ePro\Helper\Data */
        $helper = $this->getHelper('Data');

        // ---------------------------------------

        $this->jsUrl->addUrls(array_merge(
            [],
            $helper->getControllerActions(
                'Ebay\Listing',
                ['_current' => true]
            ),
            $helper->getControllerActions(
                'Ebay_Listing_AutoAction',
                ['id' => $this->getRequest()->getParam('id')]
            ),
            ['variationProductManage' =>
                $this->getUrl('*/ebay_listing_variation_product_manage/index')]
        ));
        // ---------------------------------------

        // ---------------------------------------

        $this->jsTranslator->addTranslations([
            'Remove Category' => $this->__('Remove Category'),
            'Add New Group' => $this->__('Add New Group'),
            'Add/Edit Categories Rule' => $this->__('Add/Edit Categories Rule'),
            'Auto Add/Remove Rules' => $this->__('Auto Add/Remove Rules'),
            'Based on Magento Categories' => $this->__('Based on Magento Categories'),
            'You must select at least 1 Category.' => $this->__('You must select at least 1 Category.'),
            'Rule with the same Title already exists.' => $this->__('Rule with the same Title already exists.'),
            'Compatibility Attribute' => $this->__('Compatibility Attribute'),
            'Sell on Another Marketplace' => $this->__('Sell on Another Marketplace'),
            'Product' => $this->__('Product'),
            'Translation Service' => $this->__('Translation Service'),
            'You must select at least 1 Listing.' => $this->__('You must select at least 1 Listing.'),
            'Data migration.' => $this->__('Data migration...'),
            'Creating Policies in process. Please wait...' =>
                $this->__('Creating Policies in process. Please wait...'),
            'Creating Translation Account in process. Please wait...' =>
                $this->__('Creating Translation Account in process. Please wait...'),
            'Creating Listing in process. Please wait...' =>
                $this->__('Creating Listing in process. Please wait...'),
            'Adding Products in process. Please wait...' =>
                $this->__('Adding Products in process. Please wait...'),
            'Products failed to add' => $this->__('Failed Products'),
            'Migration success.' => $this->__('The Products have been successfully added into Destination Listing.'),
            'Migration error.' => $this->__('The Products have not been added into Destination Listing'
                .' because Products with the same Magento Product IDs already exist there.'),
            'Some Products Categories Settings are not set or Attributes for Title or Description are empty.' =>
                $this->__('Some Products Categories Settings are not set'
                    .' or Attributes for Title or Description are empty.'),
            'Another Synchronization Is Already Running.' => $this->__('Another Synchronization Is Already Running.'),
            'Getting information. Please wait ...' => $this->__('Getting information. Please wait ...'),
            'Preparing to start. Please wait ...' => $this->__('Preparing to start. Please wait ...'),
            'Synchronization has successfully ended.' => $this->__('Synchronization has successfully ended.'),
            'Synchronization ended with warnings. <a target="_blank" href="%url%">View Log</a> for details.' =>
                $this->__(
                    'Synchronization ended with warnings. <a target="_blank" href="%url%">View Log</a> for details.'
                ),
            'Synchronization ended with errors. <a target="_blank" href="%url%">View Log</a> for details.' =>
                $this->__(
                    'Synchronization ended with errors. <a target="_blank" href="%url%">View Log</a> for details.'
                )
        ]);
        // ---------------------------------------

        if (!$this->getRequest()->isXmlHttpRequest()) {
            $this->js->add(
                <<<JS
    define('EbayListingAutoActionInstantiation', [
        'M2ePro/Ebay/Listing/AutoAction',
        'extjs/ext-tree-checkbox'
    ], function(){

        window.ListingAutoActionObj = new EbayListingAutoAction();

    });
JS
            );
        }
        // ---------------------------------------

        return $viewHeaderBlock->toHtml() .
            parent::getGridHtml();
    }

    //########################################

    protected function getSettingsButtonDropDownItems()
    {
        $items = [];

        // ---------------------------------------
        $url = $this->getUrl('*/ebay_template/editListing', [
            'id' => $this->listing->getId(),
            'tab' => 'selling'
        ]);
        $items[] = [
            'label' => $this->__('Selling'),
            'onclick' => 'window.open(\'' . $url . '\',\'_blank\');'
        ];
        // ---------------------------------------

        // ---------------------------------------
        $url = $this->getUrl('*/ebay_template/editListing', [
            'id' => $this->listing->getId(),
            'tab' => 'synchronization'
        ]);
        $items[] = [
            'label' => $this->__('Synchronization'),
            'onclick' => 'window.open(\'' . $url . '\',\'_blank\');'
        ];
        // ---------------------------------------

        // ---------------------------------------
        $url = $this->getUrl(
            '*/ebay_template/editListing',
            [
                'id' => $this->listing->getId(),
                'tab' => 'general'
            ]
        );
        $items[] = [
            'url' => $url,
            'label' => $this->__('Payment / Shipping'),
            'onclick' => 'window.open(\'' . $url . '\',\'_blank\');',
            'target' => '_blank'
        ];
        // ---------------------------------------

        // ---------------------------------------
        $items[] = [
            'onclick' => 'ListingAutoActionObj.loadAutoActionHtml();',
            'label' => $this->__('Auto Add/Remove Rules')
        ];
        // ---------------------------------------

        return $items;
    }

    //########################################

    public function getAddProductsDropDownItems()
    {
        $items = [];

        // ---------------------------------------
        $url = $this->getUrl('*/ebay_listing_product_add', [
            'source' => \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product\Add\SourceMode::MODE_PRODUCT,
            'clear' => true,
            'id' => $this->listing->getId()
        ]);
        $items[] = [
            'label' => $this->__('From Products List'),
            'onclick' => "setLocation('" . $url . "')",
            'default' => true
        ];
        // ---------------------------------------

        // ---------------------------------------
        $url = $this->getUrl('*/ebay_listing_product_add', [
            'source' => \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product\Add\SourceMode::MODE_CATEGORY,
            'clear' => true,
            'id' => $this->listing->getId()
        ]);
        $items[] = [
            'label' => $this->__('From Categories'),
            'onclick' => "setLocation('" . $url . "')"
        ];
        // ---------------------------------------

        return $items;
    }

    //########################################
}
