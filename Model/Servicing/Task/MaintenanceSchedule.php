<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Servicing\Task;

/**
 * Class \Ess\M2ePro\Model\Servicing\Task\ProductVariationVocabulary
 */
class MaintenanceSchedule extends \Ess\M2ePro\Model\Servicing\Task
{
    //########################################

    /**
     * @return string
     */
    public function getPublicNick()
    {
        return 'maintenance_schedule';
    }

    //########################################

    /**
     * @return array
     */
    public function getRequestData()
    {
        return [];
    }

    public function processResponseData(array $data)
    {
        $dateEnabledFrom = false;
        $dateEnabledTo = false;

        if (!empty($data['date_enabled_from']) && !empty($data['date_enabled_to'])) {
            $dateEnabledFrom = $data['date_enabled_from'];
            $dateEnabledTo = $data['date_enabled_to'];
        }

        $helper = $this->getHelper('Server_Maintenance');

        if ($helper->getDateEnabledFrom() != $dateEnabledFrom) {
            $helper->setDateEnabledFrom($dateEnabledFrom);
        }

        if ($helper->getDateEnabledTo() != $dateEnabledTo) {
            $helper->setDateEnabledTo($dateEnabledTo);
        }
    }

    //########################################
}
