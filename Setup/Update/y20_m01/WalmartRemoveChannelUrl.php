<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Update\y20_m01;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

/**
 * Class \Ess\M2ePro\Setup\Update\y20_m01\WalmartRemoveChannelUrl
 */
class WalmartRemoveChannelUrl extends AbstractFeature
{
    //########################################

    public function execute()
    {
        $this->getTableModifier('walmart_listing_other')->dropColumn('channel_url');
        $this->getTableModifier('walmart_listing_product')->dropColumn('channel_url');
    }

    //########################################
}
