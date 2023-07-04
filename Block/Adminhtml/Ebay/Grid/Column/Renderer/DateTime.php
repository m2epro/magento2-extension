<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Grid\Column\Renderer;

class DateTime extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\Datetime
{
    use \Ess\M2ePro\Block\Adminhtml\Traits\BlockTrait;

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
        $value = parent::render($row);

        if ($row->getChildObject() && ($value === null || $value === '')) {
            $value = $row->getChildObject()->getData($this->getColumn()->getData('index'));
            $row->setData($this->getColumn()->getData('index'), $value);
            $value = parent::render($row);
        }

        if ($row->getData('status') == \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED) {
            if ($isExport) {
                return '';
            }

            return '<span style="color: gray;">' . __('Not Listed') . '</span>';
        }

        if ($row->getChildObject() && ($value === null || $value === '')) {
            if ($isExport) {
                return '';
            }

            return __('N/A');
        }

        return $value;
    }
}
