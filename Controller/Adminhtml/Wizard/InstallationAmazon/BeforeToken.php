<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationAmazon;

use Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationAmazon;

class BeforeToken extends InstallationAmazon
{
    /** @var \Ess\M2ePro\Helper\View\Configuration */
    private $configurationHelper;
    /** @var \Ess\M2ePro\Helper\Data\Session */
    private $sessionHelper;
    /** @var \Ess\M2ePro\Helper\Module\Exception */
    private $exceptionHelper;
    /** @var \Ess\M2ePro\Helper\Module\License */
    private $licenseHelper;
    /** @var \Ess\M2ePro\Model\Servicing\Dispatcher */
    private $servicingDispatcher;
    /** @var \Magento\Framework\Controller\Result\JsonFactory */
    private $jsonResultFactory;

    public function __construct(
        \Ess\M2ePro\Helper\Data\Session $sessionHelper,
        \Ess\M2ePro\Helper\Module\Exception $exceptionHelper,
        \Ess\M2ePro\Helper\Module\License $licenseHelper,
        \Ess\M2ePro\Helper\View\Configuration $configurationHelper,
        \Ess\M2ePro\Model\Servicing\Dispatcher $servicingDispatcher,
        \Magento\Framework\Controller\Result\JsonFactory $jsonResultFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Helper\View\Amazon $amazonViewHelper,
        \Magento\Framework\Code\NameBuilder $nameBuilder,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($amazonFactory, $amazonViewHelper, $nameBuilder, $context);

        $this->configurationHelper = $configurationHelper;
        $this->sessionHelper = $sessionHelper;
        $this->exceptionHelper = $exceptionHelper;
        $this->licenseHelper = $licenseHelper;
        $this->servicingDispatcher = $servicingDispatcher;
        $this->jsonResultFactory = $jsonResultFactory;
    }

    public function execute()
    {
        // Get and save form data
        // ---------------------------------------
        $marketplaceId = $this->getRequest()->getParam('marketplace_id', 0);
        // ---------------------------------------

        $marketplace = $this->activeRecordFactory->getObjectLoaded('Marketplace', $marketplaceId);

        try {
            $backUrl = $this->getUrl('*/*/afterToken');

            $dispatcherObject = $this->modelFactory->getObject('Amazon_Connector_Dispatcher');
            $connectorObj = $dispatcherObject->getVirtualConnector(
                'account',
                'get',
                'authUrl',
                ['back_url' => $backUrl, 'marketplace' => $marketplace->getData('native_id')]
            );

            $dispatcherObject->process($connectorObj);
            $response = $connectorObj->getResponseData();
        } catch (\Exception $exception) {
            $this->exceptionHelper->process($exception);

            $this->servicingDispatcher->processTask(\Ess\M2ePro\Model\Servicing\Task\License::NAME);

            $error = 'The Amazon token obtaining is currently unavailable.<br/>Reason: %error_message%';

            if (
                !$this->licenseHelper->isValidDomain()
                || !$this->licenseHelper->isValidIp()
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

            return $this->jsonResultFactory->create()->setData(['message' => $error]);
        }

        $this->sessionHelper->setValue('marketplace_id', $marketplaceId);

        return $this->jsonResultFactory->create()->setData(['url' => $response['url']]);
    }
}
