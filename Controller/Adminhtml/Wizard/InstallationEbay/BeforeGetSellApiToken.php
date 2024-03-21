<?php

namespace Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationEbay;

class BeforeGetSellApiToken extends \Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationEbay
{
    /** @var \Ess\M2ePro\Helper\View\Configuration */
    private $configurationHelper;

    /** @var \Ess\M2ePro\Helper\Module\Exception */
    private $exceptionHelper;

    /** @var \Ess\M2ePro\Helper\Module\License */
    private $licenseHelper;
    /** @var \Ess\M2ePro\Model\Ebay\Connector\DispatcherFactory */
    private $dispatcherFactory;

    public function __construct(
        \Ess\M2ePro\Model\Ebay\Connector\DispatcherFactory $dispatcherFactory,
        \Ess\M2ePro\Helper\Module\Exception $exceptionHelper,
        \Ess\M2ePro\Helper\Module\License $licenseHelper,
        \Ess\M2ePro\Helper\View\Configuration $configurationHelper,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Helper\View\Ebay $ebayViewHelper,
        \Magento\Framework\Code\NameBuilder $nameBuilder,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($ebayFactory, $ebayViewHelper, $nameBuilder, $context);

        $this->dispatcherFactory = $dispatcherFactory;
        $this->configurationHelper = $configurationHelper;
        $this->exceptionHelper = $exceptionHelper;
        $this->licenseHelper = $licenseHelper;
    }

    public function execute()
    {
        $accountMode = $this->getRequest()->getParam('mode');
        $mode = ($accountMode == 'production')
            ? \Ess\M2ePro\Model\Ebay\Account::MODE_PRODUCTION
            : \Ess\M2ePro\Model\Ebay\Account::MODE_SANDBOX;

        if ($accountMode === null) {
            $this->setJsonContent([
                'message' => 'Account type have not been specified.',
            ]);

            return $this->getResult();
        }

        try {
            $backUrl = $this->getUrl('*/*/afterGetSellApiToken', ['mode' => $mode]);

            /** @var \Ess\M2ePro\Model\Ebay\Connector\Account\Get\GrantAccessUrl $connectorObj */
            $connectorObj = $this->dispatcherFactory
                ->create()
                ->getConnector(
                    'account',
                    'get',
                    'grantAccessUrl',
                    [
                        'mode' => $accountMode,
                        'back_url' => $backUrl,
                    ]
                );

            $connectorObj->process();
            $response = $connectorObj->getResponseData();
        } catch (\Exception $exception) {
            $this->exceptionHelper->process($exception);

            $this->modelFactory->getObject('Servicing\Dispatcher')->processTask(
                \Ess\M2ePro\Model\Servicing\Task\License::NAME
            );

            $error = 'The eBay token obtaining is currently unavailable.<br/>Reason: %error_message%';

            if (
                !$this->licenseHelper->isValidDomain() ||
                !$this->licenseHelper->isValidIp()
            ) {
                $error .= '</br>Go to the <a href="%url%" target="_blank">License Page</a>.';
                $error = $this->__(
                    $error,
                    $exception->getMessage(),
                    $this->configurationHelper->getLicenseUrl(['wizard' => 1])
                );
            } else {
                $error = $this->__($error, $exception->getMessage());
            }

            $this->setJsonContent([
                'type' => 'error',
                'message' => $error,
            ]);

            return $this->getResult();
        }

        if (!$response || !isset($response['url'])) {
            $this->setJsonContent([
                'url' => null,
            ]);

            return $this->getResult();
        }

        $this->setJsonContent([
            'url' => $response['url'],
        ]);

        return $this->getResult();
    }
}
