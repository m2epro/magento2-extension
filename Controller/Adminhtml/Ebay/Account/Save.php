<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Account;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Account\Save
 */
class Save extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Account
{
    public function execute()
    {
        $post = $this->getRequest()->getPost();

        if (!$post->count()) {
            $this->_forward('index');
            return;
        }

        $id = $this->getRequest()->getParam('id');

        try {
            $data = $this->sendDataToServer($id, $post);

            $accountExists = $this->getExistsAccount($data['user_id']);
            if (empty($id) && !empty($accountExists)) {
                $data['title'] = $accountExists->getTitle();
                $this->updateAccount($accountExists->getAccountId(), $data->toArray());

                $this->getMessageManager()->addError(
                    $this->__('An account with the same eBay User ID already exists.')
                );

                return $this->_redirect('*/*/new');
            }
            $id = $this->updateAccount($id, $data->toArray());
        } catch (\Exception $exception) {
            if ($this->isAjax()) {
                $this->setJsonContent([
                    'success' => false,
                    'message' => $exception->getMessage()
                ]);
                return $this->getResult();
            }

            $this->messageManager->addError($exception->getMessage());

            return $this->_redirect('*/ebay_account');
        }

        if ($this->isAjax()) {
            $this->setJsonContent([
                'success' => true,
            ]);
            return $this->getResult();
        }

        $this->messageManager->addSuccess($this->__('Account was saved'));

        return $this->_redirect($this->getHelper('Data')->getBackUrl(
            'list',
            [],
            [
                'edit' => [
                    'id'                => $id,
                    'update_ebay_store' => null,
                    '_current'          => true
                ]
            ]
        ));
    }

    //########################################

    protected function getExistsAccount($userId)
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Account\Collection $account */
        $account = $this->ebayFactory->getObject('Account')->getCollection()
            ->addFieldToSelect('title')
            ->addFieldToFilter('user_id', $userId);

        if (!$account->getSize()) {
            return null;
        }

        return $account->getFirstItem();
    }

    //########################################
}
