<?php

namespace Ess\M2ePro\Setup\Update\y21_m08;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

class FixedStuckedManualPriceRevise extends AbstractFeature
{
    //########################################

    public function execute()
    {
        $scheduledAction = $this->getFullTableName('listing_product_scheduled_action');

        $stmt = $this->getConnection()->select()
            ->from(
                $scheduledAction,
                ['id', 'tag']
            )
            ->where('component = ?', 'amazon')
            ->where('tag LIKE ?', '%price_regular%')
            ->orWhere('tag LIKE ?', '%price_business%')
            ->query();

        while ($row = $stmt->fetch()) {
            $tags = array_filter(
                explode('/', $row['tag']),
                function ($tag) {
                    return !empty($tag) && $tag !== 'price_regular' && $tag !== 'price_business';
                }
            );

            $tags[] = 'price';

            $tags = '/' . implode('/', $tags) . '/';

            $this->getConnection()->update(
                $scheduledAction,
                ['tag' => $tags],
                ['id = ?' => (int)$row['id']]
            );
        }
    }

    //########################################
}
