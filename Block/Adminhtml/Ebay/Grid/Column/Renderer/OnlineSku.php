<?php

declare(strict_types=1);

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Grid\Column\Renderer;

class OnlineSku extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\Text
{
    public function render(\Magento\Framework\DataObject $row): string
    {
        return $this->renderGeneral($row, false);
    }

    public function renderExport(\Magento\Framework\DataObject $row): string
    {
        return $this->renderGeneral($row, true);
    }

    public function renderGeneral(\Magento\Framework\DataObject $row, bool $isExport): string
    {
        $onlineSku = $this->_getValue($row);

        if ($row->getChildObject() && ($onlineSku === null || $onlineSku === '')) {
            $onlineSku = $row->getChildObject()->getData($this->getColumn()->getData('index'));
        }

        if ($row->getData('status') == \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED) {
            if ($isExport) {
                return '';
            }

            return '<span style="color: gray;">' . __('Not Listed') . '</span>';
        }

        if ($onlineSku === null || $onlineSku === '') {
            if ($isExport) {
                return '';
            }

            return (string)__('N/A');
        }

        return $onlineSku;
    }
}
