<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Account;

class Check extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Account
{
    /** @var \Ess\M2ePro\Model\Amazon\Connector\Dispatcher */
    private $dispatcher;

    /**
     * @param \Ess\M2ePro\Model\Amazon\Connector\Dispatcher $dispatcher
     * @param \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory
     * @param \Ess\M2ePro\Controller\Adminhtml\Context $context
     */
    public function __construct(
        \Ess\M2ePro\Model\Amazon\Connector\Dispatcher $dispatcher,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($amazonFactory, $context);

        $this->dispatcher = $dispatcher;
    }

    public function execute()
    {
        $id = $this->getRequest()->getParam('id');

        /** @var \Ess\M2ePro\Model\Account $account */
        $account = $this->amazonFactory->getObjectLoaded('Account', $id);

        /** @var \Ess\M2ePro\Model\Amazon\Connector\Account\Check\EntityRequester $connectorObj */
        $connectorObj = $this->dispatcher->getConnector(
            'account',
            'check',
            'entityRequester',
            ['account_server_hash' => $account->getChildObject()->getServerHash()]
        );

        $this->dispatcher->process($connectorObj);

        $responseData = $connectorObj->getResponseData();

        if ($responseData['status']) {
            $this->messageManager->addSuccess($this->__('Amazon account token is valid.'));
        } else {
            $this->messageManager->addError($this->__('Amazon account token is invalid. Please re-get token.'));
        }

        $this->_forward('edit');
    }
}
