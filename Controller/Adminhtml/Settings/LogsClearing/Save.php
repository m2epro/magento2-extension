<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Settings\LogsClearing;

class Save extends \Ess\M2ePro\Controller\Adminhtml\Base
{
    public function execute()
    {
        // Get actions
        // ---------------------------------------
        $task = $this->getRequest()->getParam('task');
        $log = $this->getRequest()->getParam('log');

        $messages = [];
        if ($task !== null) {
            $title = ucwords(str_replace('_', ' ', $log));

            switch ($task) {
                case 'run_now':
                    $this->modelFactory->getObject('Log\Clearing')->clearOldRecords($log);
                    $tempString = $this->__(
                        'Log for %title% has been cleared.',
                        $title
                    );
                    $messages[] = ['success' => $tempString];
                    break;

                case 'clear_all':
                    $this->modelFactory->getObject('Log\Clearing')->clearAllLog($log);
                    $tempString = $this->__(
                        'All Log for %title% has been cleared.',
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
}
