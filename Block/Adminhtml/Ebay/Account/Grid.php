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
        
        if ($this->getHelper('View\Ebay')->isFeedbacksShouldBeShown()) {

            $this->addColumn('feedbacks', array(
                'header'         => $this->__('Feedback'),
                'align'          => 'center',
                'width'          => '120px',
                'type'           => 'text',
                'sortable'       => false,
                'filter'         => false,
                'frame_callback' => array($this, 'callbackColumnFeedbacks')
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

    public function callbackColumnFeedbacks($value, $row, $column, $isExport)
    {
        if ($this->getHelper('View\Ebay')->isFeedbacksShouldBeShown($row->getData('id'))) {
            $link = <<<HTML
<a href="javascript:void(0)" 
    onclick="EbayAccountGridObj.openAccountFeedbackPopup({$row->getData('id')})" 
    target="_blank">{$this->__('Feedback')}</a>
HTML;
        } else {
            $link = '<strong style="color: gray;">' . $this->__("Disabled") . '</strong>';
        }

        return $link;
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