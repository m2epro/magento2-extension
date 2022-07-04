<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Listing\Log\Grid;

class LastActions extends \Ess\M2ePro\Block\Adminhtml\Log\Grid\LastActions
{
    /** @var \Ess\M2ePro\Helper\View */
    protected $viewHelper;

    public function __construct(
        \Ess\M2ePro\Helper\View $viewHelper,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Ess\M2ePro\Helper\Data $dataHelper,
        array $data = []
    ) {
        parent::__construct($context, $dataHelper,$data);
        $this->viewHelper = $viewHelper;
    }

    protected function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('lastProductActions');
        // ---------------------------------------
    }

    //########################################

    protected function getActions(array $logs)
    {
        $actions = $this->getGroupedActions($logs);

        $this->sortActions($actions);
        $this->sortActionLogs($actions);

        return array_slice($actions, 0, self::ACTIONS_COUNT);
    }

    protected function getGroupedActions(array $logs)
    {
        $groupedLogsByAction = [];

        foreach ($logs as $log) {
            $log['description'] = $this->viewHelper->getModifiedLogMessage($log['description']);
            $groupedLogsByAction[$log['action_id']][] = $log;
        }

        $actions = [];

        foreach ($groupedLogsByAction as $actionLogs) {
            $actions[] = [
                'type'           => $this->getMainType($actionLogs),
                'date'           => $date = $this->getMainDate($actionLogs),
                'localized_date' => $this->_localeDate->formatDate($date, \IntlDateFormatter::MEDIUM, true),
                'action'         => $this->getActionTitle($actionLogs),
                'initiator'      => $this->getInitiator($actionLogs),
                'items'          => $actionLogs
            ];
        }

        return $actions;
    }

    //########################################
}
