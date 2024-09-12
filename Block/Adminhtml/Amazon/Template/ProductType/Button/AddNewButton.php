<?php

declare(strict_types=1);

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Template\ProductType\Button;

class AddNewButton implements \Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface
{
    private \Magento\Backend\Model\UrlInterface $urlBuilder;

    public function __construct(
        \Magento\Backend\Model\UrlInterface $urlBuilder
    ) {
        $this->urlBuilder = $urlBuilder;
    }

    public function getButtonData(): array
    {
        $url = $this->urlBuilder->getUrl('*/amazon_template_productType/edit');

        return [
            'label' => __('Add Product Type'),
            'class' => 'action-primary',
            'on_click' => 'setLocation(\'' . $url . '\')',
            'sort_order' => 4,
        ];
    }
}
