<?php

namespace Ess\M2ePro\Block\Adminhtml\Widget\Grid\AdvancedFilter\Filters\DropDown;

class OptionCollection
{
    /** @var \Magento\Framework\Escaper */
    private $escaper;

    public function __construct(
        \Magento\Framework\Escaper $escaper
    ) {
        $this->escaper = $escaper;
    }

    /** @var Option[] */
    private $options = [];

    public function addOption(Option $option)
    {
        $this->options[] = $option;
    }

    /**
     * @return Option[]
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    public function isEmpty(): bool
    {
        return count($this->options) === 0;
    }

    public function getOptionsHtml(): string
    {
        $optionsHtml = '';
        foreach ($this->getOptionsByGroups() as $groupLabel => $options) {
            if ($groupLabel !== Option::DEFAULT_OPTION_GROUP_LABEL) {
                $optionsHtml .= sprintf(
                    '<optgroup label="%s">',
                    $this->escaper->escapeHtml($groupLabel)
                );
            }

            foreach ($options as $option) {
                $optionsHtml .= $option->getOptionHtml();
            }

            if ($groupLabel !== Option::DEFAULT_OPTION_GROUP_LABEL) {
                $optionsHtml .= '</optgroup>';
            }
        }

        return $optionsHtml;
    }

    /**
     * @return array<string, Option[]>
     */
    private function getOptionsByGroups(): array
    {
        $options = [];
        foreach ($this->getOptions() as $option) {
            $options[$option->getOptionGroupLabel()][] = $option;
        }

        return $options;
    }
}
