<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Account;

use Ess\M2ePro\Model\Ebay\Account as EbayAccount;

class BeforeGetSellApiToken extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Account
{
    /** @var \Ess\M2ePro\Helper\Module\Exception */
    private $helperException;
    /** @var \Ess\M2ePro\Model\Ebay\Connector\DispatcherFactory */
    private $dispatcherFactory;

    public function __construct(
        \Ess\M2ePro\Model\Ebay\Connector\DispatcherFactory $dispatcherFactory,
        \Ess\M2ePro\Helper\Module\Exception $helperException,
        \Ess\M2ePro\Model\Ebay\Account\Store\Category\Update $storeCategoryUpdate,
        \Ess\M2ePro\Helper\Component\Ebay\Category\Store $componentEbayCategoryStore,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct(
            $storeCategoryUpdate,
            $componentEbayCategoryStore,
            $ebayFactory,
            $context
        );

        $this->dispatcherFactory = $dispatcherFactory;
        $this->helperException = $helperException;
    }

    public function execute()
    {
        $accountId = $this->getRequest()->getParam('id', 0);
        $accountMode = (int)$this->getRequest()->getParam('mode', \Ess\M2ePro\Model\Ebay\Account::MODE_SANDBOX);

        $mode = $accountMode == \Ess\M2ePro\Model\Ebay\Account::MODE_PRODUCTION ? 'production' : 'sandbox';

        try {
            $backUrl = $this->getUrl(
                '*/*/afterGetSellApiToken',
                ['mode' => $accountMode, 'id' => $accountId, '_current' => true]
            );

            /** @var \Ess\M2ePro\Model\Ebay\Connector\Account\Get\GrantAccessUrl $connectorObj */
            $connectorObj = $this->dispatcherFactory
                ->create()
                ->getConnector(
                    'account',
                    'get',
                    'grantAccessUrl',
                    [
                        'mode' => $mode,
                        'back_url' => $backUrl,
                    ]
                );

            $connectorObj->process();
            $response = $connectorObj->getResponseData();
        } catch (\Throwable $exception) {
            $this->helperException->process($exception);
            $error = 'The eBay Sell token obtaining is currently unavailable.<br/>Reason: %1';
            $error = __($error, $exception->getMessage());

            $this->getMessageManager()->addErrorMessage($error);

            $this->_redirect($this->getUrl('*/*/index'));

            return;
        }

        $this->_redirect($response['url']);
    }
}
