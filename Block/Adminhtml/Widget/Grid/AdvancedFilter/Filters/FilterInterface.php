<?php

namespace Ess\M2ePro\Block\Adminhtml\Widget\Grid\AdvancedFilter\Filters;

interface FilterInterface
{
    public function getId(): string;

    public function getLabel(): string;

    public function getFilterCallback(): \Closure;

    public function setGrid(\Magento\Backend\Block\Widget\Grid $grid): void;

    public function setSelectedValue(string $value): void;

    public function isSelected(): bool;

    public function getFilterHtml(): string;

    public function getSelectedHtml();
}
