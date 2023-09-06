<?php

namespace Ess\M2ePro\Block\Adminhtml\Widget\Grid\AdvancedFilter;

class Collection
{
    /** @var \Ess\M2ePro\Block\Adminhtml\Widget\Grid\AdvancedFilter\Filters\FilterInterface[] */
    private $filters = [];

    public function addFilter(
        \Ess\M2ePro\Block\Adminhtml\Widget\Grid\AdvancedFilter\Filters\FilterInterface $advancedFilterColumn
    ): void {
        $this->filters[$advancedFilterColumn->getId()] = $advancedFilterColumn;
    }

    public function hasFilters(): bool
    {
        return count($this->filters) > 0;
    }

    /**
     * @return \Ess\M2ePro\Block\Adminhtml\Widget\Grid\AdvancedFilter\Filters\FilterInterface[]
     */
    public function getFilters(): array
    {
        return $this->filters;
    }

    /**
     * @return \Ess\M2ePro\Block\Adminhtml\Widget\Grid\AdvancedFilter\Filters\FilterInterface[]
     */
    public function getSelectedFilters(): array
    {
        $selectedFilters = [];

        foreach ($this->getFilters() as $filter) {
            if (!$filter->isSelected()) {
                continue;
            }

            $selectedFilters[] = $filter;
        }

        return $selectedFilters;
    }
}
