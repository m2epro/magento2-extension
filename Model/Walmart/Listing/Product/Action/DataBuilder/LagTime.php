<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ss-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Listing\Product\Action\DataBuilder;

/**
 * Class \Ess\M2ePro\Model\Walmart\Listing\Product\Action\DataBuilder\LagTime
 */
class LagTime extends \Ess\M2ePro\Model\Walmart\Listing\Product\Action\DataBuilder\AbstractModel
{
    //########################################

    /**
     * @return array
     */
    public function getRequestData()
    {
        if (!isset($this->cachedData['lag_time'])) {
            $lagTime = $this->getWalmartListingProduct()->getSellingFormatTemplateSource()->getLagTime();
            $this->cachedData['lag_time'] = $lagTime;
        }

        $data['lag_time'] = $this->cachedData['lag_time'];

        return $data;
    }

    //########################################
}
