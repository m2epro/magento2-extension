<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\General;

class CheckCustomerId extends \Ess\M2ePro\Controller\Adminhtml\General
{
    /** @var \Magento\Customer\Model\Customer */
    private $customerModel;

    public function __construct(
        \Magento\Customer\Model\Customer $customerModel,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($context);

        $this->customerModel = $customerModel;
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
