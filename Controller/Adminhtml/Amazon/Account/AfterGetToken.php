<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Account;

class AfterGetToken extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Account
{
    /** @var \Ess\M2ePro\Helper\Module\Exception */
    private $helperException;
    /** @var \Ess\M2ePro\Model\Amazon\Account\Server\Update */
    private $accountServerUpdate;
    /** @var \Ess\M2ePro\Model\Amazon\Account\TemporaryStorage */
    private $temporaryStorage;

    /**
     * @param \Ess\M2ePro\Model\Amazon\Account\TemporaryStorage $temporaryStorage
     * @param \Ess\M2ePro\Model\Amazon\Account\Server\Update $accountServerUpdate
     * @param \Ess\M2ePro\Helper\Module\Exception $helperException
     * @param \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory
     * @param \Ess\M2ePro\Controller\Adminhtml\Context $context
     */
    public function __construct(
        \Ess\M2ePro\Model\Amazon\Account\TemporaryStorage $temporaryStorage,
        \Ess\M2ePro\Model\Amazon\Account\Server\Update $accountServerUpdate,
        \Ess\M2ePro\Helper\Module\Exception $helperException,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($amazonFactory, $context);

        $this->helperException = $helperException;
        $this->accountServerUpdate = $accountServerUpdate;
        $this->temporaryStorage = $temporaryStorage;
    }

    // ----------------------------------------

    public function execute()
    {
        $params = $this->getRequest()->getParams();

        if (empty($params)) {
            return $this->_redirect('*/*/new', [
                'close_on_save' => $this->getRequest()->getParam('close_on_save'),
            ]);
        }

        $requiredFields = [
            'Merchant',
            'MWSAuthToken',
        ];

        foreach ($requiredFields as $requiredField) {
            if (!isset($params[$requiredField])) {
                $error = $this->__('The Amazon token obtaining is currently unavailable.');
                $this->messageManager->addErrorMessage($error);

                return $this->_redirect('*/*/new', [
                    'close_on_save' => $this->getRequest()->getParam('close_on_save'),
                ]);
            }
        }

        $accountId = $this->temporaryStorage->getAccountId();

        // new account
        if ((int)$accountId <= 0) {
            $this->temporaryStorage->setMerchant($params['Merchant']);
            $this->temporaryStorage->setMWSToken($params['MWSAuthToken']);

            return $this->_redirect('*/*/new', [
                'close_on_save' => $this->getRequest()->getParam('close_on_save'),
            ]);
        }

        try {
            /** @var \Ess\M2ePro\Model\Account $account */
            $account = $this->amazonFactory->getObjectLoaded('Account', $accountId);
            $this->accountServerUpdate->process($account->getChildObject(), $params['MWSAuthToken']);
        } catch (\Exception $exception) {
            $this->helperException->process($exception);
            $this->temporaryStorage->removeAllValues();

            $this->messageManager->addError(
                $this->__(
                    'The Amazon access obtaining is currently unavailable.<br/>Reason: %error_message%',
                    $exception->getMessage()
                )
            );

            return $this->_redirect('*/amazon_account');
        }

        $this->messageManager->addSuccessMessage($this->__('Token was saved'));

        return $this->_redirect('*/*/edit', [
            'id' => $accountId,
            'close_on_save' => $this->getRequest()->getParam('close_on_save'),
        ]);
    }
}
