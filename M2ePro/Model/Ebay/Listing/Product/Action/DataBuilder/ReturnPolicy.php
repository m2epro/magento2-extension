<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Listing\Product\Action\DataBuilder;

/**
 * Class \Ess\M2ePro\Model\Ebay\Listing\Product\Action\DataBuilder\ReturnPolicy
 */
class ReturnPolicy extends AbstractModel
{
    /** @var \Ess\M2ePro\Model\Ebay\Template\ReturnPolicy */
    protected $returnTemplate = null;

    //########################################

    public function getBuilderData()
    {
        return [
            'return' => [
                'accepted' => $this->getReturnTemplate()->getAccepted(),
                'option' => $this->getReturnTemplate()->getOption(),
                'within' => $this->getReturnTemplate()->getWithin(),
                'shipping_cost' => $this->getReturnTemplate()->getShippingCost(),

                'international_accepted' => $this->getReturnTemplate()->getInternationalAccepted(),
                'international_option' => $this->getReturnTemplate()->getInternationalOption(),
                'international_within' => $this->getReturnTemplate()->getInternationalWithin(),
                'international_shipping_cost' => $this->getReturnTemplate()->getInternationalShippingCost(),

                'description' => $this->getReturnTemplate()->getDescription()
            ]
        ];
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Ebay\Template\ReturnPolicy
     */
    protected function getReturnTemplate()
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
