<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace  Ess\M2ePro\Block\Adminhtml\Walmart\Grid\Column\Renderer;

use Ess\M2ePro\Block\Adminhtml\Traits;

/**
 * Class  \Ess\M2ePro\Block\Adminhtml\Walmart\Grid\Column\Renderer\Sku
 */
class Sku extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\Text
{
    use Traits\BlockTrait;

    /** @var \Ess\M2ePro\Helper\Factory  */
    protected $helperFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Backend\Block\Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->helperFactory = $helperFactory;
    }

    //########################################

    public function render(\Magento\Framework\DataObject $row)
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
            $value = $this->getHelper('Module\Translation')->__('N/A');
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

        if (!$isVariationParent && $row->getData('is_online_price_invalid')) {

            $message = <<<HTML
Item Price violates Walmart pricing rules. Please adjust the Item Price to comply with the Walmart requirements.<br>
Once the changes are applied, Walmart Item will become Active automatically.
HTML;
            $msg = '<p>' . $this->getHelper('Module\Translation')->__($message) . '</p>';
            if (empty($msg)) {
                return $value;
            }

            $value .= <<<HTML
<span class="fix-magento-tooltip">
    {$this->getTooltipHtml($msg, 'map_link_defected_message_icon_'.$row->getId())}
</span>
HTML;
        }

        return $value;
    }

    //########################################
}
