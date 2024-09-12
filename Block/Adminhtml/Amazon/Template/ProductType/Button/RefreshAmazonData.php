<?php

declare(strict_types=1);

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Template\ProductType\Button;

class RefreshAmazonData implements \Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface
{
    private \Magento\Backend\Model\UrlInterface $urlBuilder;

    public function __construct(
        \Magento\Backend\Model\UrlInterface $urlBuilder
    ) {
        $this->urlBuilder = $urlBuilder;
    }

    public function getButtonData(): array
    {
        return [
            'label' => __('Refresh Amazon Data'),
            'class' => 'save update_all_marketplace primary',
            'on_click' => '',
            'sort_order' => 4,
            'data_attribute' => [
                'mage-init' => [
                    'M2ePro/Amazon/Marketplace/Sync' => [
                        'url_for_get_marketplaces' => $this->urlBuilder->getUrl(
                            '*/amazon_marketplace_sync/getMarketplaceList'
                        ),
                        'url_for_update_marketplaces_details' => $this->urlBuilder->getUrl(
                            '*/amazon_marketplace_sync/updateDetails'
                        ),
                        'url_for_get_product_types' => $this->urlBuilder->getUrl(
                            '*/amazon_marketplace_sync/getProductTypeList'
                        ),
                        'url_for_update_product_type' => $this->urlBuilder->getUrl(
                            '*/amazon_marketplace_sync/updateProductType'
                        ),
                        'progress_bar_el_id' => 'product_type_progress_bar',
                    ],
                ],
            ],
        ];
    }
}
