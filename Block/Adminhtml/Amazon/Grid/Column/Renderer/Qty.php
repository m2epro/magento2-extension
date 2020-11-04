<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace  Ess\M2ePro\Block\Adminhtml\Amazon\Grid\Column\Renderer;

use Ess\M2ePro\Block\Adminhtml\Traits;

/**
 * Class  \Ess\M2ePro\Block\Adminhtml\Amazon\Grid\Column\Renderer\Qty
 */
class Qty extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\Number
{
    use Traits\BlockTrait;

    /** @var \Ess\M2ePro\Helper\Factory  */
    protected $helperFactory;

    /** @var \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory  */
    protected $amazonFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Magento\Backend\Block\Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->helperFactory = $helperFactory;
        $this->amazonFactory = $amazonFactory;
    }

    //########################################

    public function render(\Magento\Framework\DataObject $row)
    {
        $rowObject = $row;
        $value = $this->_getValue($row);
        $translator = $this->getHelper('Module\Translation');
        $isVariationGrid = ($this->getColumn()->getData('is_variation_grid') !== null)
            ? $this->getColumn()->getData('is_variation_grid')
            : false;
        if ($isVariationGrid) {
            $value = $row->getChildObject()->getData('online_qty');
            $rowObject = $row->getChildObject();
        }

        if ($row->getData('amazon_status') == \Ess\M2ePro\Model\Listing\Product::STATUS_BLOCKED) {
            return $translator->__('N/A');
        }

        $listingProductId = $row->getData('id');

        if (!$row->getData('is_variation_parent') || $isVariationGrid) {
            if ($row->getData('amazon_status') == \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED) {
                return '<span style="color: gray;">' . $translator->__('Not Listed') . '</span>';
            }

            if ((bool)$rowObject->getData('is_afn_channel')) {
                $sku = $rowObject->getData('amazon_sku');

                if (empty($sku)) {
                    return $translator->__('AFN');
                }

                /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
                $listingProduct = $this->amazonFactory->getObjectLoaded('Listing\Product', $listingProductId);

                $afn = $translator->__('AFN');
                $total = $translator->__('Total');
                $inStock = $translator->__('In Stock');
                $accountId = $listingProduct->getListing()->getAccountId();

                return <<<HTML
<div id="m2ePro_afn_qty_value_{$listingProductId}">
    <span class="m2ePro-online-sku-value" productId="{$listingProductId}" style="display: none">{$sku}</span>
    <span class="m2epro-empty-afn-qty-data" style="display: none">{$afn}</span>
    <div class="m2epro-afn-qty-data" style="display: none">
        <div class="total">{$total}: <span></span></div>
        <div class="in-stock">{$inStock}: <span></span></div>
    </div>
    <a href="javascript:void(0)"
        onclick="AmazonListingAfnQtyObj.showAfnQty(this,'{$sku}',{$listingProductId}, {$accountId})">{$afn}</a>
</div>
HTML;
            }

            $showReceiving = ($this->getColumn()->getData('show_receiving') !== null)
                              ? $this->getColumn()->getData('show_receiving')
                              : true;

            if ($value === null || $value === '') {
                if ($showReceiving) {
                    return '<i style="color:gray;">receiving...</i>';
                } else {
                    return $translator->__('N/A');
                }
            }

            if ($value <= 0) {
                return '<span style="color: red;">0</span>';
            }

            return $value;
        }

        if ($row->getData('general_id') == '') {
            return '<span style="color: gray;">' . $translator->__('Not Listed') . '</span>';
        }

        $variationChildStatuses = $this->getHelper('Data')->jsonDecode($row->getData('variation_child_statuses'));

        if (empty($variationChildStatuses)) {
            return $translator->__('N/A');
        }

        $activeChildrenCount = 0;
        foreach ($variationChildStatuses as $childStatus => $count) {
            if ($childStatus == \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED) {
                continue;
            }

            $activeChildrenCount += (int)$count;
        }

        if ($activeChildrenCount == 0) {
            return $translator->__('N/A');
        }

        if (!(bool)$row->getData('is_afn_channel')) {
            if ($value <= 0) {
                return '<span style="color: red;">0</span>';
            }

            return $value;
        }

        $resultValue = $translator->__('AFN');
        $additionalData = (array)$this->getHelper('Data')->jsonDecode($row->getData('additional_data'));

        $filter = base64_encode('online_qty[afn]=1');

        $productTitle = $this->getHelper('Data')->escapeHtml($row->getData('name'));
        $vpmt = $translator->__('Manage Variations of &quot;%s%&quot; ', $productTitle);
        // @codingStandardsIgnoreLine
        $vpmt = addslashes($vpmt);

        $linkTitle = $translator->__('Show AFN Child Products.');
        $afnCountWord = !empty($additionalData['afn_count']) ? $additionalData['afn_count']
            : $translator->__('show');

        $resultValue = $resultValue."&nbsp;<a href=\"javascript:void(0)\"
                           class=\"hover-underline\"
                           title=\"{$linkTitle}\"
                           onclick=\"ListingGridObj.variationProductManageHandler.openPopUp(
                            {$listingProductId}, '{$vpmt}', '{$filter}'
                        )\">[".$afnCountWord."]</a>";

        return <<<HTML
    <div>{$value}</div>
    <div>{$resultValue}</div>
HTML;
    }

    //########################################
}
