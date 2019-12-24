<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Settings\LogsClearing;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Settings\LogsClearing\Save
 */
class Save extends \Ess\M2ePro\Controller\Adminhtml\Base
{
    //########################################

    public function execute()
    {
        $post = $this->getRequest()->getPostValue();

        // Save settings
        // ---------------------------------------
        if ($post) {
            $this->modelFactory->getObject('Log\Clearing')->saveSettings(
                \Ess\M2ePro\Model\Log\Clearing::LOG_LISTINGS,
                $post['listings_log_mode'],
                $post['listings_log_days']
            );
            $this->modelFactory->getObject('Log\Clearing')->saveSettings(
                \Ess\M2ePro\Model\Log\Clearing::LOG_OTHER_LISTINGS,
                $post['other_listings_log_mode'],
                $post['other_listings_log_days']
            );
            $this->modelFactory->getObject('Log\Clearing')->saveSettings(
                \Ess\M2ePro\Model\Log\Clearing::LOG_SYNCHRONIZATIONS,
                $post['synchronizations_log_mode'],
                $post['synchronizations_log_days']
            );
            $this->modelFactory->getObject('Log\Clearing')->saveSettings(
                \Ess\M2ePro\Model\Log\Clearing::LOG_ORDERS,
                $post['orders_log_mode'],
                90
            );

            if ($this->getHelper('Component_Ebay_PickupStore')->isFeatureEnabled()) {
                $this->modelFactory->getObject('Log\Clearing')->saveSettings(
                    \Ess\M2ePro\Model\Log\Clearing::LOG_EBAY_PICKUP_STORE,
                    $post['ebay_pickup_store_log_mode'],
                    $post['ebay_pickup_store_log_days']
                );
            }
        }
        // ---------------------------------------

        // Get actions
        // ---------------------------------------
        $task = $this->getRequest()->getParam('task');
        $log = $this->getRequest()->getParam('log');

        $messages = [];
        if ($task !== null) {
            $title = ucwords(str_replace('_', ' ', $log));
            if ($log == \Ess\M2ePro\Model\Log\Clearing::LOG_EBAY_PICKUP_STORE) {
                $title = 'eBay In-Store Pickup';
            }

            switch ($task) {
                case 'run_now':
                    $this->modelFactory->getObject('Log\Clearing')->clearOldRecords($log);
                    $tempString = $this->__(
                        'Log for %title% has been successfully cleared.',
                        $title
                    );
                    $messages[] = ['success' => $tempString];
                    break;

                case 'clear_all':
                    $this->modelFactory->getObject('Log\Clearing')->clearAllLog($log);
                    $tempString = $this->__(
                        'All Log for %title% has been successfully cleared.',
                        $title
                    );
                    $messages[] = ['success' => $tempString];
                    break;
            }
        }
        // ---------------------------------------

        $this->setJsonContent(
            ['success' => true, 'messages' => $messages]
        );
        return $this->getResult();
    }

    //########################################
}
