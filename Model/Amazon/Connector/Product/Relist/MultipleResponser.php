<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

/**
 * @method \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\Revise\Response getResponseObject($listingProduct)
 */
namespace Ess\M2ePro\Model\Amazon\Connector\Product\Relist;

class MultipleResponser extends \Ess\M2ePro\Model\Amazon\Connector\Product\Responser
{
    // ########################################

    protected function getSuccessfulMessage(\Ess\M2ePro\Model\Listing\Product $listingProduct)
    {
        return $this->getResponseObject($listingProduct)->getSuccessfulMessage();
    }

    // ########################################
}