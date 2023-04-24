<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Template\Shipping;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Template;

class Refresh extends Template
{
    /** @var \Ess\M2ePro\Model\Amazon\Template\Shipping */
    private $templateShippingUpdate;
    /** @var \Ess\M2ePro\Model\AccountFactory */
    private $accountFactory;

    public function __construct(
        \Ess\M2ePro\Model\AccountFactory $accountFactory,
        \Ess\M2ePro\Model\Amazon\Template\Shipping\Update $templateShippingUpdate,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($amazonFactory, $context);
        $this->templateShippingUpdate = $templateShippingUpdate;
        $this->accountFactory = $accountFactory;
    }

    /**
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function execute()
    {
        $accountId = (int)$this->getRequest()->getParam('account_id');

        $account = $this->accountFactory->create()->load($accountId);

        $this->templateShippingUpdate->process($account);

        return $this->getResult();
    }
}
