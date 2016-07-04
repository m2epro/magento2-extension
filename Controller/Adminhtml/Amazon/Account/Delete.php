<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Account;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Account;

class Delete extends Account
{
    public function execute()
    {
        $ids = $this->getRequestIds();

        if (count($ids) == 0) {
            $this->messageManager->addError($this->__('Please select account(s) to remove.'));
            $this->_redirect('*/*/index');
            return;
        }

        $accountCollection = $this->activeRecordFactory->getObject('Account')->getCollection();
        $accountCollection->addFieldToFilter('id', array('in' => $ids));

        $accounts = $accountCollection->getItems();

        if (empty($accounts)) {
            $this->_redirect('*/*/index');
            return;
        }

        $deleted = $locked = 0;
        foreach ($accounts as $account) {

            /** @var $account \Ess\M2ePro\Model\Account */

            if ($account->isLocked(true)) {
                $locked++;
                continue;
            }

            try {

                $dispatcherObject = $this->modelFactory->getObject('Amazon\Connector\Dispatcher');

                if ($dispatcherObject) {

                    $connectorObj = $dispatcherObject->getConnector('account','delete','entityRequester',
                        array(), $account);
                    $dispatcherObject->process($connectorObj);
                }

            } catch (\Exception $e) {

                $account->deleteProcessings();
                $account->deleteProcessingLocks();
                $account->delete();

                throw $e;
            }

            $account->deleteProcessings();
            $account->deleteProcessingLocks();
            $account->delete();

            $deleted++;
        }

        $tempString = $this->__('%amount% record(s) were successfully deleted.', $deleted);
        $deleted && $this->messageManager->addSuccess($tempString);

        $tempString  = $this->__('%amount% record(s) are used in M2E Pro Listing(s).', $locked) . ' ';
        $tempString .= $this->__('Account must not be in use to be deleted.');
        $locked && $this->messageManager->addError($tempString);

        $this->_redirect('*/*/index');
    }
}