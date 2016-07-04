<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\Stop;

class Response extends \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\Response
{
    //########################################

    /**
     * @param array $params
     */
    public function processSuccess($params = array())
    {
        $data = array();

        $data = $this->appendStatusChangerValue($data);
        $data = $this->appendQtyValues($data);

        $this->getListingProduct()->addData($data);
        $this->getListingProduct()->getChildObject()->addData($data);

        $this->setLastSynchronizationDates();

        $this->getListingProduct()->save();
    }

    //########################################

    protected function setLastSynchronizationDates()
    {
        $additionalData = $this->getListingProduct()->getAdditionalData();
        $additionalData['last_synchronization_dates']['qty'] = $this->getHelper('Data')->getCurrentGmtDate();
        $this->getListingProduct()->setSettings('additional_data', $additionalData);
    }

    //########################################
}