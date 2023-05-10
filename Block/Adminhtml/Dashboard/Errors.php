<?php

namespace Ess\M2ePro\Block\Adminhtml\Dashboard;

class Errors extends \Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock
{
    /** @var string */
    protected $_template = 'Ess_M2ePro::dashboard/errors.phtml';
    /** @var \Ess\M2ePro\Model\Dashboard\Errors\CalculatorInterface */
    private $calculator;
    /** @var \Ess\M2ePro\Block\Adminhtml\Dashboard\Errors\UrlStorageInterface|null */
    private $urlStorage = null;

    public function __construct(
        \Ess\M2ePro\Model\Dashboard\Errors\CalculatorInterface $calculator,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->calculator = $calculator;
    }

    /** @return array<array{title:string, value:int, url:string|null}> */
    public function getCardItems(): array
    {
        return [
            [
                'title' => __('Today'),
                'value' => $this->calculator->getCountForToday(),
                'url' => $this->urlStorage ? $this->urlStorage->getUrlForToday() : null,
            ],
            [
                'title' => __('Yesterday'),
                'value' => $this->calculator->getCountForYesterday(),
                'url' => $this->urlStorage ? $this->urlStorage->getUrlForYesterday() : null,
            ],
            [
                'title' => __('2 days ago'),
                'value' => $this->calculator->getCountFor2DaysAgo(),
                'url' => $this->urlStorage ? $this->urlStorage->getUrlFor2DaysAgo() : null,
            ],
            [
                'title' => __('Total'),
                'value' => $this->calculator->getTotalCount(),
                'url' => $this->urlStorage ? $this->urlStorage->getUrlForTotal() : null,
            ],
        ];
    }

    public function setUrlStorage(Errors\UrlStorageInterface $urlStorage): void
    {
        $this->urlStorage = $urlStorage;
    }
}
