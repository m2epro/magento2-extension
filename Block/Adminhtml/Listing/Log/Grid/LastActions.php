<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Listing\Log\Grid;

class LastActions extends \Ess\M2ePro\Block\Adminhtml\Log\Grid\LastActions
{
    //########################################

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
            $log['description'] = $this->getHelper('View')->getModifiedLogMessage($log['description']);
            $groupedLogsByAction[$log['action_id']][] = $log;
        }

        $actions = [];

        foreach ($groupedLogsByAction as $actionLogs) {
            $actions[] = [
                'type'      => $this->getMainType($actionLogs),
                'date'      => $this->getMainDate($actionLogs),
                'action'    => $this->getActionTitle($actionLogs),
                'initiator' => $this->getInitiator($actionLogs),
                'items'     => $actionLogs
            ];
        }

        return $actions;
    }

    //########################################
}