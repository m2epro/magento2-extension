<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Account;

use Ess\M2ePro\Block\Adminhtml\Account\Grid as AccountGrid;

class Grid extends AccountGrid
{
    /** @var \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory */
    protected $ebayFactory;

    /** @var \Ess\M2ePro\Helper\View\Ebay */
    protected $ebayViewHelper;

    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;

    /** @var \Ess\M2ePro\Helper\Data\GlobalData */
    private $globalDataHelper;

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Helper\View\Ebay $ebayViewHelper,
        \Ess\M2ePro\Helper\View $viewHelper,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Ess\M2ePro\Helper\Data $dataHelper,
        \Ess\M2ePro\Helper\Data\GlobalData $globalDataHelper,
        array $data = []
    ) {
        $this->ebayFactory = $ebayFactory;
        $this->ebayViewHelper = $ebayViewHelper;
        $this->dataHelper = $dataHelper;
        $this->globalDataHelper = $globalDataHelper;
        parent::__construct($viewHelper, $context, $backendHelper, $data);
    }

    protected function _prepareCollection()
    {
        $collection = $this->ebayFactory->getObject('Account')->getCollection();

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('id', [
            'header' => $this->__('ID'),
            'align' => 'right',
            'width' => '100px',
            'type' => 'number',
            'index' => 'id',
            'filter_index' => 'main_table.id',
        ]);

        $this->addColumn('title', [
            'header' => $this->__('Title / Info'),
            'align' => 'left',
            'type' => 'text',
            'index' => 'title',
            'escape' => true,
            'filter_index' => 'main_table.title',
            'frame_callback' => [$this, 'callbackColumnTitle'],
            'filter_condition_callback' => [$this, 'callbackFilterTitle'],
        ]);

        $header = $this->__('Management');
        $isFeedbacksEnabled = $this->ebayViewHelper->isFeedbacksShouldBeShown();
        if (!$isFeedbacksEnabled) {
            $header = $this->__('My Stores');
        } elseif ($this->ebayViewHelper->isFeedbacksShouldBeShown()) {
            $header = $this->__('Feedbacks');
        }

        $this->globalDataHelper->setValue('feedbacks_enabled', $isFeedbacksEnabled);

        if ($this->ebayViewHelper->isFeedbacksShouldBeShown()) {
            $this->addColumn('management', [
                'header' => $header,
                'align' => 'center',
                'width' => '120px',
                'type' => 'text',
                'sortable' => false,
                'filter' => false,
                'frame_callback' => [$this, 'callbackColumnManagement'],
            ]);
        }

        return parent::_prepareColumns();
    }

    //########################################

    public function callbackColumnTitle($value, $row, $column, $isExport)
    {
        /** @var \Ess\M2ePro\Model\Account $row */
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
        $environment = (int)$row->getChildObject()->getData('mode') == \Ess\M2ePro\Model\Ebay\Account::MODE_SANDBOX
            ? 'Sandbox (Test)'
            : 'Production (Live)';
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

        if ($this->ebayViewHelper->isFeedbacksShouldBeShown($row->getData('id'))) {
            $html = <<<HTML
            <a href="javascript:void(0)"
               onclick="EbayAccountGridObj.openAccountFeedbackPopup({$row->getData('id')})">
               {$this->__('Feedback')}</a>
HTML;
        }

        $isFeedbacksEnabled = $this->globalDataHelper->getValue('feedbacks_enabled');

        if (empty($html) && !$isFeedbacksEnabled) {
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
        if ($mode !== null) {
            $modeWhere = ' OR second_table.mode = ' . $mode;
        }

        $collection->getSelect()->where(
            'main_table.title LIKE ? OR second_table.user_id LIKE ?' . $modeWhere,
            '%' . $value . '%'
        );
    }

    //########################################
}
