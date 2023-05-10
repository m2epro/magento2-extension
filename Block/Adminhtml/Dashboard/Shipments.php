<?php

namespace Ess\M2ePro\Block\Adminhtml\Dashboard;

class Shipments extends \Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock
{
    /** @var \Ess\M2ePro\Model\Dashboard\Shipments\CalculatorInterface */
    private $calculator;
    /** @var \Ess\M2ePro\Block\Adminhtml\Dashboard\Shipments\UrlStorageInterface */
    private $urlStorage;
    /** @var string */
    protected $_template = 'Ess_M2ePro::dashboard/shipments.phtml';

    public function __construct(
        \Ess\M2ePro\Model\Dashboard\Shipments\CalculatorInterface $calculator,
        \Ess\M2ePro\Block\Adminhtml\Dashboard\Shipments\UrlStorageInterface $urlStorage,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->calculator = $calculator;
        $this->urlStorage = $urlStorage;
    }

    /** @return array<array{title:string, value:int, url:string}> */
    public function getCardItems(): array
    {
        return [
            [
                'title' => __('Late Shipments'),
                'value' => $this->calculator->getCountOfLateShipments(),
                'url' => $this->urlStorage->getUrlForLateShipments(),
            ],
            [
                'title' => __('By Today'),
                'value' => $this->calculator->getCountForToday(),
                'url' => $this->urlStorage->getUrlForToday(),
            ],
            [
                'title' => __('By 2+ days'),
                'value' => $this->calculator->getCountByOver2Days(),
                'url' => $this->urlStorage->getUrlForOver2Days(),
            ],
            [
                'title' => __('Total'),
                'value' => $this->calculator->getTotalCount(),
                'url' => $this->urlStorage->getUrlForTotal(),
            ],
        ];
    }
}
