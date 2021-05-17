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
        }

        $id = $this->getRequest()->getParam('id');
        $data = $post->toArray();

        try {
            $account = $id ? $this->updateAccount($id, $data) : $this->addAccount($data);
        } catch (\Exception $exception) {
            $this->getHelper('Module\Exception')->process($exception);

            $message = $this->__(
                'The Ebay access obtaining is currently unavailable.<br/>Reason: %error_message%',
                $exception->getMessage()
            );

            if ($this->isAjax()) {
                $this->setJsonContent([
                    'success' => false,
                    'message' => $message
                ]);

                return $this->getResult();
            }

            $this->messageManager->addError($message);

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
                    'id'                => $account->getId(),
                    'update_ebay_store' => null,
                    '_current'          => true
                ]
            ]
        ));
    }

    //########################################
}
