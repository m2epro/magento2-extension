<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Grid\Column\Renderer;

class ItemId extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\Text
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
        $itemId = $this->_getValue($row);

        if ($row->getChildObject() && ($itemId === null || $itemId === '')) {
            $itemId = $row->getChildObject()->getData($this->getColumn()->getData('index'));
        }

        if ($row->getData('status') == \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED) {
            if ($isExport) {
                return '';
            }

            return '<span style="color: gray;">' . __('Not Listed') . '</span>';
        }

        if ($itemId === null || $itemId === '') {
            if ($isExport) {
                return '';
            }

            return __('N/A');
        }

        if ($isExport) {
            return $itemId;
        }

        $accountId = ($this->getColumn()->getData('account_id')) ? $this->getColumn()->getData('account_id')
            : $row->getData('account_id');
        $marketplaceId = ($this->getColumn()->getData('marketplace_id')) ? $this->getColumn()->getData('marketplace_id')
            : $row->getData('marketplace_id');

        $url = $this->getUrl(
            '*/ebay_listing/gotoEbay/',
            [
                'item_id' => $itemId,
                'account_id' => $accountId,
                'marketplace_id' => $marketplaceId,
            ]
        );

        return '<a href="' . $url . '" target="_blank">' . $itemId . '</a>';
    }
}
