<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationEbay;

use Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationEbay;

class BeforeToken extends InstallationEbay
{
    /** @var \Ess\M2ePro\Helper\View\Configuration */
    private $configurationHelper;

    /** @var \Ess\M2ePro\Helper\Data\Session */
    private $sessionHelper;

    /** @var \Ess\M2ePro\Helper\Module\Exception */
    private $exceptionHelper;

    /** @var \Ess\M2ePro\Helper\Module\License */
    private $licenseHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Data\Session $sessionHelper,
        \Ess\M2ePro\Helper\Module\Exception $exceptionHelper,
        \Ess\M2ePro\Helper\Module\License $licenseHelper,
        \Ess\M2ePro\Helper\View\Configuration $configurationHelper,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Helper\View\Ebay $ebayViewHelper,
        \Magento\Framework\Code\NameBuilder $nameBuilder,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($ebayFactory, $ebayViewHelper, $nameBuilder, $context);

        $this->configurationHelper = $configurationHelper;
        $this->sessionHelper = $sessionHelper;
        $this->exceptionHelper = $exceptionHelper;
        $this->licenseHelper = $licenseHelper;
    }

    public function execute()
    {
        $accountMode = $this->getRequest()->getParam('mode');

        if ($accountMode === null) {
            $this->setJsonContent([
                'message' => 'Account type have not been specified.'
            ]);
            return $this->getResult();
        }

        try {
            $backUrl = $this->getUrl('*/*/afterToken', ['mode' => $accountMode]);

            $dispatcherObject = $this->modelFactory->getObject('Ebay_Connector_Dispatcher');
            $connectorObj = $dispatcherObject->getVirtualConnector(
                'account',
                'get',
                'grandAccessUrl',
                ['back_url' => $backUrl, 'mode' => $accountMode],
                null,
                null,
                null
            );

            $dispatcherObject->process($connectorObj);
            $response = $connectorObj->getResponseData();
        } catch (\Exception $exception) {
            $this->exceptionHelper->process($exception);

            $this->modelFactory->getObject('Servicing\Dispatcher')->processTask(
                \Ess\M2ePro\Model\Servicing\Task\License::NAME
            );

            $error = 'The eBay token obtaining is currently unavailable.<br/>Reason: %error_message%';

            if (!$this->licenseHelper->isValidDomain() ||
                !$this->licenseHelper->isValidIp()) {
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
                'type'    => 'error',
                'message' => $error
            ]);

            return $this->getResult();
        }

        if (!$response || !isset($response['url'], $response['session_id'])) {
            $this->setJsonContent([
                'url' => null
            ]);
            return $this->getResult();
        }

        $this->sessionHelper->setValue('token_session_id', $response['session_id']);

        $this->setJsonContent([
            'url' => $response['url']
        ]);
        return $this->getResult();

        // ---------------------------------------
    }
}
