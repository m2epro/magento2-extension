<?php

namespace Ess\M2ePro\Setup\Update\y23_m10;

class ReAddIsSoldByAmazonColumnToAmazonOrder extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    /**
     * @return void
     * @throws \Ess\M2ePro\Model\Exception\Setup
     */
    public function execute()
    {
        $this->getTableModifier('amazon_order')
             ->addColumn(
                 'is_sold_by_amazon',
                 "SMALLINT UNSIGNED NOT NULL",
                 0,
                 'is_prime',
                 false,
                 false
             )
            ->commit();
    }
}
