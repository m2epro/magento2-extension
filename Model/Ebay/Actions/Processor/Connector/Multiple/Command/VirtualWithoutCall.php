<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Actions\Processor\Connector\Multiple\Command;

class VirtualWithoutCall extends \Ess\M2ePro\Model\Connector\Command\RealTime\Virtual
{
    // ########################################

    public function process()
    {
        if (is_null($this->getConnection()->getResponse())) {
            throw new \Ess\M2ePro\Model\Exception\Logic(
                'This object must be processed in Ess_M2ePro_Model_Connector_Connection_Multiple.'
            );
        }

        if (!$this->validateResponse()) {
            throw new \Ess\M2ePro\Model\Exception('Validation Failed. The Server response data is not valid.');
        }

        $this->prepareResponseData();
    }

    // ########################################

    public function getCommandConnection()
    {
        return $this->getConnection();
    }

    // ########################################
}