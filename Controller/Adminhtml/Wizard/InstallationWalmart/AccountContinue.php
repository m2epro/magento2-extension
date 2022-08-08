<?php

/*
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

    public function __construct(
        \Ess\M2ePro\Helper\Module\Exception $exceptionHelper,
        \Ess\M2ePro\Helper\Magento\Store  $storeHelper,
        \Ess\M2ePro\Helper\Module\License $licenseHelper,
        \Ess\M2ePro\Helper\View\Configuration $configurationHelper,
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

        $accountData = [];

        $requiredFields = [
            'marketplace_id',
            'consumer_id',
            'private_key',
            'client_id',
            'client_secret'
        ];

        foreach ($requiredFields as $requiredField) {
            if (!empty($params[$requiredField])) {
                $accountData[$requiredField] = $params[$requiredField];
            }
        }

        /** @var \Ess\M2ePro\Model\Marketplace $marketplaceObject */
        $marketplaceObject = $this->walmartFactory->getCachedObjectLoaded(
            'Marketplace',
            $params['marketplace_id']
        );
        $marketplaceObject->setData('status', \Ess\M2ePro\Model\Marketplace::STATUS_ENABLE)->save();

        $accountData = array_merge(
            $this->getAccountDefaultSettings(),
            [
                'title' => "Default - {$marketplaceObject->getCode()}",
            ],
            $accountData
        );

        /** @var \Ess\M2ePro\Model\Account $account */
        $account = $this->walmartFactory->getObject('Account');
        $this->modelFactory->getObject('Walmart_Account_Builder')->build($account, $accountData);

        try {
            $requestData = [
                'marketplace_id' => $params['marketplace_id']
            ];

            if ($params['marketplace_id'] == \Ess\M2ePro\Helper\Component\Walmart::MARKETPLACE_US) {
                $requestData['client_id'] = $params['client_id'];
                $requestData['client_secret'] = $params['client_secret'];
            } else {
                $requestData['consumer_id'] = $params['consumer_id'];
                $requestData['private_key'] = $params['private_key'];
            }

            /** @var \Ess\M2ePro\Model\Walmart\Connector\Dispatcher $dispatcherObject */
            $dispatcherObject = $this->modelFactory->getObject('Walmart_Connector_Dispatcher');

            $connectorObj = $dispatcherObject->getConnector(
                'account',
                'add',
                'entityRequester',
                $requestData,
                $account
            );
            $dispatcherObject->process($connectorObj);
            $responseData = $connectorObj->getResponseData();

            $account->getChildObject()->addData(
                [
                    'server_hash' => $responseData['hash'],
                    'info'        => $this->getHelper('Data')->jsonEncode($responseData['info'])
                ]
            );
            $account->getChildObject()->save();
        } catch (\Exception $exception) {
            $this->exceptionHelper->process($exception);

            $account->delete();

            $this->modelFactory->getObject('Servicing\Dispatcher')->processTask(
                \Ess\M2ePro\Model\Servicing\Task\License::NAME
            );

            $error = 'The Walmart access obtaining is currently unavailable.<br/>Reason: %error_message%';

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

    private function getAccountDefaultSettings()
    {
        $data = $this->modelFactory->getObject('Walmart_Account_Builder')->getDefaultData();

        $data['other_listings_synchronization'] = 0;
        $data['other_listings_mapping_mode'] = 0;
        $data['magento_orders_settings']['listing_other']['store_id'] = $this->storeHelper->getDefaultStoreId();

        return $data;
    }
}
