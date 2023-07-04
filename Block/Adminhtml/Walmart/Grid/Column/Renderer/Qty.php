<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Grid\Column\Renderer;

class Qty extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\Number
{
    use \Ess\M2ePro\Block\Adminhtml\Traits\BlockTrait;

    public function render(\Magento\Framework\DataObject $row): string
    {
        $value = $this->_getValue($row);
        if (!$value && $row->getChildObject()) {
            $value = $this->_getValue($row->getChildObject());
        }

        if (!$row->getData('is_variation_parent')) {
            if ($row->getData('status') == \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED) {
                return '<span style="color: gray;">' . __('Not Listed') . '</span>';
            }

            if ($value === null || $value === '') {
                if ($row->getData('status') == \Ess\M2ePro\Model\Listing\Product::STATUS_BLOCKED) {
                    return __('N/A');
                }

                return '<i style="color:gray;">receiving...</i>';
            }

            if ($value <= 0) {
                return '<span style="color: red;">0</span>';
            }

            return $value;
        }

        $variationChildStatuses = \Ess\M2ePro\Helper\Json::decode($row->getData('variation_child_statuses'));

        if (empty($variationChildStatuses) || $value === null || $value === '') {
            return __('N/A');
        }

        $activeChildrenCount = 0;
        foreach ($variationChildStatuses as $childStatus => $count) {
            if ($childStatus == \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED) {
                continue;
            }

            $activeChildrenCount += (int)$count;
        }

        if ($activeChildrenCount == 0) {
            return __('N/A');
        }

        if ($value <= 0) {
            return '<span style="color: red;">0</span>';
        }

        return $value;
    }

    public function renderExport(\Magento\Framework\DataObject $row): string
    {
        $result = strip_tags($this->render($row));

        if (is_numeric($result)) {
            return $result;
        }

        return '';
    }
}
