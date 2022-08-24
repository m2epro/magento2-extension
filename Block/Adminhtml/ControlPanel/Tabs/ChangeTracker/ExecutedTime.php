<?php

namespace Ess\M2ePro\Block\Adminhtml\ControlPanel\Tabs\ChangeTracker;

class ExecutedTime extends \Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock
{
    /** @var array */
    public $tableData = [];
    /** @var \Ess\M2ePro\Model\ResourceModel\OperationHistory\Collection */
    private $operationHistoryCollection;

    /**
     * @param \Ess\M2ePro\Model\ResourceModel\OperationHistory\Collection $operationHistoryCollection
     * @param \Ess\M2ePro\Model\Registry\Manager $registry
     * @param \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context
     * @param array $data
     */
    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\OperationHistory\Collection $operationHistoryCollection,
        \Ess\M2ePro\Model\Registry\Manager $registry,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->operationHistoryCollection = $operationHistoryCollection;
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->setTemplate('control_panel/tabs/change_tracker/executed_time.phtml');
        parent::_construct();
    }

    /**
     * @return \Ess\M2ePro\Block\Adminhtml\ControlPanel\Tabs\ChangeTracker\Statistics
     */
    protected function _beforeToHtml()
    {
        $this->tableData = $this->loadExecutedTime();

        return parent::_beforeToHtml();
    }

    /**
     * @return array
     */
    private function loadExecutedTime(): array
    {
        $collection = $this->operationHistoryCollection;

        $select = $collection->getSelect();
        $select->reset('columns');
        $select->columns([
            'start' => new \Zend_Db_Expr("DATE_FORMAT(start_date, '%Y-%m-%d %H:%i:%s')"),
            'end' => new \Zend_Db_Expr("DATE_FORMAT(end_date, '%Y-%m-%d %H:%i:%s')"),
            'executed_time' => new \Zend_Db_Expr(
                "TIME_FORMAT(SEC_TO_TIME(TIMESTAMPDIFF(SECOND, start_date, end_date)),'%im %ss')"
            ),
        ]);
        $select->where('nick = ?', 'cron_task_listing_product_change_tracker');
        $select->order('start_date DESC');
        $select->limit(10);

        return $collection->getItems();
    }
}
