<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Log\Listing\Other\View\Grouped;

use Ess\M2ePro\Block\Adminhtml\Log\Listing\View;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Log\Listing\Other\View\Grouped\AbstractGrid
 */
abstract class AbstractGrid extends \Ess\M2ePro\Block\Adminhtml\Log\Listing\Other\AbstractGrid
{
    protected $nestedLogs = [];

    //########################################

    protected function getViewMode()
    {
        return View\Switcher::VIEW_MODE_GROUPED;
    }

    // ---------------------------------------

    protected function _prepareColumns()
    {
        parent::_prepareColumns();

        $this->getColumn('description')->setData('sortable', false);

        return $this;
    }

    protected function _prepareCollection()
    {
        $logCollection = $this->activeRecordFactory->getObject('Listing_Other_Log')->getCollection();

        $this->applyFilters($logCollection);

        $logCollection->getSelect()
            ->order(new \Zend_Db_Expr('main_table.id DESC'))
            ->limit(1, $this->getMaxLastHandledRecordsCount() - 1);

        $lastAllowedLog = $logCollection->getFirstItem();

        if ($lastAllowedLog->getId() !== null) {
            $logCollection->getSelect()->limit($this->getMaxLastHandledRecordsCount());
            $this->addMaxAllowedLogsCountExceededNotification($lastAllowedLog->getCreateDate());
        } else {
            $logCollection->getSelect()
                ->reset(\Zend_Db_Select::ORDER)
                ->reset(\Zend_Db_Select::LIMIT_COUNT)
                ->reset(\Zend_Db_Select::LIMIT_OFFSET);
        }

        $groupedCollection = $this->wrapperCollectionFactory->create();
        $groupedCollection->setConnection($this->resourceConnection->getConnection());
        $groupedCollection->getSelect()->reset()->from(
            ['main_table' => $logCollection->getSelect()],
            [
                'id' => 'main_table.id',
                'marketplace_id' => 'main_table.marketplace_id',
                'listing_other_id' => 'main_table.listing_other_id',
                'real_listing_other_id' => 'main_table.real_listing_other_id',
                'identifier' => 'main_table.identifier',
                'title' => 'main_table.title',
                'action_id' => 'main_table.action_id',
                'action' => 'main_table.action',
                'initiator' => 'main_table.initiator',
                'additional_data' => 'main_table.additional_data',
                'component_mode' => 'main_table.component_mode',
                'create_date' => new \Zend_Db_Expr('MAX(main_table.create_date)'),
                'description' => new \Zend_Db_Expr('GROUP_CONCAT(main_table.description)'),
                'type' => new \Zend_Db_Expr('MAX(main_table.type)'),
                'nested_log_ids' => new \Zend_Db_Expr('GROUP_CONCAT(main_table.id)'),
            ]
        );

        $groupedCollection->getSelect()->group(['listing_other_id', 'action_id']);

        $resultCollection = $this->wrapperCollectionFactory->create();
        $resultCollection->setConnection($this->resourceConnection->getConnection());
        $resultCollection->getSelect()->reset()->from(
            ['main_table' => $groupedCollection->getSelect()]
        );

        $this->setCollection($resultCollection);

        return parent::_prepareCollection();
    }

    protected function _afterLoadCollection()
    {
        if (!$this->getCollection()->getSize()) {
            return parent::_afterLoadCollection();
        }

        $otherLogCollection = $this->activeRecordFactory->getObject('Listing_Other_Log')->getCollection();

        $otherLogCollection->getSelect()
            ->reset(\Zend_Db_Select::COLUMNS)
            ->columns([
                'id',
                'listing_other_id',
                'action_id',
                'description',
                'type',
                'create_date'
            ])
            ->order(new \Zend_Db_Expr('id DESC'));

        $nestedLogsIds = [];
        foreach ($this->getCollection()->getItems() as $log) {
            $nestedLogsIds[] = new \Zend_Db_Expr($log->getNestedLogIds());
        }

        $otherLogCollection->getSelect()->where(
            new \Zend_Db_Expr('main_table.id IN (?)'),
            $nestedLogsIds
        );

        foreach ($otherLogCollection->getItems() as $log) {
            $this->nestedLogs[$this->getLogHash($log)][] = $log;
        }

        $sortOrder = \Ess\M2ePro\Block\Adminhtml\Log\Grid\LastActions::$actionsSortOrder;

        foreach ($this->nestedLogs as &$logs) {
            usort($logs, function ($a, $b) use ($sortOrder) {
                return $sortOrder[$a['type']] > $sortOrder[$b['type']];
            });
        }

        return parent::_afterLoadCollection();
    }

    //########################################

    public function callbackDescription($value, $row, $column, $isExport)
    {
        $description = '';

        $nestedLogs = $this->nestedLogs[$this->getLogHash($row)];

        /** @var \Ess\M2ePro\Model\Listing\Other\Log $log */
        foreach ($nestedLogs as $log) {
            $messageType = '';
            $createDate = '';

            if (count($nestedLogs) > 1) {
                $messageType = $this->callbackColumnType(
                    '[' . $this->_getLogTypeList()[$log->getType()] . ']',
                    $log,
                    $column,
                    $isExport
                );
                $createDate = $this->_localeDate->formatDate($log->getCreateDate(), \IntlDateFormatter::MEDIUM, true);
            }

            $logDescription = parent::callbackDescription($value, $log, $column, $isExport);

            $description .= <<<HTML
<div class="log-description-group">
    <span class="log-description">
        <span class="log-type">{$messageType}</span>
        {$logDescription}
    </span>
    <div class="log-date">{$createDate}</div>
</div>
HTML;
        }

        return $description;
    }

    //########################################
}
