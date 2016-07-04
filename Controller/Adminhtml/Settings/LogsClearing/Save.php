<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Settings\LogsClearing;

use \Ess\M2ePro\Block\Adminhtml\Configuration\Settings\Tabs;

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
                0
            );
//            $this->modelFactory->getObject('Log\Clearing')->saveSettings(
//                \Ess\M2ePro\Model\Log\Clearing::LOG_EBAY_PICKUP_STORE,
//                $post['orders_log_mode'],
//                0
//            );
        }
        // ---------------------------------------

        // Get actions
        // ---------------------------------------
        $task = $this->getRequest()->getParam('task');
        $log = $this->getRequest()->getParam('log');

        $messages = [];
        if (!is_null($task)) {

            switch ($task) {
                case 'run_now':
                    $this->modelFactory->getObject('Log\Clearing')->clearOldRecords($log);
                    $tempString = $this->__(
                        'Log for %title% has been successfully cleared.',
                        str_replace('_',' ',$log)
                    );
                    $messages[] = ['success' => $tempString];
                    break;

                case 'clear_all':
                    $this->modelFactory->getObject('Log\Clearing')->clearAllLog($log);
                    $tempString = $this->__(
                        'All Log for %title% has been successfully cleared.',
                        str_replace('_',' ',$log)
                    );
                    $messages[] = ['success' => $tempString];
                    break;
            }
        }
        // ---------------------------------------

        $this->setAjaxContent(json_encode(
            ['success' => true, 'messages' => $messages]
        ), false);
        return $this->getResult();
    }

    //########################################
}