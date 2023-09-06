<?php

namespace Ess\M2ePro\Block\Adminhtml\Magento\Grid;

use Magento\Backend\Block\Widget\Grid\Extended;
use Ess\M2ePro\Block\Adminhtml\Traits;

abstract class AbstractGrid extends Extended
{
    use Traits\BlockTrait;
    use Traits\RendererTrait;

    /** @var \Ess\M2ePro\Helper\Factory */
    protected $helperFactory;
    /** @var \Ess\M2ePro\Model\Factory */
    protected $modelFactory;
    /** @var \Ess\M2ePro\Model\ActiveRecord\Factory */
    protected $activeRecordFactory;
    /** @var \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory */
    protected $parentFactory;
    /** @var string */
    protected $_template = 'magento/grid/extended.phtml';
    /** @var bool */
    protected $customPageSize = false;
    /** @var \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AdvancedFilterCollection */
    private $advancedFilterCollection;

    /**
     * @param \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param array $data
     */
    public function __construct(
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        array $data = []
    ) {
        $this->helperFactory = $context->getHelperFactory();
        $this->modelFactory = $context->getModelFactory();
        $this->activeRecordFactory = $context->getActiveRecordFactory();
        $this->parentFactory = $context->getParentFactory();

        $this->css = $context->getCss();
        $this->jsPhp = $context->getJsPhp();
        $this->js = $context->getJs();
        $this->jsTranslator = $context->getJsTranslator();
        $this->jsUrl = $context->getJsUrl();

        parent::__construct($context, $backendHelper, $data);
    }

    protected function _prepareGrid()
    {
        $this->_prepareAdvancedFilters();
        parent::_prepareGrid();

        return $this;
    }

    protected function _prepareAdvancedFilters()
    {
    }

    public function hasAdvancedFilters(): bool
    {
        return $this->getAdvancedFilterCollection()->hasFilters();
    }

    public function addAdvancedFilter(
        \Ess\M2ePro\Block\Adminhtml\Widget\Grid\AdvancedFilter\Filters\FilterInterface $filter
    ): AbstractGrid {
        $filter->setGrid($this);
        $this->getAdvancedFilterCollection()->addFilter($filter);

        return $this;
    }

    /**
     * @return \Ess\M2ePro\Block\Adminhtml\Widget\Grid\AdvancedFilter\Filters\FilterInterface[]
     */
    public function getAdvancedFilters(): array
    {
        return $this->getAdvancedFilterCollection()->getFilters();
    }

    /**
     * @return \Ess\M2ePro\Block\Adminhtml\Widget\Grid\AdvancedFilter\Filters\FilterInterface[]
     */
    public function getSelectedAdvancedFilterColumns(): array
    {
        return $this->getAdvancedFilterCollection()->getSelectedFilters();
    }

    public function addColumn($columnId, $column)
    {
        if (is_array($column)) {
            if (!array_key_exists('header_css_class', $column)) {
                $column['header_css_class'] = 'grid-listing-column-' . $columnId;
            }

            if (!array_key_exists('column_css_class', $column)) {
                $column['column_css_class'] = 'grid-listing-column-' . $columnId;
            }
        }

        if (is_array($column)) {
            $this->getColumnSet()->setChild(
                $columnId,
                $this->getLayout()
                     ->createBlock(\Ess\M2ePro\Block\Adminhtml\Widget\Grid\Column\Extended\Rewrite::class)
                     ->setData($column)
                     ->setId($columnId)
                     ->setGrid($this)
            );
            $this->getColumnSet()->getChildBlock($columnId)->setGrid($this);
        } else {
            throw new \Exception($this->__('Please correct the column format and try again.'));
        }

        $this->_lastColumnId = $columnId;

        return $this;
    }

    public function getMassactionBlockName()
    {
        return \Ess\M2ePro\Block\Adminhtml\Magento\Grid\Massaction::class;
    }

    public function isAllowedCustomPageSize()
    {
        return $this->customPageSize;
    }

    public function setCustomPageSize($value)
    {
        $this->customPageSize = $value;

        return $this;
    }

    /**
     * @return void
     */
    public function applyQueryFilters(): void
    {
        // See \Magento\Backend\Block\Widget\Grid::_prepareCollection()
        $filter = $this->getParam($this->getVarNameFilter());

        if ($filter === null) {
            $filter = $this->_defaultFilter;
        }

        if (is_string($filter)) {
            $data = $this->_backendHelper->prepareFilterString($filter);
            $data = array_merge($data, (array)$this->getRequest()->getPost($this->getVarNameFilter()));
            $this->_setFilterValues($data);
        } elseif ($filter && is_array($filter)) {
            $this->_setFilterValues($filter);
        } elseif (count($this->_defaultFilter) !== 0) {
            $this->_setFilterValues($this->_defaultFilter);
        }
    }

    public function getCsv(): string
    {
        $csv = '';
        $this->_isExport = true;
        $this->_prepareGrid();
        $this->getCollection()->getSelect()->limit();
        $this->getCollection()->setPageSize(0);
        $this->getCollection()->load();
        $this->_afterLoadCollection();

        $data = [];
        foreach ($this->getColumns() as $column) {
            if (!$column->getIsSystem()) {
                $data[] = '"' . $column->getExportHeader() . '"';
            }
        }
        $csv .= implode(',', $data) . "\n";

        foreach ($this->getCollection() as $item) {
            $data = [];
            foreach ($this->getColumns() as $column) {
                if (!$column->getIsSystem()) {
                    $exportField = (string)$column->getRowFieldExport($item);
                    $data[] = '"' . str_replace(
                        ['"', '\\'],
                        ['""', '\\\\'],
                        is_numeric($exportField) ? $exportField : ($exportField ?: '')
                    ) . '"';
                }
            }
            $csv .= implode(',', $data) . "\n";
        }

        if ($this->getCountTotals()) {
            $data = [];
            foreach ($this->getColumns() as $column) {
                if (!$column->getIsSystem()) {
                    $data[] = '"' . str_replace(
                        ['"', '\\'],
                        ['""', '\\\\'],
                        $column->getRowFieldExport($this->getTotals()) ?: ''
                    ) . '"';
                }
            }
            $csv .= implode(',', $data) . "\n";
        }

        return $csv;
    }

    protected function _setFilterValues($data)
    {
        $this->applyAdvancedFilters($data);

        return parent::_setFilterValues($data);
    }

    private function applyAdvancedFilters(array $data)
    {
        foreach ($this->getAdvancedFilters() as $filter) {
            $filterId = $filter->getId();

            if (empty($data[$filterId])) {
                continue;
            }

            $filterValue = $data[$filterId];
            $filter->setSelectedValue($filterValue);

            $filterCallback = $filter->getFilterCallback();
            $filterCallback($this->getCollection(), $filterValue);
        }
    }

    private function getAdvancedFilterCollection(): \Ess\M2ePro\Block\Adminhtml\Widget\Grid\AdvancedFilter\Collection
    {
        if ($this->advancedFilterCollection === null) {
            $this->advancedFilterCollection = new \Ess\M2ePro\Block\Adminhtml\Widget\Grid\AdvancedFilter\Collection();
        }

        return $this->advancedFilterCollection;
    }
}
