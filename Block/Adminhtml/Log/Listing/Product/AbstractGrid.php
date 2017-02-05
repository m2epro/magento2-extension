<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Log\Listing\Product;

abstract class AbstractGrid extends \Ess\M2ePro\Block\Adminhtml\Log\Listing\AbstractGrid
{
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
        $this->setId($view . 'ListingLogGrid' . $this->getEntityId());
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
        return crc32("{$log->getActionId()}_{$log->getListingId()}_{$log->getListingProductId()}");
    }

    //########################################

    /**
     * @param \Magento\Framework\Data\Collection $collection
     */
    protected function applyFilters($collection)
    {
        // Set listing filter
        // ---------------------------------------
        if ($this->getEntityId()) {
            if ($this->isListingProductLog() && $this->getListingProduct()->isComponentModeAmazon() &&
                $this->getListingProduct()->getChildObject()->getVariationManager()->isRelationParentType()) {
                $collection->addFieldToFilter(
                    array(
                        self::LISTING_PRODUCT_ID_FIELD,
                        self::LISTING_PARENT_PRODUCT_ID_FIELD
                    ),
                    array(
                        array(
                            'attribute' => self::LISTING_PRODUCT_ID_FIELD,
                            'eq' => $this->getEntityId()
                        ),
                        array(
                            'attribute' => self::LISTING_PARENT_PRODUCT_ID_FIELD,
                            'eq' => $this->getEntityId()
                        )
                    )
                );
            } else {
                $collection->addFieldToFilter($this->getEntityField(), $this->getEntityId());
            }
        }
        // ---------------------------------------

        $component = $this->getComponentMode();

        $collection->getSelect()->where('main_table.component_mode = ?', $this->getComponentMode());

        $accountId = (int)$this->getRequest()->getParam($component.'Account', false);
        $marketplaceId = (int)$this->getRequest()->getParam($component.'Marketplace', false);

        if ($accountId) {
            $collection->getSelect()->where('main_table.account_id = ?', $accountId);
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
    }

    protected function _prepareColumns()
    {
        $this->addColumn('create_date', array(
            'header'    => $this->__('Creation Date'),
            'align'     => 'left',
            'type'      => 'datetime',
            'filter_time' => true,
            'filter_index' => 'main_table.create_date',
            'index'     => 'create_date',
            'frame_callback' => array($this, 'callbackColumnCreateDate'),
        ));

        $this->addColumn('action', array(
            'header'    => $this->__('Action'),
            'align'     => 'left',
            'type'      => 'options',
            'index'     => 'action',
            'sortable'  => false,
            'filter_index' => 'main_table.action',
            'options' => $this->getActionTitles(),
        ));

        if (!$this->getEntityId()) {
            $this->addColumn('listing_title', array(
                'header'    => $this->__('Listing'),
                'align'     => 'left',
                'type'      => 'text',
                'index'     => 'listing_title',
                'filter_index' => 'main_table.listing_title',
                'frame_callback' => array($this, 'callbackColumnListingTitleID'),
                'filter_condition_callback' => array($this, 'callbackFilterListingTitleID')
            ));
        }

        if (!$this->isListingProductLog()) {
            $this->addColumn('product_title', array(
                'header' => $this->__('Magento Product'),
                'align' => 'left',
                'type' => 'text',
                'index' => 'product_title',
                'filter_index' => 'main_table.product_title',
                'frame_callback' => array($this, 'callbackColumnProductTitleID'),
                'filter_condition_callback' => array($this, 'callbackFilterProductTitleID')
            ));
        }

        if ($this->isListingProductLog() && $this->getListingProduct()->isComponentModeAmazon() &&
            ($this->getListingProduct()->getChildObject()->getVariationManager()->isRelationParentType() ||
                $this->getListingProduct()->getChildObject()->getVariationManager()->isRelationChildType() ||
                $this->getListingProduct()->getChildObject()->getVariationManager()->isIndividualType())) {

            $this->addColumn('attributes', array(
                'header' => $this->__('Variation'),
                'align' => 'left',
                'index' => 'additional_data',
                'sortable'  => false,
                'filter_index' => 'main_table.additional_data',
                'frame_callback' => array($this, 'callbackColumnAttributes'),
                'filter_condition_callback' => array($this, 'callbackFilterAttributes')
            ));
        }

        $this->addColumn('description', array(
            'header'    => $this->__('Message'),
            'align'     => 'left',
            'type'      => 'text',
            'index'     => 'description',
            'filter_index' => 'main_table.description',
            'frame_callback' => array($this, 'callbackDescription')
        ));

        $this->addColumn('initiator', array(
            'header'=> $this->__('Run Mode'),
            'index' => 'initiator',
            'align' => 'right',
            'type'  => 'options',
            'sortable'  => false,
            'options' => $this->_getLogInitiatorList(),
            'frame_callback' => array($this, 'callbackColumnInitiator')
        ));

        $this->addColumn('type', array(
            'header'=> $this->__('Type'),
            'index' => 'type',
            'align' => 'right',
            'type'  => 'options',
            'sortable'  => false,
            'options' => $this->_getLogTypeList(),
            'frame_callback' => array($this, 'callbackColumnType')
        ));

        return parent::_prepareColumns();
    }

    //########################################

    public function callbackColumnListingTitleID($value, $row, $column, $isExport)
    {
        if (strlen($value) > 50) {
            $value = $this->filterManager->truncate($value, ['length' => 50]);
        }

        $value = $this->getHelper('Data')->escapeHtml($value);

        if ($row->getData('listing_id')) {

            $url = $this->getUrl(
                '*/'.$row->getData('component_mode').'_listing/view',
                array('id' => $row->getData('listing_id'))
            );

            $value = '<a target="_blank" href="'.$url.'">' .
                $value .
                '</a><br/>ID: '.$row->getData('listing_id');
        }

        return $value;
    }

    public function callbackColumnProductTitleID($value, $row, $column, $isExport)
    {
        if (!$row->getData('product_id')) {
            return $value;
        }

        $url = $this->getUrl('catalog/product/edit', array('id' => $row->getData('product_id')));
        $value = '<a target="_blank" href="'.$url.'" target="_blank">'.
            $this->getHelper('Data')->escapeHtml($value).
            '</a><br/>ID: '.$row->getData('product_id');

        $additionalData = $this->getHelper('Data')->jsonDecode($row->getData('additional_data'));
        if (empty($additionalData['variation_options'])) {
            return $value;
        }

        $value .= '<div style="font-size: 11px; color: grey;">';
        foreach ($additionalData['variation_options'] as $attribute => $option) {
            !$option && $option = '--';
            $value .= '<strong>'.
                $this->getHelper('Data')->escapeHtml($attribute) .
                '</strong>:&nbsp;'.
                $this->getHelper('Data')->escapeHtml($option) . '<br/>';
        }
        $value .= '</div>';

        return $value;
    }

    public function callbackColumnAttributes($value, $row, $column, $isExport)
    {
        $additionalData = $this->getHelper('Data')->jsonDecode($row->getData('additional_data'));
        if (empty($additionalData['variation_options'])) {
            return '';
        }

        $result = '<div style="font-size: 11px; color: grey;">';
        foreach ($additionalData['variation_options'] as $attribute => $option) {
            !$option && $option = '--';
            $result .= '<strong>'.
                $this->getHelper('Data')->escapeHtml($attribute) .
                '</strong>:&nbsp;'.
                $this->getHelper('Data')->escapeHtml($option) . '<br/>';
        }
        $result .= '</div>';

        return $result;
    }

    public function callbackColumnCreateDate($value, $row, $column, $isExport)
    {
        $logHash = $this->getLogHash($row);

        if (!is_null($logHash)) {
            return "{$value}<div class='no-display log-hash'>{$logHash}</div>";
        }

        return $value;
    }

    //########################################

    protected function callbackFilterListingTitleID($collection, $column)
    {
        $value = $column->getFilter()->getValue();

        if ($value == null) {
            return;
        }

        $where = 'listing_title LIKE ' . $collection->getSelect()->getAdapter()->quote('%'. $value .'%');
        is_numeric($value) && $where .= ' OR listing_id = ' . $value;

        $collection->getSelect()->where($where);
    }

    protected function callbackFilterProductTitleID($collection, $column)
    {
        $value = $column->getFilter()->getValue();

        if ($value == null) {
            return;
        }

        $where = 'product_title LIKE ' . $collection->getSelect()->getAdapter()->quote('%'. $value .'%');
        is_numeric($value) && $where .= ' OR product_id = ' . $value;

        $collection->getSelect()->where($where);
    }

    protected function callbackFilterAttributes($collection, $column)
    {
        $value = $column->getFilter()->getValue();

        if ($value == null) {
            return;
        }

        $collection->getSelect()->where('main_table.additional_data LIKE ?', '%'. $value .'%');
    }

    //########################################

    /**
     * Implements by using traits
     */
    abstract protected function getExcludedActionTitles();

    // ---------------------------------------

    protected function getActionTitles()
    {
        $allActions = $this->activeRecordFactory->getObject('Listing\Log')->getActionsTitles();

        return array_diff_key($allActions, $this->getExcludedActionTitles());
    }

    //########################################
}