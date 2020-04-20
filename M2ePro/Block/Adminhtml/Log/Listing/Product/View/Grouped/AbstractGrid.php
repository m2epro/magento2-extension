<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Log\Listing\Product\View\Grouped;

use Ess\M2ePro\Block\Adminhtml\Log\Listing\View;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Log\Listing\Product\View\Grouped\AbstractGrid
 */
abstract class AbstractGrid extends \Ess\M2ePro\Block\Adminhtml\Log\Listing\Product\AbstractGrid
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
        $logCollection = $this->activeRecordFactory->getObject('Listing\Log')->getCollection();

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
                self::LISTING_PRODUCT_ID_FIELD => 'main_table.' . self::LISTING_PRODUCT_ID_FIELD,
                self::LISTING_PARENT_PRODUCT_ID_FIELD => 'main_table.' . self::LISTING_PARENT_PRODUCT_ID_FIELD,
                self::LISTING_ID_FIELD => 'main_table.' . self::LISTING_ID_FIELD,
                'product_id' => 'main_table.product_id',
                'action_id' => 'main_table.action_id',
                'action' => 'main_table.action',
                'listing_title' => 'main_table.listing_title',
                'product_title' => 'main_table.product_title',
                'initiator' => 'main_table.initiator',
                'component_mode' => 'main_table.component_mode',
                'additional_data' => 'main_table.additional_data',
                'create_date' => new \Zend_Db_Expr('MAX(main_table.create_date)'),
                'description' => new \Zend_Db_Expr('GROUP_CONCAT(main_table.description)'),
                'type' => new \Zend_Db_Expr('MAX(main_table.type)'),
                'nested_log_ids' => new \Zend_Db_Expr('GROUP_CONCAT(main_table.id)'),
            ]
        );

        $groupedCollection->getSelect()->group(['listing_product_id', 'action_id']);

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

        $logCollection = $this->activeRecordFactory->getObject('Listing\Log')->getCollection();

        $logCollection->getSelect()
            ->reset(\Zend_Db_Select::COLUMNS)
            ->columns([
                'id',
                self::LISTING_PRODUCT_ID_FIELD,
                self::LISTING_ID_FIELD,
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

        $logCollection->getSelect()->where(
            new \Zend_Db_Expr('main_table.id IN (?)'),
            $nestedLogsIds
        );

        foreach ($logCollection->getItems() as $log) {
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

        /** @var \Ess\M2ePro\Model\Listing\Log $log */
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
