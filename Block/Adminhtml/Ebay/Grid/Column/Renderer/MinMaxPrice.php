<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Grid\Column\Renderer;

class MinMaxPrice extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\Number
{
    use \Ess\M2ePro\Block\Adminhtml\Traits\BlockTrait;

    /** @var \Magento\Framework\Locale\CurrencyInterface */
    protected $localeCurrency;

    /** @var \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory */
    protected $ebayFactory;

    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;

    public function __construct(
        \Magento\Framework\Locale\CurrencyInterface $localeCurrency,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Magento\Backend\Block\Context $context,
        \Ess\M2ePro\Helper\Data $dataHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->localeCurrency = $localeCurrency;
        $this->ebayFactory = $ebayFactory;
        $this->dataHelper = $dataHelper;
    }

    //########################################

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
        if ($row->getData('status') == \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED) {
            if ($isExport) {
                return '';
            }

            return '<span style="color: gray;">' . __('Not Listed') . '</span>';
        }
        $currency = $this->getColumn()->getData('currency');
        $onlineMinPrice = $row->getData('min_online_price');
        $onlineMaxPrice = $row->getData('max_online_price');
        $onlineStartPrice = $row->getData('online_start_price');
        $onlineCurrentPrice = $row->getData('online_current_price');

        if ($onlineMinPrice === null || $onlineMinPrice === '') {
            if ($isExport) {
                return '';
            }

            return __('N/A');
        }

        if ((float)$onlineMinPrice <= 0) {
            if ($isExport) {
                return 0;
            }

            return '<span style="color: #f00;">0</span>';
        }

        if (!empty($onlineStartPrice)) {
            $onlineReservePrice = $row->getData('online_reserve_price');
            $onlineBuyItNowPrice = $row->getData('online_buyitnow_price');

            $onlineStartStr = $this->localeCurrency->getCurrency($currency)->toCurrency($onlineStartPrice);

            if ($isExport) {
                return $onlineStartStr;
            }

            $startPriceText = __('Start Price');

            $onlineCurrentPriceHtml = '';
            $onlineReservePriceHtml = '';
            $onlineBuyItNowPriceHtml = '';

            if ($row->getData('online_bids') > 0 || $onlineCurrentPrice > $onlineStartPrice) {
                $currentPriceText = __('Current Price');
                $onlineCurrentStr = $this->localeCurrency->getCurrency($currency)->toCurrency($onlineCurrentPrice);
                $onlineCurrentPriceHtml = '<strong>' . $currentPriceText . ':</strong> ' . $onlineCurrentStr . '<br/><br/>';
            }

            if ($onlineReservePrice > 0) {
                $reservePriceText = __('Reserve Price');
                $onlineReserveStr = $this->localeCurrency->getCurrency($currency)->toCurrency($onlineReservePrice);
                $onlineReservePriceHtml = '<strong>' . $reservePriceText . ':</strong> ' . $onlineReserveStr . '<br/>';
            }

            if ($onlineBuyItNowPrice > 0) {
                $buyItNowText = __('Buy It Now Price');
                $onlineBuyItNowStr = $this->localeCurrency->getCurrency($currency)->toCurrency($onlineBuyItNowPrice);
                $onlineBuyItNowPriceHtml = '<strong>' . $buyItNowText . ':</strong> ' . $onlineBuyItNowStr;
            }

            $intervalHtml = $this->getTooltipHtml(
                <<<HTML
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
        } else {
            $onlineMinPriceStr = $this->localeCurrency->getCurrency($currency)->toCurrency($onlineMinPrice);
            $onlineMaxPriceStr = $this->localeCurrency->getCurrency($currency)->toCurrency($onlineMaxPrice);

            if ($isExport) {
                if ($onlineMinPrice != $onlineMaxPrice) {
                    return $onlineMinPriceStr . ' - ' . $onlineMaxPriceStr;
                }

                return $onlineMinPriceStr;
            }

            $resultHtml = '<span class="product-price-value">' . $onlineMinPriceStr . '</span>' .
                (($onlineMinPrice != $onlineMaxPrice) ? ' - ' . $onlineMaxPriceStr : '');
        }

        $listingProductId = (int)$row->getData('listing_product_id');
        /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
        $listingProduct = $this->ebayFactory->getObjectLoaded('Listing\Product', $listingProductId);
        $onlineBids = $listingProduct->getChildObject()->getOnlineBids();

        if ($onlineBids) {
            $title = $row->getName();

            $onlineTitle = $row->getData('online_title');
            !empty($onlineTitle) && $title = $onlineTitle;

            $title = $this->dataHelper->escapeHtml($title);

            $bidsPopupTitle = __('Bids of &quot;%1&quot;', $title);
            $bidsPopupTitle = addslashes($bidsPopupTitle);

            $bidsTitle = __('Show bids list');
            $bidsText = __('Bid(s)');

            if ($listingProduct->getStatus() == \Ess\M2ePro\Model\Listing\Product::STATUS_STOPPED) {
                $resultHtml .= '<br/><br/><span style="font-size: 10px; color: gray;">' .
                    $onlineBids . ' ' . $bidsText . '</span>';
            } else {
                $resultHtml .= <<<HTML
<br/>
<br/>
<a class="m2ePro-ebay-auction-bids-link"
    href="javascript:void(0)"
    title="{$bidsTitle}"
    onclick="EbayListingViewEbayGridObj
        .listingProductBidsHandler.openPopUp({$listingProductId},'{$bidsPopupTitle}')"
>{$onlineBids} {$bidsText}</a>
HTML;
            }
        }

        return $resultHtml;
    }
}
