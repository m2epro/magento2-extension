<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Listing\Product\Action\Type\Stop;

class Request extends \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Type\Request
{
    //########################################

    protected function afterBuildDataEvent(array $data)
    {
        $this->getConfigurator()->setPriority(
            \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Configurator::PRIORITY_STOP
        );

        parent::afterBuildDataEvent($data);
    }

    //########################################

    /**
     * @return array
     */
    public function getActionData()
    {
        return array(
            'item_id' => $this->getEbayListingProduct()->getEbayItemIdReal()
        );
    }

    //########################################

    protected function initializeVariations() {}

    // ---------------------------------------

    protected function prepareFinalData(array $data)
    {
        return $data;
    }

    //########################################
}