<?php

declare(strict_types=1);

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Template\SellingFormat\Edit\Form;

class RepricerStrategy extends \Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock
{
    protected $_template = 'walmart/template/sellingFormat/form/repricer_strategy.phtml';

    private \Magento\Framework\Data\Form\Element\Factory $elementFactory;
    private \Magento\Framework\Data\Form $form;
    private \Ess\M2ePro\Model\Walmart\Account\Repository $accountRepository;
    private \Ess\M2ePro\Model\Walmart\Template\SellingFormat\Repricer\AccountStrategiesLoader $accountStrategiesLoader;
    private array $formData;

    public function __construct(
        \Ess\M2ePro\Model\Walmart\Template\SellingFormat\Repricer\AccountStrategiesLoader $accountStrategiesLoader,
        \Ess\M2ePro\Model\Walmart\Account\Repository $accountRepository,
        \Magento\Framework\Data\Form $form,
        array $formData,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->elementFactory = $context->getElementFactory();
        $this->form = $form;
        $this->accountRepository = $accountRepository;
        $this->accountStrategiesLoader = $accountStrategiesLoader;
        $this->formData = $formData;
    }

    public function createDropDown(\Ess\M2ePro\Model\Account $account)
    {
        $values = [
            [
                'value' => '',
                'label' => __('None'),
            ],
        ];

        $strategies = $this->accountStrategiesLoader->execute($account, false);
        foreach ($strategies as $strategy) {
            $values[] = [
                'value' => $strategy->id,
                'label' => $strategy->title,
            ];
        }

        $element = $this->elementFactory->create(
            \Ess\M2ePro\Block\Adminhtml\Magento\Form\Element\Select::class,
            [
                'data' => [
                    'html_id' => sprintf('repricer_strategy_select_%s', $account->getId()),
                    'name' => sprintf('repricer_account_strategies[%s]', $account->getId()),
                    'values' => $values,
                    'value' => $this->getStrategyValue((int)$account->getId()),
                    'tooltip' => __(
                        'Select a strategy that will determine how your product price should ' .
                        'be automatically adjusted within the Min and Max Price range.'
                    ),
                ],
            ]
        );

        $element->setRenderer(
            $this
                ->getLayout()
                ->createBlock(\Ess\M2ePro\Block\Adminhtml\Magento\Form\Renderer\Element::class)
        );

        $element->setForm($this->form);

        return $element->toHtml();
    }

    /**
     * @return \Ess\M2ePro\Model\Account[]
     */
    public function getAccounts(): array
    {
        $accounts = $this->accountRepository->getAllItems();

        $result = [];
        foreach ($accounts as $account) {
            if ($this->isCanadaMarketplace($account)) {
                continue;
            }

            $result[] = $account;
        }

        return $result;
    }

    public function createRefreshLink($account): string
    {
        return sprintf(
            '<a href="javascript:void(0)" class="refresh_account_repricer_strategy" data-account-id="%s">%s</a>',
            $account->getId(),
            __('Refresh')
        );
    }

    public function getStrategiesUrl(): string
    {
        return $this->getUrl('*/walmart_template_sellingFormat/getRepricerStrategies');
    }

    private function isCanadaMarketplace(\Ess\M2ePro\Model\Account $account): bool
    {
        return $account->getChildObject()->getMarketplaceId()
            === \Ess\M2ePro\Helper\Component\Walmart::MARKETPLACE_CA;
    }

    private function getStrategyValue(int $accountId): string
    {
        foreach ($this->formData['repricer_account_strategies'] as $strategyData) {
            if ($strategyData['account_id'] === $accountId) {
                return $strategyData['strategy_id'];
            }
        }

        return '';
    }
}
