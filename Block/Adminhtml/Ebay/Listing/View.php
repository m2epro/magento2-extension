<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing;

use Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractContainer;

class View extends AbstractContainer
{
    const VIEW_MODE_EBAY        = 'ebay';
    const VIEW_MODE_MAGENTO     = 'magento';
    const VIEW_MODE_SETTINGS    = 'settings';
    const VIEW_MODE_TRANSLATION = 'translation';

    const DEFAULT_VIEW_MODE = self::VIEW_MODE_EBAY;

    /** @var \Ess\M2ePro\Model\Listing */
    private $listing = NULL;

    //########################################

    public function _construct()
    {
        parent::_construct();

        $this->listing = $this->getHelper('Data\GlobalData')->getValue('view_listing');

        // Initialization block
        // ---------------------------------------
        $this->setId('ebayListingView');
        $this->_controller = 'adminhtml_ebay_listing_view_' . $this->getViewMode();
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
            $this->getHelper('Data')->getClassConstants('\Ess\M2ePro\Model\Listing')
        );

        if (!$this->getRequest()->isXmlHttpRequest()) {
            
            $this->appendHelpBlock([
                'content' => $this->__(
                    '<p>M2E Pro Listing is a group of Magento Products sold on a certain Marketplace 
                    from a particular Account. M2E Pro has several options to display the content of 
                    Listings referring to different data details. Each of the view options contains a 
                    unique set of available Actions accessible in the Mass Actions drop-down.</p><br>
                    <p>More detailed information you can find <a href="%url%" target="_blank">here</a>.</p>',
                    $this->getHelper('Module\Support')->getDocumentationUrl(NULL, NULL, 'x/eQItAQ')
                )
            ]);

            $this->setPageActionsBlock(
                'Ebay\Listing\View\Switcher',
                'ebay_listing_view_switcher'
            );

            $this->getLayout()->getBlock('ebay_listing_view_switcher')->addData([
                'current_view_mode' => $this->getViewMode()
            ]);
        }

        // ---------------------------------------
        $this->addButton('back', array(
            'label'   => $this->__('Back'),
            'onclick' => 'setLocation(\''.$this->getUrl('*/ebay_listing/index') . '\');',
            'class'   => 'back'
        ));
        // ---------------------------------------

        // ---------------------------------------
        // TODO NOT SUPPORTED FEATURES "PickupStore"
//        if ($this->listing->getAccount()->getChildObject()->isPickupStoreEnabled() &&
//            $this->getHelper('Component\Ebay\PickupStore')->isFeatureEnabled()) {
//            $pickupStoreUrl = $this->getUrl('*/ebay_listing_pickupStore/index', array(
//                'id'   => $this->listing->getId()
//            ));
//            $this->addButton('pickup_store_management', array(
//                'label' => $this->__('In-Store Pickup Management'),
//                'onclick' => 'window.open(\'' . $pickupStoreUrl . '\',\'_current\')',
//                'class' => 'success'
//            ));
//        }
        // ---------------------------------------

        $url = $this->getUrl(
            '*/ebay_listing_log',
            array(
                'id' => $this->listing->getId()
            )
        );
        $this->addButton('view_log', array(
            'label'   => $this->__('View Log'),
            'onclick' => 'window.open(\'' . $url . '\',\'_blank\')',
        ));

        // ---------------------------------------
        $this->addButton('edit_templates', array(
            'label'   => $this->__('Edit Settings'),
            'onclick' => '',
            'class'   => 'drop_down edit_default_settings_drop_down primary',
            'class_name' => 'Ess\M2ePro\Block\Adminhtml\Magento\Button\DropDown',
            'options' => $this->getSettingsButtonDropDownItems()
        ));
        // ---------------------------------------

        // ---------------------------------------
        $this->addButton('add_products', array(
            'id'        => 'add_products',
            'label'     => $this->__('Add Products'),
            'class'     => 'add',
            'button_class' => '',
            'class_name' => 'Ess\M2ePro\Block\Adminhtml\Magento\Button\DropDown',
            'options' => $this->getAddProductsDropDownItems(),
        ));
        // ---------------------------------------

        return parent::_prepareLayout();
    }

    //########################################

    public function getViewMode()
    {
        $allowedModes = array(
            self::VIEW_MODE_EBAY,
            self::VIEW_MODE_MAGENTO,
            self::VIEW_MODE_SETTINGS,
            self::VIEW_MODE_TRANSLATION,
        );
        $mode = $this->getParam('view_mode', self::DEFAULT_VIEW_MODE);

        if (in_array($mode, $allowedModes)) {
            return $mode;
        }

        return self::DEFAULT_VIEW_MODE;
    }

    protected function getParam($paramName, $default = NULL)
    {
        $session = $this->getHelper('Data\Session');
        $sessionParamName = $this->getId() . $this->listing->getId() . $paramName;

        if ($this->getRequest()->has($paramName)) {
            $param = $this->getRequest()->getParam($paramName);
            $session->setValue($sessionParamName, $param);
            return $param;
        } elseif ($param = $session->getValue($sessionParamName)) {
            return $param;
        }

        return $default;
    }

    //########################################

    // TODO NOT SUPPORTED FEATURES "Listing header selector"
//    public function getHeaderHtml()
//    {
//        // ---------------------------------------
//        $collection = Mage::getModel('M2ePro/Listing')->getCollection();
//        $collection->addFieldToFilter('component_mode', Ess_M2ePro_Helper_Component_Ebay::NICK);
//        $collection->addFieldToFilter('id', array('neq' => $this->listing->getId()));
//        $collection->setPageSize(200);
//        $collection->setOrder('title', 'ASC');
//
//        $items = array();
//        foreach ($collection->getItems() as $item) {
//            $items[] = array(
//                'label' => $item->getTitle(),
//                'url' => $this->getUrl('*/*/view', array('id' => $item->getId()))
//            );
//        }
//        // ---------------------------------------
//
//        if (count($items) == 0) {
//            return parent::getHeaderHtml();
//        }
//
//        // ---------------------------------------
//        $data = array(
//            'target_css_class' => 'listing-profile-title',
//            'style' => 'max-height: 120px; overflow: auto; width: 200px;',
//            'items' => $items
//        );
//        $dropDownBlock = $this->getLayout()->createBlock('M2ePro/adminhtml_widget_button_dropDown');
//        $dropDownBlock->setData($data);
//        // ---------------------------------------
//
//        return parent::getHeaderHtml() . $dropDownBlock->toHtml();
//    }

// TODO NOT SUPPORTED FEATURES "Listing header selector"
//    public function getHeaderText()
//    {
//        // ---------------------------------------
//        $changeProfile = $this->__('Change Listing');
//        $headerText = parent::getHeaderText();
//        $listingTitle = Mage::helper('M2ePro')->escapeHtml($this->listing->getTitle());
//        // ---------------------------------------
//
//        return <<<HTML
//{$headerText}&nbsp;
//<a href="javascript: void(0);"
//   id="listing-profile-title"
//   class="listing-profile-title"
//   style="font-weight: bold;"
//   title="{$changeProfile}"><span class="drop_down_header">"{$listingTitle}"</span></a>
//HTML;
//    }

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

        // TODO NOT SUPPORTED FEATURES "Listing header selector"

        // ---------------------------------------
        $viewHeaderBlock = $this->createBlock('Listing\View\Header','', [
            'data' => ['listing' => $this->listing]
        ]);
        $viewHeaderBlock->setListingViewMode(true);
        // ---------------------------------------

        /** @var $helper \Ess\M2ePro\Helper\Data */
        $helper = $this->getHelper('Data');

        // ---------------------------------------

        // TODO NOT SUPPORTED FEATURES
        $this->jsUrl->addUrls(array_merge(
            array(),
            $helper->getControllerActions(
                'Ebay\Listing', array('_current' => true)
            ),
            $helper->getControllerActions(
                'Ebay\Listing\AutoAction', array('id' => $this->getRequest()->getParam('id'))
            ),
//            $helper->getControllerActions(
//                'ebay_listing_transferring', array('listing_id' => $this->getRequest()->getParam('id'))
//            ),
//            $helper->getControllerActions('ebay_account'),
//            $helper->getControllerActions('ebay_listing_product_category_settings'),
//            $helper->getControllerActions('ebay_marketplace'),
//            array('logViewUrl' =>
//                $this->getUrl('*/amazon_listing_log/synchronization',
//                    array('back'=>$helper->makeBackUrlParam('*/common_synchronization/index')))),
//            array('runSynchNow' =>
//                $this->getUrl('*/common_marketplace/runSynchNow')),
//            array('synchCheckProcessingNow' =>
//                $this->getUrl('*/common_synchronization/synchCheckProcessingNow')),
            array('variationProductManage' =>
                $this->getUrl('*/ebay_listing_variation_product_manage/index'))
//            array('getListingProductBids' =>
//                $this->getUrl('*/ebay_listing/getListingProductBids'))
        ));
        // ---------------------------------------

        // ---------------------------------------

        $this->jsTranslator->addTranslations(array(
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
                    'Synchronization ended with warnings. <a target="_blank" href="%url%">View Log</a> for details.'),
            'Synchronization ended with errors. <a target="_blank" href="%url%">View Log</a> for details.' =>
                $this->__(
                    'Synchronization ended with errors. <a target="_blank" href="%url%">View Log</a> for details.')
        ));
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
            array(
                'id' => $this->listing->getId(),
                'tab' => 'general'
            )
        );
        $items[] = array(
            'url' => $url,
            'label' => $this->__('Payment / Shipping'),
            'onclick' => 'window.open(\'' . $url . '\',\'_blank\');',
            'target' => '_blank'
        );
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
        $url = $this->getUrl('*/ebay_listing_product_add',[
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