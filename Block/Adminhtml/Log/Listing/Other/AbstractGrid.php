<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Log\Listing\Other;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Log\Listing\Other\AbstractGrid
 */
abstract class AbstractGrid extends \Ess\M2ePro\Block\Adminhtml\Log\Listing\AbstractGrid
{
    const LISTING_ID_FIELD = 'id';

    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialize view
        // ---------------------------------------
        $view = $this->getHelper('View')->getCurrentView();
        // ---------------------------------------

        // Initialization block
        // ---------------------------------------
        $this->setId($view . 'ListingOtherLogGrid' . $this->getEntityId());
        // ---------------------------------------

        // Set default values
        // ---------------------------------------
        $this->setDefaultSort('create_date');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
        // ---------------------------------------
    }

    //########################################

    protected function getLogHash($log)
    {
        return crc32("{$log->getActionId()}_{$log->getListingOtherId()}");
    }

    //########################################

    /**
     * @param \Magento\Framework\Data\Collection $collection
     */
    protected function applyFilters($collection)
    {
        // ---------------------------------------

        if ($this->getComponentMode() == \Ess\M2ePro\Helper\Component\Ebay::NICK) {
            $collection->getSelect()
                ->joinLeft(
                    ['ea' => $this->activeRecordFactory->getObject('Ebay\Account')
                    ->getResource()->getMainTable()],
                    '(`main_table`.account_id = `ea`.account_id)',
                    [
                        'real_account_id' => 'ea.account_id',
                        'account_mode' => 'ea.mode'
                    ]
                );
        }

        $collection->getSelect()
            ->joinLeft(
                ['lo' => $this->activeRecordFactory->getObject('Listing\Other')
                ->getResource()->getMainTable()],
                '(`main_table`.listing_other_id = `lo`.id)',
                [
                    'real_listing_other_id' => 'lo.id'
                ]
            );
        // ---------------------------------------

        // Set listing filter
        // ---------------------------------------
        if ($this->isListingLog()) {
            $collection->addFieldToFilter('main_table.listing_other_id', $this->getEntityId());
        }
        // ---------------------------------------

        $component = $this->getComponentMode();

        $collection->getSelect()->where('main_table.component_mode = ?', $component);

        $accountId = (int)$this->getRequest()->getParam($component . 'Account', false);
        $marketplaceId = (int)$this->getRequest()->getParam($component . 'Marketplace', false);

        if ($accountId) {
            $collection->getSelect()->where('main_table.account_id = ?', $accountId);
        } elseif ($this->getComponentMode() == \Ess\M2ePro\Helper\Component\Ebay::NICK) {
            $collection->getSelect()->where('ea.account_id IS NOT NULL');
        } else {
            $collection->getSelect()->joinLeft(
                [
                    'account_table' => $this->activeRecordFactory->getObject('Account')
                        ->getResource()->getMainTable()
                ],
                'main_table.account_id = account_table.id',
                ['real_account_id' => 'account_table.id']
            );
            $collection->getSelect()->where('account_table.id IS NOT NULL');
        }

        if ($marketplaceId) {
            $collection->getSelect()->where('main_table.marketplace_id = ?', $marketplaceId);
        } else {
            $collection->getSelect()->joinLeft(
                [
                    'marketplace_table' => $this->activeRecordFactory->getObject('Marketplace')
                        ->getResource()->getMainTable()
                ],
                'main_table.marketplace_id = marketplace_table.id',
                ['marketplace_status' => 'marketplace_table.status']
            );
            $collection->getSelect()
                ->where('marketplace_table.status = ?', \Ess\M2ePro\Model\Marketplace::STATUS_ENABLE);
        }
        // ---------------------------------------
    }

    protected function _prepareColumns()
    {
        $columnTitles = $this->getColumnTitles();

        $this->addColumn('create_date', [
            'header' => $this->__($columnTitles['create_date']),
            'align' => 'left',
            'type' => 'datetime',
            'filter' => '\Ess\M2ePro\Block\Adminhtml\Magento\Grid\Column\Filter\Datetime',
            'filter_time' => true,
            'width' => '150px',
            'index' => 'create_date',
            'filter_index' => 'main_table.create_date',
            'frame_callback' => [$this, 'callbackColumnCreateDate']
        ]);

        $this->addColumn('action', [
            'header' => $this->__($columnTitles['action']),
            'align' => 'left',
            'type' => 'options',
            'index' => 'action',
            'sortable' => false,
            'filter_index' => 'main_table.action',
            'options' => $this->getActionTitles(),
        ]);

        if (!$this->isListingLog()) {
            $this->addColumn('identifier', [
                'header' => $this->__($columnTitles['identifier']),
                'align' => 'left',
                'type' => 'text',
                'index' => 'identifier',
                'filter_index' => 'main_table.identifier',
                'frame_callback' => [$this, 'callbackColumnIdentifier'],
                'filter_condition_callback' => [$this, 'callbackFilterIdentifier']
            ]);
        }

        $this->addColumn('description', [
            'header' => $this->__($columnTitles['description']),
            'align' => 'left',
            'type' => 'text',
            'index' => 'description',
            'filter_index' => 'main_table.description',
            'frame_callback' => [$this, 'callbackDescription']
        ]);

        $this->addColumn('initiator', [
            'header' => $this->__($columnTitles['initiator']),
            'index' => 'initiator',
            'align' => 'right',
            'type' => 'options',
            'sortable' => false,
            'options' => $this->_getLogInitiatorList(),
            'frame_callback' => [$this, 'callbackColumnInitiator']
        ]);

        $this->addColumn('type', [
            'header' => $this->__($columnTitles['type']),
            'index' => 'type',
            'align' => 'right',
            'type' => 'options',
            'sortable' => false,
            'options' => $this->_getLogTypeList(),
            'frame_callback' => [$this, 'callbackColumnType']
        ]);

        return parent::_prepareColumns();
    }

    //########################################

    public function callbackColumnIdentifier($value, $row, $column, $isExport)
    {
        $title = $row->getData('title');

        $isEmptyId = ($value === null || $value === '');
        $isEmptyTitle = ($title === null || $title === '');

        if ($isEmptyTitle && $isEmptyId) {
            return $this->__('N/A');
        }

        if ($isEmptyId) {
            return $title;
        }

        $urlTitle = $title;
        if ($isEmptyTitle) {
            $urlTitle = $value;
        }

        $accountMode = $row->getData('account_mode');
        $marketplaceId = $row->getData('marketplace_id');

        $identifier = $urlTitle;

        if ($row->getData('real_listing_other_id') !== null) {
            switch ($row->getData('component_mode')) {
                case \Ess\M2ePro\Helper\Component\Ebay::NICK:
                    $url = $this->getHelper('Component\Ebay')->getItemUrl($value, $accountMode, $marketplaceId);
                    $identifier = '<a href="' . $url . '" target="_blank">' . $urlTitle . '</a>';
                    break;

                case \Ess\M2ePro\Helper\Component\Amazon::NICK:
                    $url = $this->getHelper('Component\Amazon')->getItemUrl($value, $marketplaceId);
                    $identifier = '<a href="' . $url . '" target="_blank">' . $urlTitle . '</a>';
                    break;
            }
        }

        if (!$isEmptyTitle) {
            $identifier .= '<br/> ID: ' . $value;
        }

        return $identifier;
    }

    public function callbackColumnCreateDate($value, $row, $column, $isExport)
    {
        $logHash = $this->getLogHash($row);

        if ($logHash !== null) {
            return "{$value}<div class='no-display log-hash'>{$logHash}</div>";
        }

        return $value;
    }

    //########################################

    protected function callbackFilterIdentifier($collection, $column)
    {
        $value = $column->getFilter()->getValue();

        if ($value == null) {
            return;
        }

        $where = 'main_table.title LIKE ' . $collection->getSelect()->getAdapter()->quote('%'. $value .'%');

        if ($this->getComponentMode() == \Ess\M2ePro\Helper\View\Amazon::NICK
            || is_numeric($value)
        ) {
            $where .= ' OR main_table.identifier = ' . $collection->getSelect()->getAdapter()->quote($value);
        }

        $collection->getSelect()->where($where);
    }

    //########################################

    /**
     * Implements by using traits
     */
    abstract protected function getColumnTitles();

    // ---------------------------------------

    protected function getActionTitles()
    {
        return $this->activeRecordFactory->getObject('Listing_Other_Log')->getActionsTitles();
    }

    //########################################
}
