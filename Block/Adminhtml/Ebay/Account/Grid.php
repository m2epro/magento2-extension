<?php

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Account;

use Ess\M2ePro\Block\Adminhtml\Account\Grid as AccountGrid;

class Grid extends AccountGrid
{
    protected $ebayFactory;

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

    protected function _prepareCollection()
    {
        // Get collection of accounts
        $collection = $this->ebayFactory->getObject('Account')->getCollection();

        // Set collection to grid
        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('id', array(
            'header'    => $this->__('ID'),
            'align'     => 'right',
            'width'     => '100px',
            'type'      => 'number',
            'index'     => 'id',
            'filter_index' => 'main_table.id'
        ));

        $this->addColumn('title', array(
            'header'    => $this->__('Title / Info'),
            'align'     => 'left',
            'type'      => 'text',
            'index'     => 'title',
            'escape'    => true,
            'filter_index' => 'main_table.title',
            'frame_callback' => array($this, 'callbackColumnTitle'),
            'filter_condition_callback' => array($this, 'callbackFilterTitle')
        ));

        $header = $this->__('Management');
        $pickupStoreAccounts = $this->getHelper('Component\Ebay\PickupStore')->getEnabledAccounts();
        $isFeedbacksEnabled = $this->getHelper('View\Ebay')->isFeedbacksShouldBeShown();
        if (!empty($pickupStoreAccounts) && !$isFeedbacksEnabled) {
            $header = $this->__('My Stores');
        } elseif (empty($pickupStoreAccounts) && $this->getHelper('View\Ebay')->isFeedbacksShouldBeShown()) {
            $header = $this->__('Feedbacks');
        }

        $this->getHelper('Data\GlobalData')->setValue('pickup_store_accounts', $pickupStoreAccounts);
        $this->getHelper('Data\GlobalData')->setValue('feedbacks_enabled', $isFeedbacksEnabled);

        if ($this->getHelper('View\Ebay')->isFeedbacksShouldBeShown() || !empty($pickupStoreAccounts)) {

            $this->addColumn('management', array(
                'header'         => $header,
                'align'          => 'center',
                'width'          => '120px',
                'type'           => 'text',
                'sortable'       => false,
                'filter'         => false,
                'frame_callback' => array($this, 'callbackColumnManagement')
            ));
        }

        return parent::_prepareColumns();
    }

    //########################################

    public function callbackColumnTitle($value, $row, $column, $isExport)
    {
        $userIdLabel = $this->__('eBay User ID');
        $userId = $row->getChildObject()->getData('user_id');

        $userIdHtml = '';
        if (!empty($userId)) {
            $userIdHtml = <<<HTML
            <span style="font-weight: bold">{$userIdLabel}</span>:
            <span style="color: #505050">{$userId}</span>
            <br/>
HTML;
        }

        $environmentLabel = $this->__('Environment');
        $environment = (int)$row->getChildObject()->getData('mode') == \Ess\M2ePro\Model\Ebay\Account::MODE_SANDBOX ?
            'Sandbox (Test)' : 'Production (Live)';
        $environment = $this->__($environment);

        $value = <<<HTML
        <div>
            {$value}<br/>
            {$userIdHtml}
            <span style="font-weight: bold">{$environmentLabel}</span>:
            <span style="color: #505050">{$environment}</span>
            <br/>
        </div>
HTML;

        return $value;
    }

    public function callbackColumnManagement($value, $row, $column, $isExport)
    {
        $html = '';

        if ($this->getHelper('View\Ebay')->isFeedbacksShouldBeShown($row->getData('id'))) {
            $html = <<<HTML
            <a href="javascript:void(0)"
               onclick="EbayAccountGridObj.openAccountFeedbackPopup({$row->getData('id')})">
               {$this->__('Feedback')}</a>
HTML;
        }

        $additionalData = $this->getHelper('Data')->jsonDecode($row->getData('additional_data'));

        if (!empty($additionalData) && (int)$additionalData['bopis']) {
            $url = $this->getUrl('*/ebay_account_pickupStore/index', ['account_id' => $row->getData('id')]);
            $html .= <<<HTML
            <a href="{$url}" style="display: block; padding-top: 5px;" target="_self">{$this->__('My Stores')}</a>
HTML;
        }

        $pickupStoreAccounts = $this->getHelper('Data\GlobalData')->getValue('pickup_store_accounts');
        $isFeedbacksEnabled = $this->getHelper('Data\GlobalData')->getValue('feedbacks_enabled');

        if (empty($html) && (!$isFeedbacksEnabled || empty($pickupStoreAccounts))) {
            $html = '<strong style="color: gray;">' . $this->__("Disabled") . '</strong>';
        }

        return $html;
    }

    //########################################

    protected function callbackFilterTitle($collection, $column)
    {
        $value = $column->getFilter()->getValue();

        if ($value == null) {
            return;
        }

        $mode = null;
        if (strpos('sandbox (test)', strtolower($value)) !== false) {
            $mode = \Ess\M2ePro\Model\Ebay\Account::MODE_SANDBOX;
        } elseif (strpos('production (live)', strtolower($value)) !== false) {
            $mode = \Ess\M2ePro\Model\Ebay\Account::MODE_PRODUCTION;
        }

        $modeWhere = '';
        if (!is_null($mode)) {
            $modeWhere = ' OR second_table.mode = ' . $mode;
        }

        $collection->getSelect()->where(
            'main_table.title LIKE ? OR second_table.user_id LIKE ?' . $modeWhere,
            '%'. $value .'%'
        );
    }

    //########################################
}