<?php

namespace Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationEbay;

use Ess\M2ePro\Controller\Adminhtml\Context;
use Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationEbay;
use Ess\M2ePro\Model\Ebay\Account as EbayAccount;

class AfterGetSellApiToken extends InstallationEbay
{
    /** @var \Ess\M2ePro\Model\Ebay\Account\Create */
    private $accountCreate;

    public function __construct(
        \Ess\M2ePro\Model\Ebay\Account\Create $accountCreate,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Helper\View\Ebay $ebayViewHelper,
        \Magento\Framework\Code\NameBuilder $nameBuilder,
        Context $context
    ) {
        parent::__construct(
            $ebayFactory,
            $ebayViewHelper,
            $nameBuilder,
            $context
        );

        $this->accountCreate = $accountCreate;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function execute()
    {
        $oauthCode = base64_decode((string)$this->getRequest()->getParam('code'));

        if (!$oauthCode) {
            $this->messageManager->addError($this->__('Token is not defined'));

            return $this->_redirect('*/*/installation');
        }

        $accountMode = (int)$this->getRequest()->getParam('mode');

        try {
            $this->accountCreate->create($oauthCode, $accountMode);
        } catch (\Throwable $exception) {
            $this->messageManager->addError($exception->getMessage());
            return $this->_redirect('*/*/installation');
        }

        $this->setStep($this->getNextStep());

        return $this->_redirect('*/*/installation');
    }
}
