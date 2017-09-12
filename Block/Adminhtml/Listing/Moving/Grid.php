<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Listing\Moving;

class Grid extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractGrid
{
    protected $storeFactory;

    //########################################

    public function __construct(
        \Magento\Store\Model\StoreFactory $storeFactory,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        array $data = []
    )
    {
        $this->storeFactory = $storeFactory;
        parent::__construct($context, $backendHelper, $data);
    }

    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('listingMovingGrid');
        // ---------------------------------------

        // Set default values
        // ---------------------------------------
        $this->setDefaultSort('product_id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setPagerVisibility(false);
        $this->setDefaultLimit(100);
        $this->setUseAjax(true);
        // ---------------------------------------
    }

    protected function _prepareCollection()
    {
        $componentMode = $this->getHelper('Data\GlobalData')->getValue('componentMode');
        $ignoreListings = (array)$this->getHelper('Data\GlobalData')->getValue('ignoreListings');

        // Update statistic table values
        $this->activeRecordFactory->getObject('Listing')->getResource()->updateStatisticColumns();
        $this->activeRecordFactory->getObject(ucfirst($componentMode).'\Listing')
            ->getResource()->updateStatisticColumns();

        $collection = $this->parentFactory
            ->getObject($componentMode, 'Listing')
            ->getCollection();

        foreach ($ignoreListings as $listingId) {
            $collection->addFieldToFilter('main_table.id', array('neq'=>$listingId));
        }

        $this->addAccountAndMarketplaceFilter($collection);

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('listing_id', array(
            'header'       => $this->__('ID'),
            'align'        => 'right',
            'type'         => 'number',
            'width'        => '75px',
            'index'        => 'id',
            'filter_index' => 'id',
            'frame_callback' => array($this, 'callbackColumnId')
        ));

        $this->addColumn('title', array(
            'header'       => $this->__('Title'),
            'align'        => 'left',
            'type'         => 'text',
            'width'        => '200px',
            'index'        => 'title',
            'filter_index' => 'main_table.title',
            'frame_callback' => array($this, 'callbackColumnTitle')
        ));

        $this->addColumn('store_name', array(
            'header'        => $this->__('Store View'),
            'align'        => 'left',
            'type'         => 'text',
            'width'        => '100px',
            'index'        => 'store_id',
            'filter'    => false,
            'sortable'  => false,
            'frame_callback' => array($this, 'callbackColumnStore')
        ));

        $this->addColumn('products_total_count', array(
            'header'        => $this->__('Total Items'),
            'align'        => 'right',
            'type'         => 'number',
            'width'        => '100px',
            'index'        => 'products_total_count',
            'filter_index' => 'products_total_count',
            'frame_callback' => array($this, 'callbackColumnSourceTotalItems')
        ));

        $this->addColumn('actions', array(
            'header'       => $this->__('Actions'),
            'align'        => 'left',
            'type'         => 'text',
            'width'        => '125px',
            'filter'       => false,
            'sortable'     => false,
            'frame_callback' => array($this, 'callbackColumnActions'),
        ));
    }

    //########################################

    public function callbackColumnId($value, $row, $column, $isExport)
    {
        return $value.'&nbsp;';
    }

    public function callbackColumnTitle($value, $row, $column, $isExport)
    {
        $url = $this->getHelper('View')->getUrl(
            $row, 'listing', 'view', array('id' => $row->getData('id'))
        );
        return '&nbsp;<a href="'.$url.'" target="_blank">'.$value.'</a>';
    }

    public function callbackColumnStore($value, $row, $column, $isExport)
    {
        $storeModel = $this->storeFactory->create()->load($value);
        $website = $storeModel->getWebsite();

        if (!$website) {
            return '';
        }

        $websiteName = $website->getName();

        if (strtolower($websiteName) != 'admin') {
            $storeName = $storeModel->getName();
        } else {
            $storeName = $storeModel->getGroup()->getName();
        }

        return '&nbsp;'.$storeName;
    }

    public function callbackColumnSource($value, $row, $column, $isExport)
    {
        return '&nbsp;'.$value;
    }

    public function callbackColumnSourceTotalItems($value, $row, $column, $isExport)
    {
        return $value.'&nbsp;';
    }

    public function callbackColumnActions($value, $row, $column, $isExport)
    {
        $actions = <<<HTML
&nbsp;<a href="javascript:void(0);" onclick="CommonObj.confirm({
        actions: {
            confirm: function () {
                {$this->getMovingHandlerJs()}.tryToSubmit({$row->getData('id')});
            }.bind(this),
            cancel: function () {
                return false;
            }
        }
    });">{$this->__('Move To This Listing')}</a>
HTML;
        return $actions;
    }

    //########################################

    protected function getHelpBlockHtml()
    {
        $helpBlockHtml = '';

        if ($this->canDisplayContainer()) {

            if ($this->getRequest()->getParam('listing_view', false)) {
                $helpBlockHtml = $this->createBlock('HelpBlock')->setData([
                    'content' => <<<HTML
                <p>You can move Items from one Listing to another one providing that both Listings are created for
                 the same Account and Marketplace. This option is helpful if you would like to change some
                 settings for the Items (e.g. to have listed using another Policy) or  change the Listing structure
                 to affect the number of products placed in one Listing, etc.</p><br>

                <p><strong>Note:</strong> The same Item cannot be placed in the same M2E Pro Listing more than once.
                If you try to move an Item into the Listing where it has already been placed, the action will be
                ignored.</p><br>

                <p>In case you move Items to the Listing with a different Magento Store View, the values will be
                updated in accordance with the new Policy configurations and the Store View Scope. In view of this,
                more values might be updated on Channel.</p>
HTML
                ])->toHtml();
            } else {
                $helpBlockHtml = $this->createBlock('HelpBlock')->setData([
                    'content' => <<<HTML
                <p>Below you can find the list of M2E Pro Listings which were created for the given
                Account and Marketplace.</p><br>

                <p>After you choose an M2E Pro Listing where you would like the 3rd Party Product to be moved,
                click on Move to This Listing link. In case, there is no suitable M2E Pro Listing available,
                press Add New Listing button to create a new one.</p><br>

                <p>Please note that once an Item is Moved into an M2E Pro Listing, the Policy settings used for
                the selected Listing will be applied to it. Thus, the values (Price, Quantity, etc.) of the
                Item on the channel will be synchronized in accordance with the Magento Product values.</p>
HTML
                ])->toHtml();
            }
        }

        return $helpBlockHtml;
    }

    protected function getNewListingUrl()
    {
        $newListingUrl = $this->getUrl('*/amazon_listing_create/index', array(
            'step' => 1,
            'clear' => 1,
            'account_id' => $this->getHelper('Data\GlobalData')->getValue('accountId'),
            'marketplace_id' => $this->getHelper('Data\GlobalData')->getValue('marketplaceId'),
            'creation_mode' => \Ess\M2ePro\Helper\View::LISTING_CREATION_MODE_LISTING_ONLY,
        ));

        return $newListingUrl;
    }

    protected function _toHtml()
    {
        $this->jsUrl->add($this->getNewListingUrl(), 'add_new_listing_url');

        $this->js->add(<<<JS
        var warning_msg_block = $('empty_grid_warning');
            warning_msg_block && warning_msg_block.remove();

            $$('#listingMovingGrid div.grid th').each(function(el) {
                el.style.padding = '2px 4px';
            });

            $$('#listingMovingGrid div.grid td').each(function(el) {
                el.style.padding = '2px 4px';
            });
JS
);

        return $this->getHelpBlockHtml() . parent::_toHtml();
    }

    //########################################

    public function getGridUrl()
    {
        return $this->getData('grid_url');
    }

    public function getRowUrl($row)
    {
        return false;
    }

    //########################################

    protected function addAccountAndMarketplaceFilter($collection)
    {
        $accountId = $this->getHelper('Data\GlobalData')->getValue('accountId');
        $marketplaceId = $this->getHelper('Data\GlobalData')->getValue('marketplaceId');

        $collection->addFieldToFilter('main_table.marketplace_id', $marketplaceId);
        $collection->addFieldToFilter('main_table.account_id', $accountId);
    }

    //########################################
}