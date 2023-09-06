<?php

namespace Ess\M2ePro\Block\Adminhtml\Widget\Grid\AdvancedFilter;

class FilterFactory
{
    /** @var \Magento\Framework\ObjectManagerInterface */
    private $objectManager;

    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        $this->objectManager = $objectManager;
    }

    public function createDropDownFilter(
        string $id,
        string $label,
        \Ess\M2ePro\Block\Adminhtml\Widget\Grid\AdvancedFilter\Filters\DropDown\OptionCollection $options,
        \Closure $filterCallback
    ): \Ess\M2ePro\Block\Adminhtml\Widget\Grid\AdvancedFilter\Filters\DropDown {
        return $this->objectManager->create(
            \Ess\M2ePro\Block\Adminhtml\Widget\Grid\AdvancedFilter\Filters\DropDown::class,
            [
                'id' => $id,
                'label' => $label,
                'options' => $options,
                'filterCallback' => $filterCallback,
            ]
        );
    }
}
