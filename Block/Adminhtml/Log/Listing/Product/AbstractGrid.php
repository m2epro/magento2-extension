<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Log\Listing\Product;

abstract class AbstractGrid extends \Ess\M2ePro\Block\Adminhtml\Log\Listing\AbstractGrid
{
    public function _construct()
    {
        parent::_construct();
        $this->setId($this->getComponentMode() . 'LogListingGrid' . $this->getEntityId());

        $this->setDefaultSort('create_date');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);

        $this->entityIdFieldName = self::LISTING_PRODUCT_ID_FIELD;
        $this->logModelName = 'Listing_Log';
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
            if ($this->checkVariations()) {
                $collection->addFieldToFilter(
                    [
                        self::LISTING_PRODUCT_ID_FIELD,
                        self::LISTING_PARENT_PRODUCT_ID_FIELD,
                    ],
                    [
                        [
                            'attribute' => self::LISTING_PRODUCT_ID_FIELD,
                            'eq' => $this->getEntityId(),
                        ],
                        [
                            'attribute' => self::LISTING_PARENT_PRODUCT_ID_FIELD,
                            'eq' => $this->getEntityId(),
                        ],
                    ]
                );
            } else {
                $collection->addFieldToFilter($this->getEntityField(), $this->getEntityId());
            }
        }
        // ---------------------------------------

        $collection->addFieldToFilter('main_table.component_mode', $this->getComponentMode());

        if ($accountId = $this->getRequest()->getParam($this->getComponentMode() . 'Account')) {
            $collection->addFieldToFilter('main_table.account_id', $accountId);
        } else {
            $collection->getSelect()->joinLeft(
                [
                    'account_table' => $this->activeRecordFactory->getObject('Account')
                                                                 ->getResource()->getMainTable(),
                ],
                'main_table.account_id = account_table.id',
                ['real_account_id' => 'account_table.id']
            );
            $collection->addFieldToFilter('account_table.id', ['notnull' => true]);
        }

        if ($marketplaceId = $this->getRequest()->getParam($this->getComponentMode() . 'Marketplace')) {
            $collection->addFieldToFilter('main_table.marketplace_id', $marketplaceId);
        } else {
            $collection->getSelect()->joinLeft(
                [
                    'marketplace_table' => $this->activeRecordFactory->getObject('Marketplace')
                                                                     ->getResource()->getMainTable(),
                ],
                'main_table.marketplace_id = marketplace_table.id',
                ['marketplace_status' => 'marketplace_table.status']
            );
            $collection->addFieldToFilter('marketplace_table.status', \Ess\M2ePro\Model\Marketplace::STATUS_ENABLE);
        }
    }

    /**
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function checkVariations(): bool
    {
        if (
            $this->isListingProductLog() && $this->getListingProduct()->isComponentModeEbay() &&
            $this->getListingProduct()->getChildObject()->isVariationsReady()
        ) {
            return true;
        } elseif (
            $this->isListingProductLog() && !$this->getListingProduct()->isComponentModeEbay() &&
            $this->getListingProduct()->getChildObject()->getVariationManager()->isRelationParentType()
        ) {
            return true;
        }

        return false;
    }

    protected function _prepareColumns()
    {
        $this->addColumn('create_date', [
            'header' => $this->__('Creation Date'),
            'align' => 'left',
            'type' => 'datetime',
            'filter' => \Ess\M2ePro\Block\Adminhtml\Magento\Grid\Column\Filter\Datetime::class,
            'filter_time' => true,
            'filter_index' => 'main_table.create_date',
            'index' => 'create_date',
            'frame_callback' => [$this, 'callbackColumnCreateDate'],
        ]);

        $this->addColumn('action', [
            'header' => $this->__('Action'),
            'align' => 'left',
            'type' => 'options',
            'index' => 'action',
            'sortable' => false,
            'filter_index' => 'main_table.action',
            'options' => $this->getActionTitles(),
        ]);

        if (!$this->getEntityId()) {
            $this->addColumn('listing_title', [
                'header' => $this->__('Listing'),
                'align' => 'left',
                'type' => 'text',
                'index' => 'listing_title',
                'filter_index' => 'main_table.listing_title',
                'frame_callback' => [$this, 'callbackColumnListingTitleID'],
                'filter_condition_callback' => [$this, 'callbackFilterListingTitleID'],
            ]);
        }

        if (!$this->isListingProductLog()) {
            $this->addColumn('product_title', [
                'header' => $this->__('Magento Product'),
                'align' => 'left',
                'type' => 'text',
                'index' => 'product_title',
                'filter_index' => 'main_table.product_title',
                'frame_callback' => [$this, 'callbackColumnProductTitleID'],
                'filter_condition_callback' => [$this, 'callbackFilterProductTitleID'],
            ]);
        }

        if (
            $this->isListingProductLog() && $this->getListingProduct()->isComponentModeAmazon() &&
            ($this->getListingProduct()->getChildObject()->getVariationManager()->isRelationParentType() ||
                $this->getListingProduct()->getChildObject()->getVariationManager()->isRelationChildType() ||
                $this->getListingProduct()->getChildObject()->getVariationManager()->isIndividualType())
        ) {
            $this->addColumn('attributes', [
                'header' => $this->__('Variation'),
                'align' => 'left',
                'index' => 'additional_data',
                'sortable' => false,
                'filter_index' => 'main_table.additional_data',
                'frame_callback' => [$this, 'callbackColumnAttributes'],
                'filter_condition_callback' => [$this, 'callbackFilterAttributes'],
            ]);
        }

        $this->addColumn('description', [
            'header' => $this->__('Message'),
            'align' => 'left',
            'type' => 'text',
            'index' => 'description',
            'filter_index' => 'main_table.description',
            'frame_callback' => [$this, 'callbackColumnDescription'],
        ]);

        $this->addColumn('initiator', [
            'header' => $this->__('Run Mode'),
            'index' => 'initiator',
            'align' => 'right',
            'type' => 'options',
            'sortable' => false,
            'options' => $this->_getLogInitiatorList(),
            'frame_callback' => [$this, 'callbackColumnInitiator'],
        ]);

        $this->addColumn('type', [
            'header' => $this->__('Type'),
            'index' => 'type',
            'align' => 'right',
            'type' => 'options',
            'sortable' => false,
            'options' => $this->_getLogTypeList(),
            'frame_callback' => [$this, 'callbackColumnType'],
        ]);

        return parent::_prepareColumns();
    }

    //########################################

    public function callbackColumnListingTitleID($value, $row, $column, $isExport)
    {
        if (strlen($value) > 50) {
            $value = $this->filterManager->truncate($value, ['length' => 50]);
        }

        $value = $this->dataHelper->escapeHtml($value);
        $productId = (int)$row->getData('product_id');

        $urlData = [
            'id' => $row->getData('listing_id'),
            'filter' => base64_encode("product_id[from]={$productId}&product_id[to]={$productId}"),
        ];

        $manageUrl = $this->getUrl('*/' . $row->getData('component_mode') . '_listing/view', $urlData);
        if ($row->getData('listing_id')) {
            $url = $this->getUrl(
                '*/' . $row->getData('component_mode') . '_listing/view',
                ['id' => $row->getData('listing_id')]
            );

            $value = '<a target="_blank" href="' . $url . '">' .
                $value .
                '</a><br/>ID: ' . $row->getData('listing_id');

            if ($productId) {
                $value .= '<br/>Product:<br/>' .
                    '<a target="_blank" href="' . $manageUrl . '">' . $row->getData('product_title') . '</a>';
            }
        }

        return $value;
    }

    public function callbackColumnProductTitleID($value, $row, $column, $isExport)
    {
        if (!$row->getData('product_id')) {
            return $value;
        }

        $url = $this->getUrl('catalog/product/edit', ['id' => $row->getData('product_id')]);
        $value = '<a target="_blank" href="' . $url . '" target="_blank">' .
            $this->dataHelper->escapeHtml($value) .
            '</a><br/>ID: ' . $row->getData('product_id');

        $additionalData = \Ess\M2ePro\Helper\Json::decode($row->getData('additional_data'));
        if (empty($additionalData['variation_options'])) {
            return $value;
        }

        $value .= '<div style="font-size: 11px; color: grey;">';
        foreach ($additionalData['variation_options'] as $attribute => $option) {
            if ($option === '' || $option === null) {
                $option = '--';
            }
            $value .= '<strong>' .
                $this->dataHelper->escapeHtml($attribute) .
                '</strong>:&nbsp;' .
                $this->dataHelper->escapeHtml($option) . '<br/>';
        }
        $value .= '</div>';

        return $value;
    }

    public function callbackColumnAttributes($value, $row, $column, $isExport): string
    {
        $additionalData = \Ess\M2ePro\Helper\Json::decode($row->getData('additional_data'));
        if (empty($additionalData['variation_options'])) {
            return '';
        }

        $result = '<div style="font-size: 11px; color: grey;">';
        foreach ($additionalData['variation_options'] as $attribute => $option) {
            if ($option === '' || $option === null) {
                $option = '--';
            }
            $result .= '<strong>' .
                $this->dataHelper->escapeHtml($attribute) .
                '</strong>:&nbsp;' .
                $this->dataHelper->escapeHtml($option) . '<br/>';
        }
        $result .= '</div>';

        return $result;
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

    protected function callbackFilterListingTitleID($collection, $column)
    {
        $value = $column->getFilter()->getValue();

        if ($value == null) {
            return;
        }

        $where = 'listing_title LIKE ' . $collection->getSelect()->getAdapter()->quote('%' . $value . '%');
        is_numeric($value) && $where .= ' OR listing_id = ' . $value;

        $collection->getSelect()->where($where);
    }

    protected function callbackFilterProductTitleID($collection, $column)
    {
        $value = $column->getFilter()->getValue();

        if ($value == null) {
            return;
        }

        $where = 'product_title LIKE ' . $collection->getSelect()->getAdapter()->quote('%' . $value . '%');
        is_numeric($value) && $where .= ' OR product_id = ' . $value;

        $collection->getSelect()->where($where);
    }

    protected function callbackFilterAttributes($collection, $column)
    {
        $value = $column->getFilter()->getValue();

        if ($value == null) {
            return;
        }

        $collection->getSelect()->where('main_table.additional_data LIKE ?', '%' . $value . '%');
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
