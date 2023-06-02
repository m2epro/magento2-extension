<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Template\Shipping;

class GetTemplates extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Account
{
    /** @var \Ess\M2ePro\Model\AccountFactory */
    private $accountFactory;

    public function __construct(
        \Ess\M2ePro\Model\AccountFactory $accountFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($amazonFactory, $context);

        $this->accountFactory = $accountFactory;
    }

    /**
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function execute()
    {
        $accountId = (int)$this->getRequest()->getParam('account_id');

        $account = $this->accountFactory->create()->load($accountId);
        $account->setChildMode(\Ess\M2ePro\Helper\Component\Amazon::NICK);

        $templatesArray = $account->getChildObject()->getDictionaryTemplateShipping();

        $this->setJsonContent($templatesArray);

        return $this->getResult();
    }
}
