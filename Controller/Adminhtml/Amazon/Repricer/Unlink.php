<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Repricer;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Account;

class Unlink extends Account
{
    /** @var \Ess\M2ePro\Helper\Data\Cache\Permanent */
    private $permanentCacheHelper;

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context,
        \Ess\M2ePro\Helper\Data\Cache\Permanent $permanentCacheHelper
    ) {
        parent::__construct($amazonFactory, $context);
        $this->permanentCacheHelper = $permanentCacheHelper;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function execute()
    {
        $accountId = $this->getRequest()->getParam('id');

        $status = $this->getRequest()->getParam('status');
        $messages = $this->getRequest()->getParam('message', []);

        /** @var \Ess\M2ePro\Model\Account $account */
        $account = $this->amazonFactory->getObjectLoaded('Account', $accountId, null, false);

        if ($accountId && $account === null) {
            $this->getMessageManager()->addError($this->__('Account does not exist.'));

            return $this->_redirect('*/amazon_account/index');
        }

        foreach ($messages as $message) {
            if ($message['type'] === 'notice') {
                $this->getMessageManager()->addNotice($message['text']);
            }

            if ($message['type'] === 'warning') {
                $this->getMessageManager()->addWarning($message['text']);
            }

            if ($message['type'] === 'error') {
                $this->getMessageManager()->addError($message['text']);
            }
        }

        if ($status == '1') {
            /** @var \Ess\M2ePro\Model\Amazon\Repricing\Synchronization\General $repricingSynchronization */
            $repricingSynchronization = $this->modelFactory->getObject('Amazon_Repricing_Synchronization_General');
            $repricingSynchronization->setAccount($account);
            $repricingSynchronization->reset();

            $account->getChildObject()->getRepricing()->delete();
            $this->permanentCacheHelper->removeValue(\Ess\M2ePro\Model\Amazon\Repricing\Issue\InvalidToken::CACHE_KEY);
        }

        return $this->_redirect(
            $this->getUrl('*/amazon_repricer_settings/index/')
        );
    }
}
