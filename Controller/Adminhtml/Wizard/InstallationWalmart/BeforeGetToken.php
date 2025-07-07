<?php

declare(strict_types=1);

namespace Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationWalmart;

use Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationWalmart;

class BeforeGetToken extends InstallationWalmart
{
    private \Ess\M2ePro\Helper\Module\Exception $exceptionHelper;
    private \Ess\M2ePro\Helper\Module\License $licenseHelper;
    private \Ess\M2ePro\Helper\View\Configuration $configurationHelper;
    private \Ess\M2ePro\Model\Walmart\Connector\Account\GetGrantAccessUrl\Processor $connectProcessor;
    private \Ess\M2ePro\Model\Servicing\Dispatcher $servicingDispatcher;

    public function __construct(
        \Ess\M2ePro\Helper\Module\Exception $exceptionHelper,
        \Ess\M2ePro\Helper\Module\License $licenseHelper,
        \Ess\M2ePro\Helper\View\Configuration $configurationHelper,
        \Ess\M2ePro\Model\Walmart\Connector\Account\GetGrantAccessUrl\Processor $connectProcessor,
        \Ess\M2ePro\Model\Servicing\Dispatcher $servicingDispatcher,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Helper\View\Walmart $walmartViewHelper,
        \Magento\Framework\Code\NameBuilder $nameBuilder,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($walmartFactory, $walmartViewHelper, $nameBuilder, $context);

        $this->exceptionHelper = $exceptionHelper;
        $this->licenseHelper = $licenseHelper;
        $this->configurationHelper = $configurationHelper;
        $this->connectProcessor = $connectProcessor;
        $this->servicingDispatcher = $servicingDispatcher;
    }

    public function execute(): void
    {
        try {
            $backUrl = $this->getUrl(
                '*/wizard_installationWalmart/afterGetToken',
                [
                    '_current' => true,
                ]
            );

            $response = $this->connectProcessor->process($backUrl);
        } catch (\Throwable $throwable) {
            $this->exceptionHelper->process($throwable);

            $this->servicingDispatcher->processTask(\Ess\M2ePro\Model\Servicing\Task\License::NAME);

            $error = (string)__(
                'The Walmart token obtaining is currently unavailable.<br/>Reason: %error_message',
                ['error_message' => $throwable->getMessage()]
            );

            if (
                !$this->licenseHelper->isValidDomain() ||
                !$this->licenseHelper->isValidIp()
            ) {
                $error .= '</br>Go to the <a href="%url%" target="_blank">License Page</a>.';
                $error = __(
                    $error,
                    $throwable->getMessage(),
                    $this->configurationHelper->getLicenseUrl(['wizard' => 1])
                );
            } else {
                $error = __($error, $throwable->getMessage());
            }

            $this->messageManager->addError($error);
            $this->_redirect($this->_redirect->getRefererUrl());

            return;
        }

        $this->_redirect($response->getUrl());
    }
}
