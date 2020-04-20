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
        if (empty($data['date_enabled_from']) ||
            empty($data['date_enabled_to']) ||
            empty($data['date_real_from']) ||
            empty($data['date_real_to'])
        ) {
            $dateEnabledFrom = null;
            $dateEnabledTo = null;
            $dateRealFrom = null;
            $dateRealTo = null;
        } else {
            $dateEnabledFrom = $data['date_enabled_from'];
            $dateEnabledTo = $data['date_enabled_to'];
            $dateRealFrom = $data['date_real_from'];
            $dateRealTo = $data['date_real_to'];
        }

        /** @var \Ess\M2ePro\Model\Registry $enabledFrom */
        $enabledFrom = $this->activeRecordFactory->getObject('Registry');
        $enabledFrom->loadByKey('/server/maintenance/schedule/date/enabled/from/');

        if ($enabledFrom->getValue() != $dateEnabledFrom) {
            $enabledFrom->setValue($dateEnabledFrom)->save();
        }

        /** @var \Ess\M2ePro\Model\Registry $realFrom */
        $realFrom = $this->activeRecordFactory->getObject('Registry');
        $realFrom->loadByKey('/server/maintenance/schedule/date/real/from/');

        if ($realFrom->getValue() != $dateRealFrom) {
            $realFrom->setValue($dateRealFrom)->save();
        }

        /** @var \Ess\M2ePro\Model\Registry $realTo */
        $realTo = $this->activeRecordFactory->getObject('Registry');
        $realTo->loadByKey('/server/maintenance/schedule/date/real/to/');

        /** @var \Ess\M2ePro\Model\Registry $enabledTo */
        $enabledTo = $this->activeRecordFactory->getObject('Registry');
        $enabledTo->loadByKey('/server/maintenance/schedule/date/enabled/to/');

        if ($realTo->getValue() != $dateRealTo) {
            $realTo->setValue($dateRealTo)->save();
            $enabledTo->setValue($dateEnabledTo)->save();
        }
    }

    //########################################
}
