<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task\System;

use Ess\M2ePro\Model\HealthStatus\Task\IssueType;

/**
 * Class \Ess\M2ePro\Model\Cron\Task\System\HealthStatus
 */
class HealthStatus extends \Ess\M2ePro\Model\Cron\Task\AbstractModel
{
    const NICK = 'system/health_status';

    /**
     * @var int (in seconds)
     */
    protected $interval = 3600;

    const MAX_MEMORY_LIMIT = 512;

    const MESSAGE_SEND_REGISTRY_KEY = '/health_status/notification/send/';

    //########################################

    protected function performActions()
    {
        $this->updateCurrentStatus();
        $this->processEmailNotification();

        return true;
    }

    //########################################

    private function updateCurrentStatus()
    {
        $healthManager = $this->modelFactory->getObject('HealthStatus\Manager');
        $resultSet = $healthManager->doCheck(IssueType::TYPE);

        $currentStatus = $this->modelFactory->getObject('HealthStatus\CurrentStatus');
        $currentStatus->set($resultSet);
    }

    private function processEmailNotification()
    {
        $settings = $this->modelFactory->getObject('HealthStatus_Notification_Settings');
        $currentStatus = $this->modelFactory->getObject('HealthStatus\CurrentStatus');

        if (!$settings->isModeEmail() || empty($settings->getEmail())) {
            return;
        }

        if ($currentStatus->get() < $settings->getLevel()) {
            $this->unsetProblemExistsMark();
            return;
        }

        if ($this->isSetProblemExistsMark()) {
            return;
        }

        $sender = $this->modelFactory->getObject('HealthStatus_Notification_Email_Sender');
        $sender->send();

        $this->setProblemExistsMark();
    }

    //----------------------------------------

    private function isSetProblemExistsMark()
    {
        $sendDate = $this->getHelper('Module')->getRegistry()->getValue(self::MESSAGE_SEND_REGISTRY_KEY);

        if (!$sendDate) {
            return false;
        }

        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        $expirationDate = new \DateTime($sendDate, new \DateTimeZone('UTC'));
        $expirationDate->modify('+ 1 day');

        return $expirationDate->getTimestamp() > $now->getTimestamp();
    }

    private function setProblemExistsMark()
    {
        $this->getHelper('Module')->getRegistry()->setValue(
            self::MESSAGE_SEND_REGISTRY_KEY,
            $this->getHelper('Data')->getCurrentGmtDate()
        );
    }

    private function unsetProblemExistsMark()
    {
        $this->getHelper('Module')->getRegistry()->deleteValue(self::MESSAGE_SEND_REGISTRY_KEY);
    }

    //########################################
}
