<?php

namespace Ess\M2ePro\Block\Adminhtml\Widget\Grid\AdvancedFilter\Filters\DropDown;

class Option
{
    public const DEFAULT_OPTION_GROUP_LABEL = '';

    /** @var \Magento\Framework\Escaper */
    private $escaper;
    /** @var string */
    private $label;
    /** @var string */
    private $value;
    /** @var string */
    private $optionGroupLabel;
    /** @var bool */
    private $isSelected = false;

    public function __construct(
        \Magento\Framework\Escaper $escaper,
        string $label,
        string $value,
        string $optionGroupLabel = self::DEFAULT_OPTION_GROUP_LABEL
    ) {
        $this->label = $label;
        $this->value = $value;
        $this->optionGroupLabel = $optionGroupLabel;
        $this->escaper = $escaper;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getOptionGroupLabel(): string
    {
        return $this->optionGroupLabel;
    }

    public function isDefaultOptionGroupLabel(): bool
    {
        return $this->getOptionGroupLabel() === self::DEFAULT_OPTION_GROUP_LABEL;
    }

    public function setIsSelected(bool $isSelected = true): void
    {
        $this->isSelected = $isSelected;
    }

    public function isSelected(): bool
    {
        return $this->isSelected;
    }

    public function getOptionHtml(): string
    {
        return sprintf(
            '<option value="%s"%s>%s</option>',
            $this->escaper->escapeHtml($this->getValue()),
            $this->isSelected ? ' selected' : '',
            $this->escaper->escapeHtml($this->getLabel())
        );
    }
}
