<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing;

use Ess\M2ePro\Block\Adminhtml\Magento\Renderer;

class Grid extends \Ess\M2ePro\Block\Adminhtml\Listing\Grid
{
    protected $ebayFactory;

    //########################################
    
    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        array $data = []
    )
    {
        $this->ebayFactory = $ebayFactory;
        parent::__construct($context, $backendHelper, $data);
    }

    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('ebayListingGrid');
        // ---------------------------------------
    }

    protected function _prepareCollection()
    {
        // Update statistic table values
        $this->activeRecordFactory->getObject('Listing')->getResource()->updateStatisticColumns();
        $this->activeRecordFactory->getObject('Ebay\Listing')->getResource()->updateStatisticColumns();

        $aTable = $this->activeRecordFactory->getObject('Account')->getResource()->getMainTable();
        $mTable = $this->activeRecordFactory->getObject('Marketplace')->getResource()->getMainTable();

        // Get collection of listings
        $collection = $this->ebayFactory->getObject('Listing')->getCollection();
        $collection->getSelect()->join(
            array('a'=>$aTable),
            '(`a`.`id` = `main_table`.`account_id`)',
            array('account_title'=>'title')
        );
        $collection->getSelect()->join(
            array('m'=>$mTable),
            '(`m`.`id` = `main_table`.`marketplace_id`)',
            array('marketplace_title'=>'title')
        );

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    //########################################

    protected function _prepareLayout()
    {
        $this->css->addFile('ebay/listing/grid.css');

        return parent::_prepareLayout();
    }

    //########################################

    protected function setColumns()
    {
        $this->addColumn('items_sold_count', array(
            'header'    => $this->__('Sold QTY'),
            'align'     => 'right',
            'type'      => 'number',
            'index'     => 'items_sold_count',
            'filter_index' => 'second_table.items_sold_count',
            'frame_callback' => array($this, 'callbackColumnSoldQTY')
        ));

        return $this;
    }

    protected function getColumnActionsItems()
    {
        $backUrl = $this->getHelper('Data')->makeBackUrlParam('*/ebay_listing/index');

        $actions = array(
            'manageProducts' => array(
                'caption' => $this->__('Manage'),
                'group'   => 'products_actions',
                'field'   => 'id',
                'url'     => array(
                    'base'   => '*/ebay_listing/view',
                    'params' => array('id' => $this->getId(), 'back' => $backUrl)
                )
            ),

            'addProductsSourceProducts' => array(
                'caption'        => $this->__('Add From Products List'),
                'group'          => 'products_actions',
                'field'          => 'id',
                'onclick_action' => 'EbayListingGridObj.addProductsSourceProductsAction',
            ),

            'addProductsSourceCategories' => array(
                'caption'        => $this->__('Add From Categories'),
                'group'          => 'products_actions',
                'field'          => 'id',
                'onclick_action' => 'EbayListingGridObj.addProductsSourceCategoriesAction',
            ),

            'autoActions' => array(
                'caption' => $this->__('Auto Add/Remove Rules'),
                'group'   => 'products_actions',
                'field'   => 'id',
                'url'     => array(
                    'base'   => '*/ebay_listing/view',
                    'params' => array('id' => $this->getId(), 'auto_actions' => 1)
                )
            ),

            'viewLogs' => array(
                'caption' => $this->__('View Log'),
                'group'   => 'other',
                'field'   => 'id',
                'url'     => array(
                    'base'   => '*/ebay_listing_log/index',
                    'params' => array('id' => $this->getId())
                )
            ),

            'clearLogs' => array(
                'caption' => $this->__('Clear Log'),
                'confirm' => $this->__('Are you sure?'),
                'group'   => 'other',
                'field'   => 'id',
                'url'     => array(
                    'base' => '*/listing/clearLog',
                    'params' => array(
                        'back' => $backUrl
                    )
                )
            ),

            'delete' => array(
                'caption' => $this->__('Delete Listing'),
                'confirm' => $this->__('Are you sure?'),
                'group'   => 'other',
                'field'   => 'id',
                'url'     => array(
                    'base'   => '*/ebay_listing/delete',
                    'params' => array('id' => $this->getId())
                )
            ),

            'editTitle' => array(
                'caption'        => $this->__('Title'),
                'group'          => 'edit_actions',
                'field'          => 'id',
                'onclick_action' => 'EditListingTitleObj.openPopup',
            ),

            'editSelling' => array(
                'caption' => $this->__('Selling'),
                'group'   => 'edit_actions',
                'field'   => 'id',
                'url'     => array(
                    'base'   => '*/ebay_template/editListing',
                    'params' => array(
                        'id' => $this->getId(),
                        'tab' => 'selling',
                        'back' => $backUrl
                    )
                )
            ),

            'editSynchronization' => array(
                'caption' => $this->__('Synchronization'),
                'group'   => 'edit_actions',
                'field'   => 'id',
                'url'     => array(
                    'base'   => '*/ebay_template/editListing',
                    'params' => array(
                        'id' => $this->getId(),
                        'tab' => 'synchronization',
                        'back' => $backUrl
                    )
                )
            ),

            'editPaymentAndShipping' => array(
                'caption' => $this->__('Payment / Shipping'),
                'group'   => 'edit_actions',
                'field'   => 'id',
                'url'     => array(
                    'base'   => '*/ebay_template/editListing',
                    'params' => array(
                        'id' => $this->getId(),
                        'tab' => 'general',
                        'back' => $backUrl
                    )
                )
            )
        );

        return $actions;
    }

    //########################################

    public function callbackColumnTitle($value, $row, $column, $isExport)
    {
        $value = '<span id="listing_title_'.$row->getId().'">' .
            $this->getHelper('Data')->escapeHtml($value) .
        '</span>';

        /* @var $row \Ess\M2ePro\Model\Listing */
        $accountTitle = $row->getData('account_title');
        $marketplaceTitle = $row->getData('marketplace_title');

        $storeModel = $this->_storeManager->getStore($row->getStoreId());
        $storeView = $this->_storeManager->getWebsite($storeModel->getWebsiteId())->getName();
        if (strtolower($storeView) != 'admin') {
            $storeView .= ' > '.$this->_storeManager->getGroup($storeModel->getStoreGroupId())->getName();
            $storeView .= ' > '.$storeModel->getName();
        } else {
            $storeView = $this->__('Admin (Default Values)');
        }

        $account = $this->__('Account');
        $marketplace = $this->__('Marketplace');
        $store = $this->__('Magento Store View');

        $value .= <<<HTML
<div>
    <span style="font-weight: bold">{$account}</span>: <span style="color: #505050">{$accountTitle}</span><br/>
    <span style="font-weight: bold">{$marketplace}</span>: <span style="color: #505050">{$marketplaceTitle}</span><br/>
    <span style="font-weight: bold">{$store}</span>: <span style="color: #505050">{$storeView}</span>
</div>
HTML;

        return $value;
    }

    public function callbackColumnSoldQTY($value, $row, $column, $isExport)
    {
        return $this->getColumnValue($row->getChildObject()->getItemsSoldCount());
    }

    //########################################

    public function getRowUrl($row)
    {
        return $this->getUrl('*/ebay_listing/view', array(
            'id' => $row->getId()
        ));
    }

    //########################################

    protected function callbackFilterTitle($collection, $column)
    {
        $value = $column->getFilter()->getValue();

        if ($value == null) {
            return;
        }

        $collection->getSelect()->where(
            'main_table.title LIKE ? OR a.title LIKE ? OR m.title LIKE ?',
            '%'.$value.'%'
        );
    }

    //########################################

    protected function _toHtml()
    {
        if ($this->getRequest()->isXmlHttpRequest()) {
            return parent::_toHtml();
        }

        $this->jsUrl->addUrls(array_merge(
            $this->getHelper('Data')->getControllerActions('Ebay\Listing'),
            $this->getHelper('Data')->getControllerActions('Ebay\Listing\Product\Add'),
            //$this->getHelper('Data')->getControllerActions('Ebay\Listing\Product\Add'),
            $this->getHelper('Data')->getControllerActions('Ebay\Listing\Log'),
            $this->getHelper('Data')->getControllerActions('Ebay\Listing\Product\Log'),
            $this->getHelper('Data')->getControllerActions('Ebay\Template')
//            array(
//                'common_listing/editTitle' => $this->getUrl('*/common_listing/editTitle')
//            )
        ));

        $this->jsUrl->add($this->getUrl('*/listing/edit'), 'listing/edit');

        $this->jsTranslator->add('Edit Listing Title', $this->__('Edit Listing Title'));
        $this->jsTranslator->add('Listing Title', $this->__('Listing Title'));
        $this->jsTranslator->add(
            'The specified Title is already used for other Listing. Listing Title must be unique.',
            $this->__(
                'The specified Title is already used for other Listing. Listing Title must be unique.'
            )
        );

        $this->jsPhp->addConstants(
            $this->getHelper('Data')->getClassConstants('\Ess\M2ePro\Helper\Component\Ebay')
        );

        $component = \Ess\M2ePro\Helper\Component\Ebay::NICK;

        $this->js->add(
<<<JS
    require([
        'M2ePro/Ebay/Listing/Grid',
        'M2ePro/Listing/EditTitle'
    ], function(){

        window.EbayListingGridObj = new EbayListingGrid('{$this->getId()}');
        window.EditListingTitleObj = new ListingEditListingTitle('{$this->getId()}', '{$component}');

    });
JS
        );

        return parent::_toHtml();
    }

    //########################################
}