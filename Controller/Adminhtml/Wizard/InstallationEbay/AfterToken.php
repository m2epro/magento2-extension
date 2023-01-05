<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationEbay;

use Ess\M2ePro\Controller\Adminhtml\Context;
use Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationEbay;
use Ess\M2ePro\Model\Ebay\Account as EbayAccount;

class AfterToken extends InstallationEbay
{
    /** @var \Ess\M2ePro\Helper\Data\Session */
    private $sessionHelper;
    /** @var \Ess\M2ePro\Helper\Magento\Store */
    private $magentoStoreHelper;
    /** @var \Ess\M2ePro\Model\Ebay\Account\Store\Category\Update */
    private $storeCategoryUpdate;
    /** @var \Ess\M2ePro\Model\Ebay\Account\Builder */
    private $ebayAccountBuilder;
    /** @var \Ess\M2ePro\Model\AccountFactory */
    private $accountFactory;
    /** @var \Ess\M2ePro\Model\Ebay\Connector\DispatcherFactory */
    private $ebayDispatcherFactory;

    public function __construct(
        \Ess\M2ePro\Helper\Data\Session $sessionHelper,
        \Ess\M2ePro\Helper\Magento\Store $magentoStoreHelper,
        \Ess\M2ePro\Model\Ebay\Account\Store\Category\Update $storeCategoryUpdate,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Helper\View\Ebay $ebayViewHelper,
        \Magento\Framework\Code\NameBuilder $nameBuilder,
        \Ess\M2ePro\Model\Ebay\Account\Builder $ebayAccountBuilder,
        \Ess\M2ePro\Model\AccountFactory $accountFactory,
        \Ess\M2ePro\Model\Ebay\Connector\DispatcherFactory $ebayDispatcherFactory,
        Context $context
    ) {
        parent::__construct(
            $ebayFactory,
            $ebayViewHelper,
            $nameBuilder,
            $context
        );

        $this->storeCategoryUpdate = $storeCategoryUpdate;
        $this->sessionHelper = $sessionHelper;
        $this->magentoStoreHelper = $magentoStoreHelper;
        $this->ebayAccountBuilder = $ebayAccountBuilder;
        $this->accountFactory = $accountFactory;
        $this->ebayDispatcherFactory = $ebayDispatcherFactory;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function execute()
    {
        $tokenSessionId = $this->sessionHelper->getValue('token_session_id', true);

        if (!$tokenSessionId) {
            $this->messageManager->addError($this->__('Token is not defined'));

            return $this->_redirect('*/*/installation');
        }

        $accountMode = $this->getRequest()->getParam('mode');

        $params = [
            'mode' => $accountMode,
            'token_session' => $tokenSessionId,
        ];

        $dispatcherObject = $this->ebayDispatcherFactory->create();
        $connectorObj = $dispatcherObject->getVirtualConnector(
            'account',
            'add',
            'entity',
            $params
        );

        $dispatcherObject->process($connectorObj);
        $responseData = array_filter($connectorObj->getResponseData());

        if (empty($responseData)) {
            $this->messageManager->addError($this->__('Account Add Entity failed.'));

            return $this->_redirect('*/*/installation');
        }

        $account = $this->createAccount(
            $responseData,
            $accountMode === 'sandbox' ? EbayAccount::MODE_SANDBOX : EbayAccount::MODE_PRODUCTION,
            $tokenSessionId
        );

        $this->storeCategoryUpdate->process($account->getChildObject());

        $this->setStep($this->getNextStep());

        return $this->_redirect('*/*/installation');
    }

    /**
     * @param array $responseData
     * @param int $accountMode
     * @param string $tokenSessionId
     *
     * @return \Ess\M2ePro\Model\Account
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function createAccount(
        array $responseData,
        int $accountMode,
        string $tokenSessionId
    ): \Ess\M2ePro\Model\Account {
        $data = $this->ebayAccountBuilder->getDefaultData();

        $data['title'] = $responseData['info']['UserID'];
        $data['user_id'] = $responseData['info']['UserID'];
        $data['mode'] = $accountMode;
        $data['server_hash'] = $responseData['hash'];
        $data['token_session'] = $tokenSessionId;
        $data['token_expired_date'] = $responseData['token_expired_date'];

        $data['magento_orders_settings']['listing_other']['store_id'] = $this->magentoStoreHelper->getDefaultStoreId();

        $data['marketplaces_data'] = [];
        $data['info'] = \Ess\M2ePro\Helper\Json::encode($responseData['info']);

        $account = $this->accountFactory->create();
        $account->setChildMode(\Ess\M2ePro\Helper\Component\Ebay::NICK);

        $this->ebayAccountBuilder->build($account, $data);

        return $account;
    }

    //########################################
}
