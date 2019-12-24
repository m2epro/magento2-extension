<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Listing\Product\Action\Request;

/**
 * Class \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Request\ReturnPolicy
 */
class ReturnPolicy extends \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Request\AbstractModel
{
    /**
     * @var \Ess\M2ePro\Model\Ebay\Template\ReturnPolicy
     */
    private $returnTemplate = null;

    //########################################

    /**
     * @return array
     */
    public function getRequestData()
    {
        return [
            'return' => [
                'accepted'      => $this->getReturnTemplate()->getAccepted(),
                'option'        => $this->getReturnTemplate()->getOption(),
                'within'        => $this->getReturnTemplate()->getWithin(),
                'is_holiday_enabled' => $this->getReturnTemplate()->isHolidayEnabled(),
                'description'   => $this->getReturnTemplate()->getDescription(),
                'shipping_cost'  => $this->getReturnTemplate()->getShippingCost(),
                'restocking_fee' => $this->getReturnTemplate()->getRestockingFee()
            ]
        ];
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Ebay\Template\ReturnPolicy
     */
    private function getReturnTemplate()
    {
        if ($this->returnTemplate === null) {
            $this->returnTemplate = $this->getListingProduct()
                                         ->getChildObject()
                                         ->getReturnTemplate();
        }
        return $this->returnTemplate;
    }

    //########################################
}
