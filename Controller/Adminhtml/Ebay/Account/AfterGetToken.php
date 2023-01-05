<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Account;

use Ess\M2ePro\Controller\Adminhtml\Ebay\Account;
use Ess\M2ePro\Model\Ebay\Account\Issue\ValidTokens;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Account\AfterGetToken
 */
class AfterGetToken extends Account
{
    /** @var \Ess\M2ePro\Helper\Module\Exception */
    private $helperException;
    /** @var \Ess\M2ePro\Model\Ebay\Account\TemporaryStorage */
    private $temporaryStorage;
    /** @var \Ess\M2ePro\Helper\Data\Cache\Permanent */
    private $permanentCacheHelper;

    public function __construct(
        \Ess\M2ePro\Model\Ebay\Account\TemporaryStorage $temporaryStorage,
        \Ess\M2ePro\Helper\Module\Exception $helperException,
        \Ess\M2ePro\Model\Ebay\Account\Store\Category\Update $storeCategoryUpdate,
        \Ess\M2ePro\Helper\Component\Ebay\Category\Store $componentEbayCategoryStore,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context,
        \Ess\M2ePro\Helper\Data\Cache\Permanent $permanentCacheHelper
    ) {
        parent::__construct(
            $storeCategoryUpdate,
            $componentEbayCategoryStore,
            $ebayFactory,
            $context
        );

        $this->helperException = $helperException;
        $this->temporaryStorage = $temporaryStorage;
        $this->permanentCacheHelper = $permanentCacheHelper;
    }

    // ----------------------------------------

    public function execute()
    {
        $sessionId = $this->temporaryStorage->getSessionId();
        if ($sessionId === null) {
            $this->_redirect('*/*/index');
        }

        $accountId = (int)$this->temporaryStorage->getAccountId();

        if ($accountId <= 0) {
            return $this->_redirect(
                '*/*/new',
                [
                    'is_show_tables' => true,
                    '_current'       => true
                ]
            );
        }

        $data = [
            'mode' => $this->temporaryStorage->getAccountMode(),
            'token_session' => $sessionId
        ];

        try {
            $this->updateAccount($accountId, $data);
        } catch (\Exception $exception) {
            $this->temporaryStorage->deleteAllValues();
            $this->helperException->process($exception);

            $this->messageManager->addError($this->__(
                'The Ebay access obtaining is currently unavailable.<br/>Reason: %error_message%',
                $exception->getMessage()
            ));

            return $this->_redirect('*/ebay_account');
        }

        $this->permanentCacheHelper->removeValue(ValidTokens::ACCOUNT_TOKENS_CACHE_KEY);

        $this->messageManager->addSuccessMessage($this->__('Token was saved'));

        return $this->_redirect('*/*/edit', ['id' => $accountId, '_current' => true]);
    }

    // ----------------------------------------
}
