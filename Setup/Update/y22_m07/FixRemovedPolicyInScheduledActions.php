<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Update\y22_m07;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

class FixRemovedPolicyInScheduledActions extends AbstractFeature
{
    public function execute()
    {
        $scheduledActionTable = $this->getFullTableName('listing_product_scheduled_action');
        $query = $this->getConnection()->select()
            ->from(
                $scheduledActionTable,
                ['id', 'tag', 'additional_data']
            )
            ->where('component = ?', 'ebay')
            ->where("tag LIKE '%payment%' OR additional_data LIKE '%\"payment\"%'")
            ->query();

        while ($row = $query->fetch()) {
            $tags = array_filter(
                explode('/', $row['tag']),
                function ($tag) {
                    return !empty($tag) && $tag !== 'payment';
                }
            );
            $tags = '/' . implode('/', $tags) . '/';

            $additionalData = json_decode($row['additional_data'], true);
            if (!empty($additionalData['configurator']['allowed_data_types'])) {
                $additionalData['configurator']['allowed_data_types'] = array_filter(
                    $additionalData['configurator']['allowed_data_types'],
                    function ($dataType) {
                        return $dataType !== 'payment';
                    }
                );
            }
            $additionalData = json_encode($additionalData);

            $this->getConnection()->update(
                $scheduledActionTable,
                ['tag' => $tags, 'additional_data' => $additionalData],
                ['id = ?' => (int)$row['id']]
            );
        }
    }
}
