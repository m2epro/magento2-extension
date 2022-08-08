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
    /** @var \Ess\M2ePro\Helper\Data\Session */
    private $helperDataSession;
    /** @var \Ess\M2ePro\Model\Amazon\Account\Server\Update */
    private $accountServerUpdate;

    /**
     * @param \Ess\M2ePro\Model\Amazon\Account\Server\Update $accountServerUpdate
     * @param \Ess\M2ePro\Helper\Module\Exception $helperException
     * @param \Ess\M2ePro\Helper\Data\Session $helperDataSession
     * @param \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory
     * @param \Ess\M2ePro\Controller\Adminhtml\Context $context
     */
    public function __construct(
        \Ess\M2ePro\Model\Amazon\Account\Server\Update $accountServerUpdate,
        \Ess\M2ePro\Helper\Module\Exception $helperException,
        \Ess\M2ePro\Helper\Data\Session $helperDataSession,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($amazonFactory, $context);

        $this->helperException = $helperException;
        $this->helperDataSession = $helperDataSession;
        $this->accountServerUpdate = $accountServerUpdate;
    }

    // ----------------------------------------

    public function execute()
    {
        $params = $this->getRequest()->getParams();

        if (empty($params)) {
            return $this->_redirect('*/*/new', [
                'close_on_save' => $this->getRequest()->getParam('close_on_save')
            ]);
        }

        $requiredFields = [
            'Merchant',
            'MWSAuthToken',
        ];

        foreach ($requiredFields as $requiredField) {
            if (!isset($params[$requiredField])) {
                $error = $this->__('The Amazon token obtaining is currently unavailable.');
                $this->messageManager->addError($error);

                return $this->_redirect('*/*/new', [
                    'close_on_save' => $this->getRequest()->getParam('close_on_save')
                ]);
            }
        }

        $id = $this->helperDataSession->getValue('account_id');

        // new account
        if ((int)$id <= 0) {
            $this->helperDataSession->setValue('merchant_id', $params['Merchant']);
            $this->helperDataSession->setValue('mws_token', $params['MWSAuthToken']);

            return $this->_redirect('*/*/new', [
                'close_on_save' => $this->getRequest()->getParam('close_on_save')
            ]);
        }

        try {
            /** @var \Ess\M2ePro\Model\Account $account */
            $account = $this->amazonFactory->getObjectLoaded('Account', $id);

            $this->accountServerUpdate->process($account->getChildObject(), $params['MWSAuthToken']);
        } catch (\Exception $exception) {
            $this->helperException->process($exception);

            $this->messageManager->addError($this->__(
                'The Amazon access obtaining is currently unavailable.<br/>Reason: %error_message%',
                $exception->getMessage()
            ));

            return $this->_redirect('*/amazon_account');
        }

        $this->messageManager->addSuccess($this->__('Token was saved'));

        return $this->_redirect('*/*/edit', [
            'id' => $id, 'close_on_save' => $this->getRequest()->getParam('close_on_save')
        ]);
    }
}
