<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Account;

use Ess\M2ePro\Controller\Adminhtml\Walmart\Account;

class Save extends Account
{
    /** @var \Ess\M2ePro\Helper\Module\Wizard */
    private $helperWizard;

    /** @var \Ess\M2ePro\Helper\Module\Exception */
    private $helperException;

    /** @var \Ess\M2ePro\Helper\Data */
    private $helperData;

    public function __construct(
        \Ess\M2ePro\Helper\Module\Wizard $helperWizard,
        \Ess\M2ePro\Helper\Module\Exception $helperException,
        \Ess\M2ePro\Helper\Data $helperData,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($walmartFactory, $context);

        $this->helperWizard = $helperWizard;
        $this->helperException = $helperException;
        $this->helperData = $helperData;
    }

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
            $this->helperException->process($exception);

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

        $routerParams = [
            'id'       => $account->getId(),
            '_current' => true
        ];

        if ($this->helperWizard->isActive(\Ess\M2ePro\Helper\View\Walmart::WIZARD_INSTALLATION_NICK) &&
            $this->helperWizard->getStep(\Ess\M2ePro\Helper\View\Walmart::WIZARD_INSTALLATION_NICK) == 'account') {
            $routerParams['wizard'] = true;
        }

        return $this->_redirect($this->helperData->getBackUrl('list', [], ['edit'=>$routerParams]));
    }
}
