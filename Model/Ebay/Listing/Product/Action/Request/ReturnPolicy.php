<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Listing\Product\Action\Request;

class ReturnPolicy extends \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Request\AbstractModel
{
    /**
     * @var \Ess\M2ePro\Model\Ebay\Template\ReturnPolicy
     */
    private $returnTemplate = NULL;

    //########################################

    /**
     * @return array
     */
    public function getRequestData()
    {
        return array(
            'return' => array(
                'accepted'      => $this->getReturnTemplate()->getAccepted(),
                'option'        => $this->getReturnTemplate()->getOption(),
                'within'        => $this->getReturnTemplate()->getWithin(),
                'is_holiday_enabled' => $this->getReturnTemplate()->isHolidayEnabled(),
                'description'   => $this->getReturnTemplate()->getDescription(),
                'shipping_cost'  => $this->getReturnTemplate()->getShippingCost(),
                'restocking_fee' => $this->getReturnTemplate()->getRestockingFee()
            )
        );
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Ebay\Template\ReturnPolicy
     */
    private function getReturnTemplate()
    {
        if (is_null($this->returnTemplate)) {
            $this->returnTemplate = $this->getListingProduct()
                                         ->getChildObject()
                                         ->getReturnTemplate();
        }
        return $this->returnTemplate;
    }

    //########################################
}