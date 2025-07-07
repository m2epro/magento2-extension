<?php

declare(strict_types=1);

namespace Ess\M2ePro\Block\Adminhtml\Wizard\InstallationWalmart\Installation\Account;

class MarketplaceSelector extends \Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock
{
    protected $_template = 'wizard/installationWalmart/installation/account/marketplace_selector.phtml';

    private \Ess\M2ePro\Model\Walmart\Marketplace\Repository $marketplaceRepository;

    public function __construct(
        \Ess\M2ePro\Model\Walmart\Marketplace\Repository $marketplaceRepository,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        array $data = []
    ) {
        $this->marketplaceRepository = $marketplaceRepository;
        parent::__construct($context, $data);
    }

    /**
     * @return \Ess\M2ePro\Model\Marketplace[]
     */
    public function getMarketplaces(): array
    {
        return $this->marketplaceRepository->findWithDeveloperKey();
    }

    public function getConnectButton(): \Ess\M2ePro\Block\Adminhtml\Magento\Button
    {
        $url = $this->getUrl(
            '*/wizard_installationWalmart/beforeGetToken',
            [
                'marketplace_id' => \Ess\M2ePro\Helper\Component\Walmart::MARKETPLACE_US,
                '_current' => true,
            ]
        );

        return $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Magento\Button::class)->addData(
            [
                'id' => 'account_us_connect',
                'label' => __('Connect'),
                'onclick' => 'setLocation(\'' . $url . '\')',
                'class' => 'check M2ePro_check_button primary',
                'style' => 'display: none;',
            ]
        );
    }
}
