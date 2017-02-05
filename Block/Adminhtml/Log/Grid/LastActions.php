<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2016 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Log\Grid;

use Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock;
use Ess\M2ePro\Model\Log\AbstractModel as LogModel;

abstract class LastActions extends AbstractBlock
{
    const VIEW_LOG_LINK_SHOW = 0;
    const VIEW_LOG_LINK_HIDE = 1;

    const ACTIONS_COUNT  = 3;
    const PRODUCTS_LIMIT = 100;

    protected $_template = 'log/last_actions.phtml';
    protected $tip = NULL;
    protected $iconSrc = NULL;
    protected $rows = [];

    public static $actionsSortOrder = [
        LogModel::TYPE_SUCCESS  => 1,
        LogModel::TYPE_ERROR    => 2,
        LogModel::TYPE_WARNING  => 3,
        LogModel::TYPE_NOTICE   => 4,
    ];

    //########################################

    public function getTip()
    {
        return $this->tip;
    }

    public function getIconSrc()
    {
        return $this->iconSrc;
    }

    public function getEncodedRows()
    {
        return base64_encode($this->getHelper('Data')->jsonEncode($this->rows));
    }

    public function getEntityId()
    {
        if (!$this->hasData('entity_id') || !is_int($this->getData('entity_id'))) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Entity ID is not set.');
        }

        return $this->getData('entity_id');
    }

    public function getViewHelpHandler()
    {
        if (!$this->hasData('view_help_handler') || !is_string($this->getData('view_help_handler'))) {
            throw new \Ess\M2ePro\Model\Exception\Logic('View help handler is not set.');
        }

        return $this->getData('view_help_handler');
    }

    public function getCloseHelpHandler()
    {
        if (!$this->hasData('hide_help_handler') || !is_string($this->getData('hide_help_handler'))) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Close help handler is not set.');
        }

        return $this->getData('hide_help_handler');
    }

    public function getHideViewLogLink()
    {
        if ($this->hasData('hide_view_log_link')) {
            return self::VIEW_LOG_LINK_HIDE;
        }

        return self::VIEW_LOG_LINK_SHOW;
    }

    //########################################

    protected function getInitiator(array $actionLogs)
    {
        if (empty($actionLogs)) {
            return '';
        }

        $log = reset($actionLogs);

        if (!isset($log['initiator'])) {
            return '';
        }

        switch ($log['initiator']) {
            case \Ess\M2ePro\Helper\Data::INITIATOR_UNKNOWN:
                return '';
            case \Ess\M2ePro\Helper\Data::INITIATOR_USER:
                return $this->__('Manual');
            case \Ess\M2ePro\Helper\Data::INITIATOR_EXTENSION:
                return $this->__('Automatic');
        }

        return '';
    }

    protected function getActionTitle(array $actionLogs)
    {
        if (empty($actionLogs)) {
            return '';
        }

        $log = reset($actionLogs);

        if (!isset($log['action'])) {
            return '';
        }

        $availableActions = $this->getAvailableActions();
        $action = $log['action'];

        if (isset($availableActions[$action])) {
            return $availableActions[$action];
        }

        return '';
    }

    protected function getMainType(array $actionLogs)
    {
        $types = array_column($actionLogs, 'type');

        return max($types);
    }

    protected function getMainDate(array $actionLogs)
    {
        if (count($actionLogs) > 1) {
            $row = array_reduce($actionLogs, function ($a, $b) {
                return strtotime($a['create_date']) > strtotime($b['create_date']) ? $a : $b;
            });
        } else {
            $row = reset($actionLogs);
        }

        return $this->_localeDate->formatDate($row['create_date'], \IntlDateFormatter::MEDIUM, true);
    }

    //----------------------------------------

    abstract protected function getActions(array $logs);

    protected function sortActionLogs(array &$actions)
    {
        $sortOrder = self::$actionsSortOrder;

        foreach ($actions as &$actionLogs) {
            usort($actionLogs['items'], function($a, $b) use ($sortOrder) {
                return $sortOrder[$a['type']] > $sortOrder[$b['type']];
            });
        }
    }

    protected function sortActions(array &$actions)
    {
        usort($actions, function($a, $b) {
            return strtotime($a['date']) < strtotime($b['date']);
        });
    }

    protected function getRows()
    {
        if (!$this->hasData('logs') || !is_array($this->getData('logs'))) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Logs are not set.');
        }

        $logs = $this->getData('logs');

        if (empty($logs)) {
            return [];
        }

        return $this->getActions($logs);
    }

    //----------------------------------------

    protected function getAvailableActions()
    {
        if (!$this->hasData('available_actions') || !is_array($this->getData('available_actions'))) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Available actions are not set.');
        }

        return $this->getData('available_actions');
    }

    protected function getTips()
    {
        if (!$this->hasData('tips') || !is_array($this->getData('tips'))) {
            return [
                LogModel::TYPE_SUCCESS  => 'Last Action was completed successfully.',
                LogModel::TYPE_ERROR    => 'Last Action was completed with error(s).',
                LogModel::TYPE_WARNING  => 'Last Action was completed with warning(s).',
                LogModel::TYPE_NOTICE   => 'Last Action was completed with notice(s).'
            ];
        }

        return $this->getData('tips');
    }

    protected function getIcons()
    {
        if (!$this->hasData('icons') || !is_array($this->getData('icons'))) {
            return [
                LogModel::TYPE_SUCCESS  => 'success',
                LogModel::TYPE_ERROR    => 'error',
                LogModel::TYPE_WARNING  => 'warning',
                LogModel::TYPE_NOTICE   => 'notice',
            ];
        }

        return $this->getData('icons');
    }

    protected function getDefaultTip()
    {
        return $this->__('Last Action was completed successfully.');
    }

    protected function getTipByType($type)
    {
        foreach ($this->getTips() as $tipType => $tip) {
            if ($tipType == $type) {
                return $this->__($tip);
            }
        }

        return $this->getDefaultTip();
    }

    protected function getDefaultIcon()
    {
        return 'success';
    }

    protected function getIconByType($type)
    {
        foreach ($this->getIcons() as $iconType => $icon) {
            if ($iconType == $type) {
                return $icon;
            }
        }

        return $this->getDefaultIcon();
    }

    //----------------------------------------

    protected function _beforeToHtml()
    {
        $rows = $this->getRows();

        if (empty($rows)) {
            return parent::_beforeToHtml();
        }

        $lastActionRow = $rows[0];
        // ---------------------------------------

        // Get log icon
        // ---------------------------------------
        $icon = $this->getDefaultIcon();
        $tip = $this->getDefaultTip();

        if (isset($lastActionRow['type'])) {
            $tip = $this->getTipByType($lastActionRow['type']);
            $icon = $this->getIconByType($lastActionRow['type']);
        }

        $this->tip = $this->getHelper('Data')->escapeHtml($tip);
        $this->iconSrc = $this->getViewFileUrl('Ess_M2ePro::images/log_statuses/'.$icon.'.png');
        $this->rows = $rows;
        // ---------------------------------------

        $this->jsPhp->addConstants($this->getHelper('Data')->getClassConstants('\Ess\M2ePro\Model\Log\AbstractModel'));

        // ---------------------------------------

        return parent::_beforeToHtml();
    }

    protected function _toHtml()
    {
        if (empty($this->rows)) {
            return '';
        }

        return parent::_toHtml();
    }

    //########################################
}