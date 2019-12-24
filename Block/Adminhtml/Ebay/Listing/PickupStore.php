<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing;

use Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractContainer;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\PickupStore
 */
class PickupStore extends AbstractContainer
{
    protected $localeResolver;
    protected $listing;

    //########################################

    public function __construct(
        \Magento\Framework\Locale\ResolverInterface $localeResolver,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Widget $context,
        array $data = []
    ) {
        $this->localeResolver = $localeResolver;
        parent::__construct($context, $data);
    }

    //########################################

    public function _construct()
    {
        parent::_construct();

        $this->listing = $this->getHelper('Data\GlobalData')->getValue('temp_data');

        // Initialization block
        // ---------------------------------------
        $this->setId('ebayListingPickupStore'.$this->listing->getId());
        $this->_controller = 'adminhtml_ebay_listing_pickupStore';
        // ---------------------------------------

        // Set buttons actions
        // ---------------------------------------
        $this->removeButton('back');
        $this->removeButton('reset');
        $this->removeButton('delete');
        $this->removeButton('add');
        $this->removeButton('save');
        $this->removeButton('edit');
        // ---------------------------------------

        $isExistsPickupStores = $this->activeRecordFactory->getObject('Ebay_Account_PickupStore')
            ->getCollection()
            ->addFieldToFilter('account_id', $this->listing->getAccountId())
            ->addFieldToFilter('marketplace_id', $this->listing->getMarketplaceId())
            ->getSize();

        // ---------------------------------------
        $backUrl = $this->getUrl('*/ebay_listing/view', [
            'id' => $this->listing->getId()
        ]);
        $this->addButton('back', [
            'label'     => $this->getBackButtonLabel(),
            'onclick'   => 'setLocation(\'' . $backUrl .'\')',
            'class'     => 'back',
        ]);
        // ---------------------------------------

        // ---------------------------------------
        $url = $this->getUrl(
            '*/ebay_account_pickupStore/new',
            ['account_id' => $this->listing->getAccountId()]
        );
        $currentUrl = $this->getUrl('*/*/*', ['_current' => true]);
        $callback = 'var newPickupStore = window.open(\'' . $url . '\',\'_blank\');';

        if (!$isExistsPickupStores) {
            $callback .= 'var tmpInterval = setInterval(function() {
                          if (newPickupStore.closed) {
                              clearInterval(tmpInterval);
                              setLocation(\''.$currentUrl.'\');
                          }
                      }, 300);';
            $this->addButton('create_new_store', [
                'label'   => $this->__('Create New Store'),
                'onclick' => $callback,
                'class'   => 'add primary'
            ]);
        } else {
            $locale = $this->localeResolver->getLocale();
            $myStoresUrl = $this->getUrl('*/ebay_account_pickupStore/index', [
                'account_id' => $this->listing->getAccountId(),
                'filter' => base64_encode(http_build_query([
                    'marketplace_id' => $this->listing->getMarketplaceId(),
                    'create_date[locale]' => $locale,
                    'update_date[locale]' => $locale
                ]))
            ]);
            $this->addButton('my_stores', [
                'label' => $this->__('My Stores'),
                'class' => 'scalable button_link',
                'onclick' => 'window.open(\''.$myStoresUrl.'\',\'_blank\');'
            ]);
            $this->addButton('add_products_to_stores', [
                'label'     => $this->__('Assign Products to Stores'),
                'onclick'   => 'EbayListingPickupStoreGridObj.pickupStoreStepProducts('
                    .$this->listing->getId().')',
                'class'     => 'add primary'
            ]);
        }
        // ---------------------------------------
    }

    //########################################

    protected function _prepareLayout()
    {
        if (!$this->getRequest()->isXmlHttpRequest()) {
            $this->appendHelpBlock([
                'content' => $this->__(
                    'In this section, you can <strong>review</strong> Store and Product details as well as Product
                    Quantity and Logs.<br/>
                    Press <strong>Assign Products to Stores</strong> button to add new Products to the selected Store
                    for In-Store Pickup Service.<br/>
                    If you want to <strong>unassign</strong> the Product from the Store you can use a
                    <strong>Unassign Option</strong> from the Actions bulk at the top of the Grid.'
                )
            ]);
        }

        return parent::_prepareLayout();
    }

    //########################################

    public function getGridHtml()
    {
        if ($this->getRequest()->isXmlHttpRequest()) {
            return parent::getGridHtml();
        }

        // ---------------------------------------
        $viewHeaderBlock = $this->createBlock('Listing_View_Header', '', [
            'data' => ['listing' => $this->listing]
        ]);
        // ---------------------------------------

        return $viewHeaderBlock->toHtml() . parent::getGridHtml();
    }

    //########################################

    protected function _toHtml()
    {
        return '<div id="pickup_store_view_progress_bar"></div>' .
        '<div id="pickup_store_container_errors_summary" class="errors_summary" style="display: none;"></div>' .
        '<div id="pickup_store_view_content_container">' .
        parent::_toHtml() .
        '</div>';
    }

    //########################################
}
