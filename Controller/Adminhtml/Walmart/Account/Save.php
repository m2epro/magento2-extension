<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Account;

use Ess\M2ePro\Controller\Adminhtml\Walmart\Account;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Walmart\Account\Save
 */
class Save extends Account
{
    //########################################

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
                'The Walmart access obtaining is currently unavailable.<br/>Reason: %error_message%',
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

            return $this->_redirect('*/walmart_account');
        }

        if ($this->isAjax()) {
            $this->setJsonContent([
                'success' => true
            ]);

            return $this->getResult();
        }

        $this->messageManager->addSuccess($this->__('Account was saved'));

        /** @var $wizardHelper \Ess\M2ePro\Helper\Module\Wizard */
        $wizardHelper = $this->getHelper('Module\Wizard');

        $routerParams = [
            'id'       => $account->getId(),
            '_current' => true
        ];

        if ($wizardHelper->isActive(\Ess\M2ePro\Helper\View\Walmart::WIZARD_INSTALLATION_NICK) &&
            $wizardHelper->getStep(\Ess\M2ePro\Helper\View\Walmart::WIZARD_INSTALLATION_NICK) == 'account') {
            $routerParams['wizard'] = true;
        }

        return $this->_redirect($this->getHelper('Data')->getBackUrl('list', [], ['edit'=>$routerParams]));
    }

    //########################################
}
