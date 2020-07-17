<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\M2ePro\Connector\Server\Servicing;

/**
 * Class \Ess\M2ePro\Model\M2ePro\Connector\Server\Servicing\UpdateData
 */
class UpdateData extends \Ess\M2ePro\Model\Connector\Command\RealTime
{
    //########################################

    protected function getCommand()
    {
        return ['servicing', 'update', 'data'];
    }

    public function getRequestData()
    {
        return $this->params;
    }

    //########################################

    protected function buildConnectionInstance()
    {
        $connection = parent::buildConnectionInstance();
        $connection->setCanIgnoreMaintenance(true);

        return $connection;
    }

    //########################################
}
