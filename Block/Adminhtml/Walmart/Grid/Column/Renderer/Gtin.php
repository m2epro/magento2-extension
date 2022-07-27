<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace  Ess\M2ePro\Block\Adminhtml\Walmart\Grid\Column\Renderer;

use Ess\M2ePro\Block\Adminhtml\Traits;

class Gtin extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\Text
{
    use Traits\BlockTrait;

    /** @var \Ess\M2ePro\Helper\Factory  */
    protected $helperFactory;

    /** @var \Ess\M2ePro\Helper\Module\Translation */
    private $translationHelper;

    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;

    /** @var \Ess\M2ePro\Helper\Component\Walmart */
    private $walmartHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Backend\Block\Context $context,
        \Ess\M2ePro\Helper\Module\Translation $translationHelper,
        \Ess\M2ePro\Helper\Data $dataHelper,
        \Ess\M2ePro\Helper\Component\Walmart $walmartHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->helperFactory = $helperFactory;
        $this->translationHelper = $translationHelper;
        $this->dataHelper = $dataHelper;
        $this->walmartHelper = $walmartHelper;
    }

    public function render(\Magento\Framework\DataObject $row)
    {
        $gtin = $this->_getValue($row);
        $objectRow = $row;

        $isVariationGrid = ($this->getColumn()->getData('is_variation_grid') !== null)
                            ? $this->getColumn()->getData('is_variation_grid')
                            : false;
        if ($isVariationGrid) {
            $objectRow = $row->getChildObject();
            $gtin = $objectRow->getData('gtin');
        }

        if (empty($gtin)) {
            return $this->translationHelper->__('N/A');
        }

        $productId = $row->getData('id');
        $gtinHtml = $this->dataHelper->escapeHtml($gtin);

        $marketplaceId = ($this->getColumn()->getData('marketplace_id') !== null)
                              ? $this->getColumn()->getData('marketplace_id')
                              : $row->getData('marketplace_id');

        $channelUrl = $this->walmartHelper->getItemUrl(
            $objectRow->getData($this->walmartHelper->getIdentifierForItemUrl($marketplaceId)),
            $marketplaceId
        );

        if (!empty($channelUrl)) {
            $gtinHtml = <<<HTML
<a href="{$channelUrl}" target="_blank">{$gtin}</a>
HTML;
        }

        $html = '<div class="walmart-identifiers-gtin">' . $gtinHtml;

        $showEditIdentifier = ($this->getColumn()->getData('show_edit_identifier') !== null)
                              ? $this->getColumn()->getData('show_edit_identifier')
                              : true;

        if ($showEditIdentifier) {
            $isVariationParent = $row->getData('is_variation_parent');

            if ($row->getData('status') == \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED && !$isVariationParent) {
                $html .= <<<HTML
    &nbsp;&nbsp;<a href="#" class="walmart-identifiers-gtin-edit"
       onclick="ListingGridObj.editChannelDataHandler.showIdentifiersPopup('$productId')">(edit)</a>
HTML;
            }
        }

        $html .= '</div>';

        $identifiers = [
            'UPC'        => $objectRow->getData('upc'),
            'EAN'        => $objectRow->getData('ean'),
            'ISBN'       => $objectRow->getData('isbn'),
            'Walmart ID' => $objectRow->getData('wpid'),
            'Item ID'    => $objectRow->getData('item_id')
        ];

        $htmlAdditional = '';
        foreach ($identifiers as $title => $value) {
            if (empty($value)) {
                continue;
            }

            if (($objectRow->getData('upc') || $objectRow->getData('ean') || $objectRow->getData('isbn')) &&
                ($objectRow->getData('wpid') || $objectRow->getData('item_id')) && $title == 'Walmart ID') {
                $htmlAdditional .= "<div class='separator-line'></div>";
            }

            $identifierCode  = $this->translationHelper->__($title);
            $identifierValue = $this->dataHelper->escapeHtml($value);

            $htmlAdditional .= <<<HTML
<div>
    <span style="display: inline-block; float: left;">
        <strong>{$identifierCode}:</strong>&nbsp;&nbsp;&nbsp;&nbsp;
    </span>
    <span style="display: inline-block; float: right;">
        {$identifierValue}
    </span>
    <div style="clear: both;"></div>
</div>
HTML;
        }

        if ($htmlAdditional != '') {
            $html .= <<<HTML
<span class="fix-magento-tooltip">
    {$this->getTooltipHtml($htmlAdditional)}
</span>
HTML;
        }

        return $html;
    }
}
