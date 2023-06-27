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
                'title' => __('Late Shipment'),
                'value' => $this->calculator->getCountOfLateShipments(),
                'url' => $this->urlStorage->getUrlForLateShipments(),
            ],
            [
                'title' => __('Ship by Today'),
                'value' => $this->calculator->getCountOfShipByToday(),
                'url' => $this->urlStorage->getUrlForShipByToday(),
            ],
            [
                'title' => __('Ship by Tomorrow'),
                'value' => $this->calculator->getCountOfShipByTomorrow(),
                'url' => $this->urlStorage->getUrlForShipByTomorrow(),
            ],
            [
                'title' => __('Ship by 2+ Days'),
                'value' => $this->calculator->getCountForTwoAndMoreDays(),
                'url' => $this->urlStorage->getUrlForTwoAndMoreDays(),
            ],
        ];
    }
}
