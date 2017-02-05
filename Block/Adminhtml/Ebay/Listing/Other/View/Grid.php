<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Other\View;

class Grid extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractGrid
{
    protected $localeCurrency;
    protected $resourceConnection;
    protected $ebayFactory;

    //########################################

    public function __construct(
        \Magento\Framework\Locale\CurrencyInterface $localeCurrency,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        array $data = []
    )
    {
        $this->localeCurrency = $localeCurrency;
        $this->resourceConnection = $resourceConnection;
        $this->ebayFactory = $ebayFactory;
        parent::__construct($context, $backendHelper, $data);
    }

    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('ebayListingOtherViewGrid');
        // ---------------------------------------

        // Set default values
        // ---------------------------------------
        $this->setDefaultSort('id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
        // ---------------------------------------
    }

    //########################################

    protected function _prepareCollection()
    {
        $collection = $this->ebayFactory->getObject('Listing\Other')->getCollection();

        $collection->getSelect()->joinLeft(
            array('mp' => $this->activeRecordFactory->getObject('Marketplace')->getResource()->getMainTable()),
            'mp.id = main_table.marketplace_id',
            array('marketplace_title' => 'mp.title')
        );

        $collection->getSelect()->joinLeft(
            array('mea' => $this->activeRecordFactory->getObject('Ebay\Account')->getResource()->getMainTable()),
            'mea.account_id = main_table.account_id',
            array('account_mode' => 'mea.mode')
        );

        // Add Filter By Account
        if ($accountId = $this->getRequest()->getParam('account')) {
            $collection->addFieldToFilter('main_table.account_id', $accountId);
        }

        // Add Filter By Marketplace
        if ($marketplaceId = $this->getRequest()->getParam('marketplace')) {
            $collection->addFieldToFilter('main_table.marketplace_id', $marketplaceId);
        }

        $collection->getSelect()->reset(\Zend_Db_Select::COLUMNS);
        $collection->getSelect()->columns(
            array(
                'id'                    => 'main_table.id',
                'account_id'            => 'main_table.account_id',
                'marketplace_id'        => 'main_table.marketplace_id',
                'product_id'            => 'main_table.product_id',
                'title'                 => 'second_table.title',
                'sku'                   => 'second_table.sku',
                'item_id'               => 'second_table.item_id',
                'available_qty'         => new \Zend_Db_Expr(
                    '(second_table.online_qty - second_table.online_qty_sold)'
                ),
                'online_qty_sold'       => 'second_table.online_qty_sold',
                'online_price'          => 'second_table.online_price',
                'status'                => 'main_table.status',
                'start_date'            => 'second_table.start_date',
                'end_date'              => 'second_table.end_date',
                'currency'              => 'second_table.currency',
                'account_mode'          => 'mea.mode'
            )
        );

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('product_id', array(
            'header' => $this->__('Product ID'),
            'align' => 'left',
            'type' => 'number',
            'width' => '80px',
            'index' => 'product_id',
            'filter_index' => 'main_table.product_id',
            'frame_callback' => array($this, 'callbackColumnProductId')
        ));

        $this->addColumn('title', array(
            'header' => $this->__('Title / SKU'),
            'align' => 'left',
            'type' => 'text',
            'index' => 'title',
            'filter_index' => 'second_table.title',
            'frame_callback' => array($this, 'callbackColumnProductTitle'),
            'filter_condition_callback' => array($this, 'callbackFilterTitle')
        ));

        $this->addColumn('item_id', array(
            'header' => $this->__('Item ID'),
            'align' => 'left',
            'width' => '100px',
            'type' => 'text',
            'index' => 'item_id',
            'filter_index' => 'second_table.item_id',
            'frame_callback' => array($this, 'callbackColumnItemId')
        ));

        $this->addColumn('available_qty', array(
            'header' => $this->__('Available QTY'),
            'align' => 'right',
            'width' => '50px',
            'type' => 'number',
            'index' => 'available_qty',
            'filter_index' => new \Zend_Db_Expr('(second_table.online_qty - second_table.online_qty_sold)'),
            'frame_callback' => array($this, 'callbackColumnOnlineAvailableQty')
        ));

        $this->addColumn('online_qty_sold', array(
            'header' => $this->__('Sold QTY'),
            'align' => 'right',
            'width' => '50px',
            'type' => 'number',
            'index' => 'online_qty_sold',
            'filter_index' => 'second_table.online_qty_sold',
            'frame_callback' => array($this, 'callbackColumnOnlineQtySold')
        ));

        $this->addColumn('online_price', array(
            'header' => $this->__('Price'),
            'align' => 'right',
            'width' => '50px',
            'type' => 'number',
            'index' => 'online_price',
            'filter_index' => 'second_table.online_price',
            'frame_callback' => array($this, 'callbackColumnOnlinePrice')
        ));

        $this->addColumn('status', array(
            'header' => $this->__('Status'),
            'width' => '100px',
            'index' => 'status',
            'filter_index' => 'main_table.status',
            'type' => 'options',
            'sortable' => false,
            'options' => array(
                \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED   => $this->__('Listed'),
                \Ess\M2ePro\Model\Listing\Product::STATUS_HIDDEN   => $this->__('Listed (Hidden)'),
                \Ess\M2ePro\Model\Listing\Product::STATUS_SOLD     => $this->__('Sold'),
                \Ess\M2ePro\Model\Listing\Product::STATUS_STOPPED  => $this->__('Stopped'),
                \Ess\M2ePro\Model\Listing\Product::STATUS_FINISHED => $this->__('Finished'),
                \Ess\M2ePro\Model\Listing\Product::STATUS_BLOCKED  => $this->__('Pending')
            ),
            'frame_callback' => array($this, 'callbackColumnStatus')
        ));

        $this->addColumn('end_date', array(
           'header' => $this->__('End Date'),
           'align' => 'right',
           'width' => '150px',
           'type' => 'datetime',
           'filter_time' => true,
           'index' => 'end_date',
           'filter_index' => 'second_table.end_date',
           'frame_callback' => array($this, 'callbackColumnEndTime')
        ));

        $back = $this->getHelper('Data')->makeBackUrlParam('*/ebay_listing_other/view',array(
            'account' => $this->getRequest()->getParam('account'),
            'marketplace' => $this->getRequest()->getParam('marketplace'),
            'back' => $this->getRequest()->getParam('back')
        ));

        return parent::_prepareColumns();
    }

    protected function _prepareMassaction()
    {
        // Set mass-action identifiers
        // ---------------------------------------
        $this->setMassactionIdField('main_table.id');
        $this->getMassactionBlock()->setFormFieldName('ids');
        // ---------------------------------------

        $this->getMassactionBlock()->setGroups(array(
            'mapping' => $this->__('Mapping'),
            'other' => $this->__('Other')
        ));

        $this->getMassactionBlock()->addItem('autoMapping', array(
            'label'   => $this->__('Map Item(s) Automatically'),
            'url'     => '',
            'confirm' => $this->__('Are you sure?')
        ), 'mapping');

        $this->getMassactionBlock()->addItem('moving', array(
            'label'   => $this->__('Move Item(s) to Listing'),
            'url'     => '',
            'confirm' => $this->__('Are you sure?')
        ), 'other');
        $this->getMassactionBlock()->addItem('removing', array(
            'label'   => $this->__('Remove Item(s)'),
            'url'     => '',
            'confirm' => $this->__('Are you sure?')
        ), 'other');
        $this->getMassactionBlock()->addItem('unmapping', array(
            'label'   => $this->__('Unmap Item(s)'),
            'url'     => '',
            'confirm' => $this->__('Are you sure?')
        ), 'mapping');
        // ---------------------------------------

        return parent::_prepareMassaction();
    }

    protected function _prepareLayout()
    {
        $this->css->addFile('listing/other/view/grid.css');

        return parent::_prepareLayout();
    }

    //########################################

    public function callbackColumnProductId($value, $row, $column, $isExport)
    {
        if (empty($value)) {
            $productTitle = $this->getHelper('Data')->escapeHtml($row->getChildObject()->getData('title'));
            $productTitle = $this->getHelper('Data')->escapeJs($productTitle);
            if (strlen($productTitle) > 60) {
                $productTitle = substr($productTitle, 0, 60) . '...';
            }

            $htmlValue = '&nbsp;<a href="javascript:void(0);"
                                    onclick="EbayListingOtherMappingObj.openPopUp(\''.
                                        $productTitle.
                                        '\','.
                                        (int)$row->getId().
                                    ');">' . $this->__('Map') . '</a>';

            if ($this->getHelper('Module')->isDevelopmentMode()) {
                $htmlValue .= '<br/>' . $row->getId();
            }
            return $htmlValue;
        }

        $htmlValue = '&nbsp<a href="'
            .$this->getUrl('catalog/product/edit',
                array('id' => $row->getData('product_id')))
            .'" target="_blank">'
            .$row->getData('product_id')
            .'</a>';

        $htmlValue .= '&nbsp&nbsp&nbsp<a href="javascript:void(0);"'
            .' onclick="EbayListingOtherGridObj.movingHandler.getGridHtml('
            .$this->getHelper('Data')->jsonEncode(array((int)$row->getData('id')))
            .')">'
            .$this->__('Move')
            .'</a>';

        if ($this->getHelper('Module')->isDevelopmentMode()) {
            $htmlValue .= '<br/>' . $row->getId();
        }

        return $htmlValue;
    }

    public function callbackColumnProductTitle($value, $row, $column, $isExport)
    {
        $title = $row->getChildObject()->getData('title');
        $title = '<span>' . $this->getHelper('Data')->escapeHtml($title) . '</span>';

        $tempSku = $row->getChildObject()->getData('sku');

        if (is_null($tempSku)) {
            $tempSku = '<i style="color:gray;">receiving...</i>';
        } elseif ($tempSku == '') {
            $tempSku = '<i style="color:gray;">none</i>';
        } else {
            $tempSku = $this->getHelper('Data')->escapeHtml($tempSku);
        }

        $title .= '<br/><strong>'
                  .$this->__('SKU')
                  .':</strong> '
                  .$tempSku;

        return $title;
    }

    public function callbackColumnItemId($value, $row, $column, $isExport)
    {
        $value = $row->getChildObject()->getData('item_id');
        if (empty($value)) {
            return $this->__('N/A');
        }

        $url = $this->getHelper('Component\Ebay')->getItemUrl($row->getChildObject()->getData('item_id'),
                                                              $row->getData('account_mode'),
                                                              $row->getData('marketplace_id'));
        $value = '<a href="' . $url . '" target="_blank">' . $value . '</a>';

        return $value;
    }

    public function callbackColumnOnlineAvailableQty($value, $row, $column, $isExport)
    {
        $value = $row->getData('available_qty');
        if (is_null($value) || $value === '') {
            return $this->__('N/A');
        }

        if ($value <= 0) {
            return '<span style="color: red;">0</span>';
        }

        if ($row->getData('status') != \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED) {
            return '<span style="color: gray; text-decoration: line-through;">' . $value . '</span>';
        }

        return $value;
    }

    public function callbackColumnOnlineQtySold($value, $row, $column, $isExport)
    {
        $value = $row->getChildObject()->getData('online_qty_sold');
        if (is_null($value) || $value === '') {
            return $this->__('N/A');
        }

        if ($value <= 0) {
            return '<span style="color: red;">0</span>';
        }

        return $value;
    }

    public function callbackColumnOnlinePrice($value, $row, $column, $isExport)
    {
        $value = $row->getChildObject()->getData('online_price');
        if (is_null($value) || $value === '') {
            return $this->__('N/A');
        }

        if ((float)$value <= 0) {
            return '<span style="color: #f00;">0</span>';
        }

        return $this->localeCurrency->getCurrency($row->getChildObject()->getData('currency'))->toCurrency($value);
    }

    public function callbackColumnStatus($value, $row, $column, $isExport)
    {
        $coloredStstuses = [
            \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED => 'green',
            \Ess\M2ePro\Model\Listing\Product::STATUS_HIDDEN => 'red',
            \Ess\M2ePro\Model\Listing\Product::STATUS_SOLD => 'brown',
            \Ess\M2ePro\Model\Listing\Product::STATUS_STOPPED => 'red',
            \Ess\M2ePro\Model\Listing\Product::STATUS_FINISHED => 'blue',
            \Ess\M2ePro\Model\Listing\Product::STATUS_BLOCKED => 'orange'
        ];

        $status = $row->getData('status');

        if ($status !== null && isset($coloredStstuses[$status])) {
            $value = '<span style="color: '.$coloredStstuses[$status].';">' . $value . '</span>';
        }

        return $value.$this->getViewLogIconHtml($row->getId()).$this->getLockedTag($row);
    }

    public function callbackColumnStartTime($value, $row, $column, $isExport)
    {
        if (empty($value)) {
            return $this->__('N/A');
        }

        return $value;
    }

    public function callbackColumnEndTime($value, $row, $column, $isExport)
    {
        $value = $row->getChildObject()->getEndDate();

        if (empty($value)) {
            return $this->__('N/A');
        }

        return $value;
    }

    protected function callbackFilterTitle($collection, $column)
    {
        $value = $column->getFilter()->getValue();

        if ($value == null) {
            return;
        }

        $collection->getSelect()->where('second_table.title LIKE ? OR second_table.sku LIKE ?', '%'.$value.'%');
    }

    //########################################

    public function getViewLogIconHtml($listingOtherId)
    {
        $listingOtherId = (int)$listingOtherId;
        $availableActionsId = array_keys($this->getAvailableActions());

        // Get last messages
        // ---------------------------------------
        $dbSelect = $this->resourceConnection->getConnection()->select()
            ->from(
                $this->activeRecordFactory->getObject('Listing\Other\Log')->getResource()->getMainTable(),
                array('action_id','action','type','description','create_date','initiator')
            )
            ->where('`listing_other_id` = ?', $listingOtherId)
            ->where('`action` IN (?)', $availableActionsId)
            ->order(array('id DESC'))
            ->limit(\Ess\M2ePro\Block\Adminhtml\Log\Grid\LastActions::PRODUCTS_LIMIT);

        $logs = $this->resourceConnection->getConnection()->fetchAll($dbSelect);

        if (empty($logs)) {
            return '';
        }

        // ---------------------------------------

        $summary = $this->createBlock('Listing\Log\Grid\LastActions')->setData([
            'entity_id' => $listingOtherId,
            'logs'      => $logs,
            'available_actions' => $this->getAvailableActions(),
            'view_help_handler' => 'EbayListingOtherGridObj.viewItemHelp',
            'hide_help_handler' => 'EbayListingOtherGridObj.hideItemHelp',
        ]);

        return $summary->toHtml();
    }

    private function getAvailableActions()
    {
        return [
            \Ess\M2ePro\Model\Listing\Other\Log::ACTION_CHANNEL_CHANGE => $this->__('Channel Change')
        ];
    }

    //########################################

    private function getLockedTag($row)
    {
        /** @var \Ess\M2ePro\Model\Listing\Other $listingOther */
        $listingOther = $this->ebayFactory->getObjectLoaded('Listing\Other', (int)$row['id']);
        $processingLocks = $listingOther->getProcessingLocks();

        $html = '';

        foreach ($processingLocks as $processingLock) {

            switch ($processingLock->getTag()) {

                case 'relist_action':
                    $html .= '<br/><span style="color: #605fff">[Relist in Progress...]</span>';
                    break;

                case 'revise_action':
                    $html .= '<br/><span style="color: #605fff">[Revise in Progress...]</span>';
                    break;

                case 'stop_action':
                    $html .= '<br/><span style="color: #605fff">[Stop in Progress...]</span>';
                    break;

                default:
                    break;

            }
        }

        return $html;
    }

    //########################################

    protected function _beforeToHtml()
    {

        if ($this->getRequest()->isXmlHttpRequest() || $this->getRequest()->getParam('isAjax')) {
            $this->js->addRequireJs([
                'jQuery' => 'jquery'
            ], <<<JS

            EbayListingOtherGridObj.afterInitPage();
JS
            );
        }

        return parent::_beforeToHtml();
    }

    //########################################

    public function getGridUrl()
    {
        return $this->getUrl('*/ebay_listing_other/viewGrid', array('_current' => true));
    }

    public function getRowUrl($row)
    {
        return false;
    }

    //########################################
}