<?php

namespace Ess\M2ePro\Controller\Adminhtml\General;

use Ess\M2ePro\Controller\Adminhtml\General;
use Ess\M2ePro\Controller\Adminhtml\Context;

class CheckCustomerId extends General
{
    protected $customerModel;

    public function __construct(
        \Magento\Customer\Model\Customer $customerModel,
        Context $context
    )
    {
        $this->customerModel = $customerModel;

        parent::__construct($context);
    }

    public function execute()
    {
        $customerId = $this->getRequest()->getParam('customer_id');

        $this->setJsonContent([
            'ok' => (bool)$this->customerModel->load($customerId)->getId()
        ]);

        return $this->getResult();
    }
}