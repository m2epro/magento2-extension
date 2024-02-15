<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Grid\Column\Renderer;

class Qty extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\Number
{
    use \Ess\M2ePro\Block\Adminhtml\Traits\BlockTrait;

    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;

    public function __construct(
        \Magento\Backend\Block\Context $context,
        \Ess\M2ePro\Helper\Data $dataHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->dataHelper = $dataHelper;
    }

    public function render(\Magento\Framework\DataObject $row): string
    {
        return $this->renderGeneral($row, false);
    }

    public function renderExport(\Magento\Framework\DataObject $row): string
    {
        $result = strip_tags($this->renderGeneral($row, true));

        if (is_numeric($result)) {
            return $result;
        }

        return '';
    }

    private function renderGeneral(\Magento\Framework\DataObject $row, bool $isExport): string
    {
        $rowObject = $row;
        $value = $this->_getValue($row);
        $isVariationGrid = ($this->getColumn()->getData('is_variation_grid') !== null)
            ? $this->getColumn()->getData('is_variation_grid')
            : false;
        if ($isVariationGrid) {
            $value = $row->getChildObject()->getData('online_qty');
            $rowObject = $row->getChildObject();
        }

        if ($row->getData('amazon_status') == \Ess\M2ePro\Model\Listing\Product::STATUS_BLOCKED) {
            return __('N/A');
        }

        $listingProductId = $row->getData('id');

        if (!$row->getData('is_variation_parent') || $isVariationGrid) {
            if ($row->getData('amazon_status') == \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED) {
                return '<span style="color: gray;">' . __('Not Listed') . '</span>';
            }

            if ($rowObject->getData('is_afn_channel')) {
                $qty = $rowObject->getData('online_afn_qty') ?? __('N/A');
                if ($isExport) {
                    return $qty;
                }

                $imageURL = $this->getViewFileUrl('Ess_M2ePro::images/amazon-afn-icon.svg');

                return "<span class='fba-qty-column'><img src='$imageURL' alt='fba'> $qty</span>";
            }

            $showReceiving = ($this->getColumn()->getData('show_receiving') !== null)
                ? $this->getColumn()->getData('show_receiving')
                : true;

            if ($value === null || $value === '') {
                if ($showReceiving) {
                    return '<i style="color:gray;">receiving...</i>';
                }

                return __('N/A');
            }

            if ($value <= 0) {
                return '<span style="color: red;">0</span>';
            }

            return $value;
        }

        if ($row->getData('general_id') == '') {
            return '<span style="color: gray;">' . __('Not Listed') . '</span>';
        }

        $variationChildStatuses = \Ess\M2ePro\Helper\Json::decode($row->getData('variation_child_statuses'));

        if (empty($variationChildStatuses)) {
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

        if (!(bool)$row->getData('is_afn_channel')) {
            if ($value <= 0) {
                return '<span style="color: red;">0</span>';
            }

            return $value;
        }

        if ($isExport) {
            return $value;
        }

        $resultValue = __('AFN');
        $additionalData = (array)\Ess\M2ePro\Helper\Json::decode($row->getData('additional_data'));

        $filter = base64_encode('online_qty[afn]=1');

        $productTitle = $this->dataHelper->escapeHtml($row->getData('name'));
        $vpmt = __('Manage Variations of &quot;%1&quot; ', $productTitle);
        // @codingStandardsIgnoreLine
        $vpmt = addslashes($vpmt);

        $linkTitle = __('Show AFN Child Products.');
        $afnCountWord = !empty($additionalData['afn_count']) ? $additionalData['afn_count']
            : __('show');

        $resultValue = $resultValue . "&nbsp;<a href=\"javascript:void(0)\"
                           class=\"hover-underline\"
                           title=\"{$linkTitle}\"
                           onclick=\"ListingGridObj.variationProductManageHandler.openPopUp(
                            {$listingProductId}, '{$vpmt}', '{$filter}'
                        )\">[" . $afnCountWord . "]</a>";

        return <<<HTML
    <div>{$value}</div>
    <div>{$resultValue}</div>
HTML;
    }
}
