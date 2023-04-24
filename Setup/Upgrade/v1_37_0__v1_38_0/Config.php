<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Upgrade\v1_37_0__v1_38_0;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractConfig;

class Config extends AbstractConfig
{
    public function getFeaturesList(): array
    {
        return [
            '@y23_m01/AmazonProductTypes',
            '@y23_m02/AmazonShippingTemplates',
            '@y23_m03/AddColumnIsStoppedManuallyForAmazonAndWalmartProducts',
        ];
    }
}
