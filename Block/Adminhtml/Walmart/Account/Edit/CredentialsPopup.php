<?php

declare(strict_types=1);

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Account\Edit;

class CredentialsPopup extends \Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock
{
    private \Ess\M2ePro\Block\Adminhtml\Walmart\Account\CredentialsFormFactory $credentialsFormFactory;
    private \Magento\Framework\View\Page\Config $config;

    public function __construct(
        \Ess\M2ePro\Block\Adminhtml\Walmart\Account\CredentialsFormFactory $credentialsFormFactory,
        \Magento\Framework\View\Page\Config $config,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        array $data = []
    ) {
        $this->credentialsFormFactory = $credentialsFormFactory;
        $this->config = $config;
        parent::__construct($context, $data);
    }

    protected function _construct()
    {
        parent::_construct();
        $this->config->addPageAsset('Ess_M2ePro::css/walmart/account/credentials.css');
    }

    protected function _prepareLayout()
    {
        $this->addChild('form', \Ess\M2ePro\Block\Adminhtml\Walmart\Account\Edit\Form::class);

        return parent::_prepareLayout();
    }

    protected function _toHtml(): string
    {
        return parent::_toHtml()
            . '<div style="display: none;">'
            . $this->credentialsFormFactory->create(
                false,
                true,
                'account_credentials',
                '',
            )->toHtml()
            . '</div>';
    }
}
