<?php

namespace Ess\M2ePro\Block\Adminhtml\Dashboard;

class ListingProductIssues extends \Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock
{
    /** @var string */
    protected $_template = 'Ess_M2ePro::dashboard/listing_product_issues.phtml';

    /** @var string */
    private $componentNick;
    /** @var \Ess\M2ePro\Helper\Component */
    private $componentHelper;
    /** @var \Magento\Backend\Model\UrlInterface */
    private $urlBuilder;
    /** @var \Ess\M2ePro\Model\Dashboard\Products\CalculatorInterface */
    private $productsCalculator;
    /** @var \Ess\M2ePro\Model\Dashboard\ListingProductIssues\CalculatorInterface|null */
    private $issuesCalculator;

    /**
     * @var $issues list<array{
     *     url: string,
     *     total: int,
     *     impact_rate: float,
     *     text: string,
     *  }>
     */
    private $issues = null;
    /** @var bool */
    private $isIssuesTableVisible = false;
    /** @var string */
    private $notificationMessage = null;

    public function __construct(
        string $componentNick,
        \Ess\M2ePro\Helper\Component $componentHelper,
        \Magento\Backend\Model\UrlInterface $urlBuilder,
        \Ess\M2ePro\Model\Dashboard\Products\CalculatorInterface $productsCalculator,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        ?\Ess\M2ePro\Model\Dashboard\ListingProductIssues\CalculatorInterface $issuesCalculator = null,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->componentNick = $componentNick;
        $this->componentHelper = $componentHelper;
        $this->urlBuilder = $urlBuilder;
        $this->productsCalculator = $productsCalculator;
        $this->issuesCalculator = $issuesCalculator;
    }

    protected function _prepareLayout()
    {
        $this->init();

        return parent::_prepareLayout();
    }

    private function init(): void
    {
        if ($this->issuesCalculator === null) {
            $this->notificationMessage = __("Coming soon...");

            return;
        }

        if ($this->productsCalculator->getCountOfActiveProducts() === 0) {
            $componentTitle = $this->componentHelper->getComponentTitle($this->componentNick);
            if ($componentTitle === null) {
                throw new \Ess\M2ePro\Model\Exception('Invalid component title');
            }

            $message = __("Currently, there are no items listed on %s. Start Listing now to get more sales.");
            $this->notificationMessage = sprintf($message, $componentTitle);

            return;
        }

        if (empty($this->getIssues())) {
            $this->notificationMessage = __("There are no issues at the moment. Keep up the good work!");

            return;
        }

        $this->isIssuesTableVisible = true;
    }

    public function getIssues(): array
    {
        if ($this->issues !== null) {
            return $this->issues;
        }

        if ($this->issuesCalculator === null) {
            return $this->issues = [];
        }

        $issueSet = $this->issuesCalculator->getTopIssues();
        $issues = array_map(function (\Ess\M2ePro\Model\Dashboard\ListingProductIssues\Issue $issue) {
            return [
                'url' => $this->getAllItemsViewUrl($issue->getTagId()),
                'text' => $issue->getText(),
                'total' => $issue->getTotal(),
                'impact_rate' => round($issue->getImpactRate(), 1) . ' %',
            ];
        }, $issueSet->getIssues());

        return $this->issues = $issues;
    }

    private function getAllItemsViewUrl(int $tagId): string
    {
        $map = [
            \Ess\M2ePro\Helper\Component\Ebay::NICK => '*/ebay_listing/allItems',
            \Ess\M2ePro\Helper\Component\Amazon::NICK => '*/amazon_listing/allItems',
            \Ess\M2ePro\Helper\Component\Walmart::NICK => '*/walmart_listing/allItems',
        ];

        if (empty($map[$this->componentNick])) {
            throw new \Ess\M2ePro\Model\Exception('Unresolved component');
        }

        return $this->urlBuilder->getUrl($map[$this->componentNick], [
            \Ess\M2ePro\Block\Adminhtml\Tag\Switcher::TAG_ID_REQUEST_PARAM_KEY => $tagId,
        ]);
    }

    public function getItemsByIssueViewUrl(): ?string
    {
        $map = [
            \Ess\M2ePro\Helper\Component\Ebay::NICK => '*/ebay_listing/itemsByIssue',
            \Ess\M2ePro\Helper\Component\Amazon::NICK => '*/amazon_listing/itemsByIssue',
        ];

        if (empty($map[$this->componentNick])) {
            return null;
        }

        return $this->urlBuilder->getUrl($map[$this->componentNick]);
    }

    protected function _beforeToHtml()
    {
        $this->js->add(
            <<<JS
    require([
        'M2ePro/Dashboard/ListingProductIssues/Table'
    ], function(){
        var table = new DashboardListingProductIssuesTable();
        table.initObservers();
    });
JS
        );

        return parent::_beforeToHtml();
    }

    public function isIssuesTableVisible(): bool
    {
        return $this->isIssuesTableVisible;
    }

    public function getNotificationMessage(): ?string
    {
        return $this->notificationMessage;
    }
}
