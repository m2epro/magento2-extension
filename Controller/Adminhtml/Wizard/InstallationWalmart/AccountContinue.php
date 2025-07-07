<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationWalmart;

use Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationWalmart;

class AccountContinue extends InstallationWalmart
{
    /** @var \Ess\M2ePro\Helper\Module\Exception */
    private $exceptionHelper;

    /** @var \Ess\M2ePro\Helper\Magento\Store */
    private $storeHelper;

    /** @var \Ess\M2ePro\Helper\Module\License */
    private $licenseHelper;

    /** @var \Ess\M2ePro\Helper\View\Configuration */
    private $configurationHelper;
    private \Ess\M2ePro\Model\Walmart\Account\Canada\Create $accountCreate;
    private \Ess\M2ePro\Model\Walmart\Account\Builder $accountBuilder;

    public function __construct(
        \Ess\M2ePro\Helper\Module\Exception $exceptionHelper,
        \Ess\M2ePro\Helper\Magento\Store $storeHelper,
        \Ess\M2ePro\Helper\Module\License $licenseHelper,
        \Ess\M2ePro\Helper\View\Configuration $configurationHelper,
        \Ess\M2ePro\Model\Walmart\Account\Canada\Create $accountCreate,
        \Ess\M2ePro\Model\Walmart\Account\Builder $accountBuilder,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Helper\View\Walmart $walmartViewHelper,
        \Magento\Framework\Code\NameBuilder $nameBuilder,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($walmartFactory, $walmartViewHelper, $nameBuilder, $context);

        $this->exceptionHelper = $exceptionHelper;
        $this->storeHelper = $storeHelper;
        $this->licenseHelper = $licenseHelper;
        $this->configurationHelper = $configurationHelper;
        $this->accountCreate = $accountCreate;
        $this->accountBuilder = $accountBuilder;
    }

    public function execute()
    {
        $params = $this->getRequest()->getParams();
        if (empty($params)) {
            return $this->indexAction();
        }

        if (!$this->validateRequiredParams($params)) {
            $this->setJsonContent(['message' => $this->__('You should fill all required fields.')]);

            return $this->getResult();
        }

        $marketplaceId = (int)$this->getRequest()->getParam('marketplace_id');
        $consumerId = $this->getRequest()->getPost('consumer_id');
        $privateKey = $this->getRequest()->getPost('private_key');

        /** @var \Ess\M2ePro\Model\Marketplace $marketplaceObject */
        $marketplaceObject = $this->walmartFactory->getCachedObjectLoaded(
            'Marketplace',
            $params['marketplace_id']
        );
        $marketplaceObject->setData('status', \Ess\M2ePro\Model\Marketplace::STATUS_ENABLE)->save();

        $title = "Default - {$marketplaceObject->getCode()}";

        try {
            $account = $this->accountCreate->createAccount($marketplaceId, $consumerId, $privateKey, $title);
            $this->accountBuilder->build($account, $this->getAccountDefaultStoreId());
        } catch (\Exception $exception) {
            $this->exceptionHelper->process($exception);

            $this->modelFactory->getObject('Servicing\Dispatcher')->processTask(
                \Ess\M2ePro\Model\Servicing\Task\License::NAME
            );

            $error = (string)__(
                'The Walmart token obtaining is currently unavailable.<br/>Reason: %error_message',
                ['error_message' => $exception->getMessage()]
            );

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

            $this->setJsonContent(['message' => $error]);

            return $this->getResult();
        }

        $this->setStep($this->getNextStep());

        $this->setJsonContent(['result' => true]);

        return $this->getResult();
    }

    private function validateRequiredParams($params)
    {
        if (empty($params['marketplace_id'])) {
            return false;
        }

        if ($params['marketplace_id'] == \Ess\M2ePro\Helper\Component\Walmart::MARKETPLACE_US) {
            if (empty($params['client_id']) || empty($params['client_secret'])) {
                return false;
            }
        } else {
            if (empty($params['consumer_id']) || empty($params['private_key'])) {
                return false;
            }
        }

        return true;
    }

    private function getAccountDefaultStoreId(): array
    {
        $data['magento_orders_settings']['listing_other']['store_id'] = $this->storeHelper->getDefaultStoreId();

        return $data;
    }
}
