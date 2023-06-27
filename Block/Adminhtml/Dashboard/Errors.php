<?php

namespace Ess\M2ePro\Block\Adminhtml\Dashboard;

class Errors extends \Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock
{
    /** @var string */
    protected $_template = 'Ess_M2ePro::dashboard/errors.phtml';
    /** @var \Ess\M2ePro\Model\Dashboard\Errors\CalculatorInterface */
    private $calculator;
    /** @var \Ess\M2ePro\Block\Adminhtml\Dashboard\Errors\UrlStorageInterface */
    private $urlStorage;

    public function __construct(
        \Ess\M2ePro\Model\Dashboard\Errors\CalculatorInterface $calculator,
        \Ess\M2ePro\Block\Adminhtml\Dashboard\Errors\UrlStorageInterface $urlStorage,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->calculator = $calculator;
        $this->urlStorage = $urlStorage;
    }

    /** @return array<array{title:string, value:int, url:string|null}> */
    public function getCardItems(): array
    {
        return [
            [
                'title' => __('Today'),
                'value' => $this->calculator->getCountForToday(),
                'url' => $this->urlStorage->getUrlForToday(),
            ],
            [
                'title' => __('Yesterday'),
                'value' => $this->calculator->getCountForYesterday(),
                'url' => $this->urlStorage->getUrlForYesterday(),
            ],
            [
                'title' => __('2 days ago'),
                'value' => $this->calculator->getCountFor2DaysAgo(),
                'url' => $this->urlStorage->getUrlFor2DaysAgo(),
            ],
            [
                'title' => __('Total'),
                'value' => $this->calculator->getTotalCount(),
                'url' => $this->urlStorage->getUrlForTotal(),
            ],
        ];
    }
}
