<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task;

use Ess\M2ePro\Model\HealthStatus\Task\IssueType;

class HealthStatus extends AbstractModel
{
    const NICK = 'health_status';
    const MAX_MEMORY_LIMIT = 512;

    const MESSAGE_SEND_REGISTRY_KEY = '/health_status/notification/send/';

    //########################################

    protected function getNick()
    {
        return self::NICK;
    }

    protected function getMaxMemoryLimit()
    {
        return self::MAX_MEMORY_LIMIT;
    }

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
        $currentStatus->set($resultSet->getWorstState());
    }

    private function processEmailNotification()
    {
        $settings = $this->modelFactory->getObject('HealthStatus\Notification\Settings');
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

        $sender = $this->modelFactory->getObject('HealthStatus\Notification\Email\Sender');
        $sender->send();

        $this->setProblemExistsMark();
    }

    //----------------------------------------

    private function isSetProblemExistsMark()
    {
        /** @var \Ess\M2ePro\Model\Registry $registry */
        $registry = $this->activeRecordFactory->getObjectLoaded(
            'Registry', self::MESSAGE_SEND_REGISTRY_KEY, 'key', false
        );

        if (!$registry || !$registry->getId()) {
            return false;
        }

        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        $expirationDate = new \DateTime($registry->getData('value'), new \DateTimeZone('UTC'));
        $expirationDate->modify('+ 1 day');

        return $expirationDate->getTimestamp() > $now->getTimestamp();
    }

    private function setProblemExistsMark()
    {
        $registry = $this->activeRecordFactory->getObjectLoaded(
            'Registry', self::MESSAGE_SEND_REGISTRY_KEY, 'key', false
        );

        !$registry && $registry = $this->activeRecordFactory->getObject('Registry');
        $registry->setData('key', self::MESSAGE_SEND_REGISTRY_KEY);
        $registry->setData('value', $this->getHelper('Data')->getCurrentGmtDate());
        $registry->save();
    }

    private function unsetProblemExistsMark()
    {
        $registry = $this->activeRecordFactory->getObjectLoaded(
            'Registry', self::MESSAGE_SEND_REGISTRY_KEY, 'key', false
        );

        if ($registry && $registry->getId()) {
            $registry->delete();
        }
    }

    //########################################
}