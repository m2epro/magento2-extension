<?php

declare(strict_types=1);

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Template\ProductType;

class ActionColumn extends \Magento\Ui\Component\Listing\Columns\Column
{
    private \Magento\Framework\UrlInterface $url;

    public function __construct(
        \Magento\Framework\UrlInterface $url,
        \Magento\Framework\View\Element\UiComponent\ContextInterface $context,
        \Magento\Framework\View\Element\UiComponentFactory $uiComponentFactory,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->url = $url;
    }

    public function prepareDataSource(array $dataSource): array
    {
        if (!isset($dataSource['data']['items'])) {
            return $dataSource;
        }

        $name = $this->getData('name');
        foreach ($dataSource['data']['items'] as &$item) {
            $buttons = [
                'enable' => [
                    'href' => $this->url->getUrl(
                        'm2epro/amazon_template_productType/edit',
                        [
                            'id' => $item['id'],
                            'back' => 1,
                        ],
                    ),
                    'label' => __('Edit'),
                ],
                'delete' => [
                    'href' => $this->url->getUrl(
                        'm2epro/amazon_template_productType/delete',
                        [
                            'id' => $item['id'],
                        ],
                    ),
                    'confirm' => [
                        'message' => 'Are you sure?',
                    ],
                    'label' => __('Delete'),
                ],
            ];

            $item[$name] = $buttons;
        }

        return $dataSource;
    }
}
