<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Account;

use Ess\M2ePro\Controller\Adminhtml\Ebay\Account;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Account\AfterGetToken
 */
class AfterGetToken extends Account
{
    /** @var \Ess\M2ePro\Helper\Module\Exception */
    private $helperException;

    /** @var \Ess\M2ePro\Helper\Data\Session */
    private $helperDataSession;

    public function __construct(
        \Ess\M2ePro\Helper\Module\Exception $helperException,
        \Ess\M2ePro\Helper\Data\Session $helperDataSession,
        \Ess\M2ePro\Model\Ebay\Account\Store\Category\Update $storeCategoryUpdate,
        \Ess\M2ePro\Helper\Component\Ebay\Category\Store $componentEbayCategoryStore,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($storeCategoryUpdate, $componentEbayCategoryStore, $ebayFactory, $context);

        $this->helperException = $helperException;
        $this->helperDataSession = $helperDataSession;
    }

    //########################################

    public function execute()
    {
        $sessionId = $this->helperDataSession->getValue('get_token_session_id', true);
        $sessionId === null && $this->_redirect('*/*/index');

        $this->helperDataSession->setValue('get_token_account_token_session', $sessionId);

        $id = (int)$this->helperDataSession->getValue('get_token_account_id', true);

        if ((int)$id <= 0) {
            return $this->_redirect(
                '*/*/new',
                [
                    'is_show_tables' => true,
                    '_current'       => true
                ]
            );
        }

        $data = [
            'mode' => $this->helperDataSession->getValue('get_token_account_mode'),
            'token_session' => $sessionId
        ];

        try {
            $this->updateAccount($id, $data);
        } catch (\Exception $exception) {
            $this->helperException->process($exception);

            $this->messageManager->addError($this->__(
                'The Ebay access obtaining is currently unavailable.<br/>Reason: %error_message%',
                $exception->getMessage()
            ));

            return $this->_redirect('*/ebay_account');
        }

        $this->messageManager->addSuccess($this->__('Token was saved'));

        return $this->_redirect('*/*/edit', ['id' => $id, '_current' => true]);
    }

    //########################################
}
