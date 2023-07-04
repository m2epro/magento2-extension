<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Grid\Column\Renderer;

class Sku extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\Text
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

    private function renderGeneral(\Magento\Framework\DataObject $row, bool $isExport)
    {
        $value = $this->_getValue($row);
        $isVariationParent = $row->getData('is_variation_parent');

        $isVariationGrid = ($this->getColumn()->getData('is_variation_grid') !== null)
            ? $this->getColumn()->getData('is_variation_grid')
            : false;
        if ($isVariationGrid) {
            $value = $row->getChildObject()->getData('sku');
        }

        if ($value === null || $value === '') {
            if ($isExport) {
                return '';
            }

            $value = __('N/A');
        }

        if ($isExport) {
            return $value;
        }

        $showEditSku = ($this->getColumn()->getData('show_edit_sku') !== null)
            ? $this->getColumn()->getData('show_edit_sku')
            : true;

        if (!$showEditSku) {
            return $value;
        }

        $productId = $row->getData('id');

        if ($row->getData('status') == \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED && !$isVariationParent) {
            $value = <<<HTML
<div class="walmart-sku">
    {$value}&nbsp;&nbsp;
    <a href="#" class="walmart-sku-edit"
       onclick="ListingGridObj.editChannelDataHandler.showEditSkuPopup({$productId})">(edit)</a>
</div>
HTML;
        }

        return $value;
    }
}
