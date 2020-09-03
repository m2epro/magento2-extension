<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Grid\Column\Renderer;

use \Ess\M2ePro\Block\Adminhtml\Traits;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Ebay\Grid\Column\Renderer\CurrentPrice
 */
class CurrentPrice extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\Number
{
    use Traits\BlockTrait;

    /** @var \Ess\M2ePro\Model\Factory */
    protected $modelFactory;

    /** @var \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory  */
    protected $ebayFactory;

    /** @var \Magento\Framework\Locale\CurrencyInterface  */
    protected $localeCurrency;

    /** @var \Ess\M2ePro\Helper\Factory  */
    protected $helperFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Magento\Framework\Locale\CurrencyInterface $localeCurrency,
        \Magento\Backend\Block\Context $context,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->helperFactory = $helperFactory;
        $this->modelFactory = $modelFactory;
        $this->ebayFactory = $ebayFactory;
        $this->localeCurrency = $localeCurrency;
    }

    //########################################

    public function render(\Magento\Framework\DataObject $row)
    {
        $translator = $this->getHelper('Module\Translation');
        if ($row->getData('status') == \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED) {
            return '<span style="color: gray;">' . $translator->__('Not Listed') . '</span>';
        }

        $onlineStartPrice   = $row->getData('online_start_price');
        $onlineCurrentPrice = $row->getData('online_current_price');

        if ($onlineCurrentPrice === null || $onlineCurrentPrice === '') {
            return $translator->__('N/A');
        }

        if ((float)$onlineCurrentPrice <= 0) {
            return '<span style="color: #f00;">0</span>';
        }

        $currency = $row->getCurrency();

        if (strpos($currency, ',') !== false) {
            $currency = $this->ebayFactory
                ->getObjectLoaded('Marketplace', $row->getMarketplaceId())
                ->getChildObject()->getCurrency();
        }

        if (!empty($onlineStartPrice)) {
            $onlineReservePrice  = $row->getData('online_reserve_price');
            $onlineBuyItNowPrice = $row->getData('online_buyitnow_price');

            $onlineStartStr = $this->localeCurrency->getCurrency($currency)->toCurrency($onlineStartPrice);

            $startPriceText = $translator->__('Start Price');

            $onlineCurrentPriceHtml  = '';
            $onlineReservePriceHtml  = '';
            $onlineBuyItNowPriceHtml = '';

            if ($row->getData('online_bids') > 0 || $onlineCurrentPrice > $onlineStartPrice) {
                $currentPriceText = $translator->__('Current Price');
                $onlineCurrentStr = $this->localeCurrency->getCurrency($currency)->toCurrency($onlineCurrentPrice);
                $onlineCurrentPriceHtml = '<strong>'.$currentPriceText.':</strong> '.$onlineCurrentStr.'<br/><br/>';
            }

            if ($onlineReservePrice > 0) {
                $reservePriceText = $translator->__('Reserve Price');
                $onlineReserveStr = $this->localeCurrency->getCurrency($currency)->toCurrency($onlineReservePrice);
                $onlineReservePriceHtml = '<strong>'.$reservePriceText.':</strong> '.$onlineReserveStr.'<br/>';
            }

            if ($onlineBuyItNowPrice > 0) {
                $buyItNowText = $translator->__('Buy It Now Price');
                $onlineBuyItNowStr = $this->localeCurrency->getCurrency($currency)->toCurrency($onlineBuyItNowPrice);
                $onlineBuyItNowPriceHtml = '<strong>'.$buyItNowText.':</strong> '.$onlineBuyItNowStr;
            }

            $intervalHtml = $this->getTooltipHtml(<<<HTML
<span style="color:gray;">
    {$onlineCurrentPriceHtml}
    <strong>{$startPriceText}:</strong> {$onlineStartStr}<br/>
    {$onlineReservePriceHtml}
    {$onlineBuyItNowPriceHtml}
</span>
HTML
            );
            $intervalHtml = <<<HTML
<div class="fix-magento-tooltip ebay-auction-grid-tooltip">{$intervalHtml}</div>
HTML;

            if ($onlineCurrentPrice > $onlineStartPrice) {
                $resultHtml = '<span style="color: grey; text-decoration: line-through;">' . $onlineStartStr . '</span>';
                $resultHtml .= '<br/>' . $intervalHtml . '&nbsp;' .
                    '<span class="product-price-value">' . $onlineCurrentStr . '</span>';
            } else {
                $resultHtml = $intervalHtml . '&nbsp;' . '<span class="product-price-value">' . $onlineStartStr . '</span>';
            }

            return $resultHtml;
        }

        $noticeHtml = '';
        if ($listingProductId = $row->getData('listing_product_id')) {

            /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
            $listingProduct = $this->ebayFactory->getObjectLoaded('Listing\Product', $listingProductId);
            if ($listingProduct->getChildObject()->isVariationsReady()) {
                $noticeText = $translator->__('The value is calculated as minimum price of all Child Products.');
                $noticeHtml = <<<HTML
<div class="m2epro-field-tooltip admin__field-tooltip" style="display: inline;">
    <a class="admin__field-tooltip-action" href="javascript://" style="margin-left: 0;"></a>
    <div class="admin__field-tooltip-content">
        {$noticeText}
    </div>
</div>
HTML;
            }
        }

        return $noticeHtml .
            '<div style="display: inline;">' .
                '<span class="product-price-value">' .
                     $this->localeCurrency->getCurrency($currency)->toCurrency($onlineCurrentPrice) .
                '</span>' .
            '</div>';
    }

    //########################################
}
