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
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Account\Delete
 */
class Delete extends Account
{
    /** @var \Ess\M2ePro\Helper\Data\Cache\Permanent */
    private $permanentCacheHelper;

    public function __construct(
        \Ess\M2ePro\Model\Ebay\Account\Store\Category\Update $storeCategoryUpdate,
        \Ess\M2ePro\Helper\Component\Ebay\Category\Store $componentEbayCategoryStore,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context,
        \Ess\M2ePro\Helper\Data\Cache\Permanent $permanentCacheHelper
    ) {
        parent::__construct($storeCategoryUpdate, $componentEbayCategoryStore, $ebayFactory, $context);

        $this->permanentCacheHelper = $permanentCacheHelper;
    }

    public function execute()
    {
        $ids = $this->getRequestIds();

        if (count($ids) == 0) {
            $this->messageManager->addError($this->__('Please select Account(s) to remove.'));
            $this->_redirect('*/*/index');
            return;
        }

        $deleted = $locked = 0;
        foreach ($ids as $id) {

            /** @var \Ess\M2ePro\Model\Account $account */
            $account = $this->ebayFactory->getObjectLoaded('Account', $id);

            if ($account->isLocked(true)) {
                $locked++;
                continue;
            }

            $account->deleteProcessings();
            $account->deleteProcessingLocks();
            $account->delete();

            $deleted++;
        }

        $this->permanentCacheHelper->removeValue(ValidTokens::ACCOUNT_TOKENS_CACHE_KEY);

        $tempString = $this->__('%amount% record(s) were deleted.', $deleted);
        $deleted && $this->messageManager->addSuccess($tempString);

        $tempString  = $this->__('%amount% record(s) are used in M2E Pro Listing(s).', $locked) . ' ';
        $tempString .= $this->__('Account must not be in use to be deleted.');
        $locked && $this->messageManager->addError($tempString);

        $this->_redirect('*/*/index');
    }
}
