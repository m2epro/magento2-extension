<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Update\y20_m01;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

class RemoveOutOfStockControl extends AbstractFeature
{
    //########################################

    public function execute()
    {
        $this->getTableModifier('ebay_template_selling_format')->dropColumn('out_of_stock_control');
        $this->getConnection()
            ->update(
                $this->getFullTableName('ebay_template_selling_format'),
                ['duration_mode' => 100], // \Ess\M2ePro\Helper\Component\Ebay::LISTING_DURATION_GTC
                ['listing_type = ?' => 2] // \Ess\M2ePro\Model\Ebay\Template\SellingFormat::LISTING_TYPE_FIXED
            );
    }

    //########################################
}