<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Order\UploadByUser;

use Ess\M2ePro\Model\Cron\Task\Amazon\Order\UploadByUser\Manager as AmazonManager;
use Ess\M2ePro\Model\Cron\Task\Ebay\Order\UploadByUser\Manager as EbayManager;
use Ess\M2ePro\Model\Cron\Task\Walmart\Order\UploadByUser\Manager as WalmartManager;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Order\UploadByUser\Reset
 */
class Reset extends \Ess\M2ePro\Controller\Adminhtml\Order
{
    //########################################

    public function execute()
    {
        $component = $this->getRequest()->getParam('component');
        $accountId = $this->getRequest()->getParam('account_id');
        if (empty($component) || empty($accountId)) {
            $this->setJsonContent(
                [
                    'result'   => false,
                    'messages' => [
                        [
                            'type' => 'error',
                            'text' => $this->__('Account must be specified.')
                        ]
                    ]
                ]
            );
            return $this->getResult();
        }

        /** @var \Ess\M2ePro\Model\Account $account */
        $account = $this->parentFactory->getCachedObjectLoaded($component, 'Account', $accountId);
        $manager = $this->getManager($account);

        $manager->clear();

        $this->setJsonContent(['result' => true]);
        return $this->getResult();
    }

    //########################################

    protected function getManager(\Ess\M2ePro\Model\Account $account)
    {
        switch ($account->getComponentMode()) {
            case \Ess\M2ePro\Helper\Component\Amazon::NICK:
                $manager = $this->modelFactory->getObject('Cron_Task_Amazon_Order_UploadByUser_Manager');
                break;

            case \Ess\M2ePro\Helper\Component\Ebay::NICK:
                $manager = $this->modelFactory->getObject('Cron_Task_Ebay_Order_UploadByUser_Manager');
                break;

            case \Ess\M2ePro\Helper\Component\Walmart::NICK:
                $manager = $this->modelFactory->getObject('Cron_Task_Walmart_Order_UploadByUser_Manager');
                break;
        }

        /** @var AmazonManager|EbayManager|WalmartManager $manager */
        $manager->setIdentifierByAccount($account);
        return $manager;
    }

    //########################################
}
