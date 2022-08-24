<?php

namespace Ess\M2ePro\Block\Adminhtml\ControlPanel\Tabs\ChangeTracker;

class Logs extends \Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock
{
    /**  @var array */
    public $logs = [];
    /** @var \Ess\M2ePro\Model\Registry\Manager */
    private $registry;

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
        $this->registry = $registry;
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->setTemplate('control_panel/tabs/change_tracker/logs.phtml');
        parent::_construct();
    }

    /**
     * @return \Ess\M2ePro\Block\Adminhtml\ControlPanel\Tabs\ChangeTracker\Logs
     */
    protected function _beforeToHtml()
    {
        $this->logs = $this->loadLogs();

        return parent::_beforeToHtml();
    }

    /**
     * @return array
     */
    private function loadLogs(): array
    {
        return $this->registry->getValueFromJson('/change_tracker/logs');
    }
}
