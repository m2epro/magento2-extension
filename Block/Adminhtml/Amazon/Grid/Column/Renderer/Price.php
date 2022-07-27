<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace  Ess\M2ePro\Block\Adminhtml\Amazon\Grid\Column\Renderer;

use Ess\M2ePro\Block\Adminhtml\Traits;

class Price extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\Text
{
    use Traits\BlockTrait;

    /** @var \Ess\M2ePro\Helper\Factory  */
    protected $helperFactory;

    /** @var \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory  */
    protected $amazonFactory;

    /** @var \Magento\Framework\Locale\CurrencyInterface  */
    protected $localeCurrency;

    /** @var \Ess\M2ePro\Helper\Data */
    protected $helperData;

    /** @var \Ess\M2ePro\Helper\Module\Translation */
    private $translationHelper;

    /** @var \Ess\M2ePro\Helper\Component\Amazon\Repricing */
    private $amazonRepricingHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\Locale\CurrencyInterface $localeCurrency,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Helper\Data $helperData,
        \Magento\Backend\Block\Context $context,
        \Ess\M2ePro\Helper\Module\Translation $translationHelper,
        \Ess\M2ePro\Helper\Component\Amazon\Repricing $amazonRepricingHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->helperFactory = $helperFactory;
        $this->amazonFactory = $amazonFactory;
        $this->localeCurrency = $localeCurrency;
        $this->helperData = $helperData;
        $this->translationHelper = $translationHelper;
        $this->amazonRepricingHelper = $amazonRepricingHelper;
    }

    public function render(\Magento\Framework\DataObject $row)
    {
        $rowObject = $row;
        $isVariationGrid = ($this->getColumn()->getData('is_variation_grid') !== null)
            ? $this->getColumn()->getData('is_variation_grid')
            : false;

        if ($isVariationGrid) {
            $rowObject = $row->getChildObject();
        }

        if ($row->getData('amazon_status') == \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED) {
            return '<span style="color: gray;">' . $this->translationHelper->__('Not Listed') . '</span>';
        }

        if ($row->getData('amazon_status') == \Ess\M2ePro\Model\Listing\Product::STATUS_BLOCKED) {
            return $this->translationHelper->__('N/A');
        }

        $onlineRegularPrice  = $rowObject->getData('online_regular_price');
        $onlineBusinessPrice = $rowObject->getData('online_business_price');

        $repricingHtml ='';

        if ($this->amazonRepricingHelper->isEnabled() &&
            (bool)(int)$rowObject->getData('is_repricing')) {
            $icon = 'repricing-enabled';
            $text = $this->translationHelper->__(
                'This Product is used by Amazon Repricing Tool, so its Price cannot be managed via M2E Pro. <br>
                 <strong>Please note</strong> that the Price value(s) shown in the grid might
                 be different from the actual one from Amazon. It is caused by the delay
                 in the values updating made via the Repricing Service.'
            );

            if ((int)$row->getData('is_repricing_disabled') == 1) {
                $icon = 'repricing-disabled';
                $text = $this->translationHelper->__(
                    'This Item is disabled or unable to be repriced on Amazon Repricing Tool.
                     Its Price is updated via M2E Pro.'
                );
            }

            $repricingHtml = <<<HTML
<div class="fix-magento-tooltip {$icon}" style="float:right; text-align: left; margin-left: 5px;">
    {$this->getTooltipHtml($text)}
</div>
HTML;
        }

        if (($onlineRegularPrice === null || $onlineRegularPrice === '') &&
            ($onlineBusinessPrice === null || $onlineBusinessPrice === '')
        ) {
            return '<i style="color:gray;">receiving...</i>' . $repricingHtml;
        }

        $marketplaceId = $this->getColumn()->getData('marketplace_id');
        $currency = $this->amazonFactory
            ->getCachedObjectLoaded('Marketplace', $marketplaceId)
            ->getChildObject()
            ->getDefaultCurrency();

        if ((float)$onlineRegularPrice <= 0) {
            $priceValue = '<span style="color: #f00;">0</span>';
        } else {
            $priceValue = $this->localeCurrency->getCurrency($currency)->toCurrency($onlineRegularPrice);
        }

        if ($rowObject->getData('is_repricing') &&
            !$row->getData('is_repricing_disabled') &&
            !$row->getData('is_repricing_inactive')
        ) {
            $accountId = $this->getColumn()->getData('account_id');
            $sku = $rowObject->getData('amazon_sku');

            $priceValue =<<<HTML
<a id="m2epro_repricing_price_value_{$sku}"
   class="m2epro-repricing-price-value"
   sku="{$sku}"
   account_id="{$accountId}"
   href="javascript:void(0)"
   onclick="AmazonListingProductRepricingPriceObj.showRepricingPrice()">{$priceValue}</a>
HTML;
        }

        $resultHtml = '';

        $salePrice = $rowObject->getData('online_regular_sale_price');
        if ((float)$salePrice > 0) {
            $currentTimestamp = (int)$this->helperData->createGmtDateTime(
                $this->helperData->getCurrentGmtDate(false, 'Y-m-d 00:00:00')
            )->format('U');

            $startDateTimestamp = (int)$this->helperData->createGmtDateTime(
                $rowObject->getData('online_regular_sale_price_start_date')
            )->format('U');
            $endDateTimestamp = (int)$this->helperData->createGmtDateTime(
                $rowObject->getData('online_regular_sale_price_end_date')
            )->format('U');

            if ($currentTimestamp <= $endDateTimestamp) {
                $fromDate = $this->_localeDate->formatDate(
                    $rowObject->getData('online_regular_sale_price_start_date'),
                    \IntlDateFormatter::MEDIUM
                );

                $toDate = $this->_localeDate->formatDate(
                    $rowObject->getData('online_regular_sale_price_end_date'),
                    \IntlDateFormatter::MEDIUM
                );

                $intervalHtml = <<<HTML
<span style="color: gray;">
    <strong>From:</strong> {$fromDate}<br/>
    <strong>To:</strong> {$toDate}
</span>
HTML;
                $intervalHtml = $this->getTooltipHtml($intervalHtml, '', ['m2epro-field-tooltip-price-info']);
                $salePriceValue = $this->localeCurrency->getCurrency($currency)->toCurrency($salePrice);

                if ($currentTimestamp >= $startDateTimestamp &&
                    $currentTimestamp <= $endDateTimestamp &&
                    $salePrice < (float)$onlineRegularPrice
                ) {
                    $resultHtml .= '<span style="color: grey; text-decoration: line-through;">'.$priceValue.'</span>' .
                                    $repricingHtml;
                    $resultHtml .= '<br/>'.$intervalHtml.'&nbsp;'.$salePriceValue;
                } else {
                    $resultHtml .= $priceValue . $repricingHtml;
                    $resultHtml .= '<br/>'.$intervalHtml.
                        '<span style="color:gray;">'.'&nbsp;'.$salePriceValue.'</span>';
                }
            }
        }

        if (empty($resultHtml)) {
            $resultHtml = $priceValue . $repricingHtml;
        }

        if ((float)$onlineBusinessPrice > 0) {
            $businessPriceValue = '<strong>B2B:</strong> '
                . $this->localeCurrency->getCurrency($currency)->toCurrency($onlineBusinessPrice);

            $businessDiscounts = $rowObject->getData('online_business_discounts');
            if (!empty($businessDiscounts) && $businessDiscounts = json_decode($businessDiscounts, true)) {
                $discountsHtml = '';

                foreach ($businessDiscounts as $qty => $price) {
                    $price = $this->localeCurrency->getCurrency($currency)->toCurrency($price);
                    $discountsHtml .= 'QTY >= '.(int)$qty.', price '.$price.'<br />';
                }

                $discountsHtml = $this->getTooltipHtml($discountsHtml, '', ['m2epro-field-tooltip-price-info']);
                $businessPriceValue = $discountsHtml .'&nbsp;'. $businessPriceValue;
            }

            if (!empty($resultHtml)) {
                $businessPriceValue = '<br />'.$businessPriceValue;
            }

            $resultHtml .= $businessPriceValue;
        }

        return $resultHtml;
    }

    //########################################

    public function getTooltipHtml($content, $id = '', $classes = [])
    {
        $classes = implode(' ', $classes);

        return <<<HTML
    <div id="{$id}" class="m2epro-field-tooltip admin__field-tooltip {$classes}">
        <a class="admin__field-tooltip-action" href="javascript://"></a>
        <div class="admin__field-tooltip-content" style="">
            {$content}
        </div>
    </div>
HTML;
    }

    //########################################
}
