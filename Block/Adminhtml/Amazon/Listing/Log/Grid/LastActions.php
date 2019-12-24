<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Log\Grid;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Log\Grid\LastActions
 */
class LastActions extends \Ess\M2ePro\Block\Adminhtml\Listing\Log\Grid\LastActions
{
    protected $resourceConnection;

    //########################################

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        array $data = []
    ) {
        $this->resourceConnection = $resourceConnection;

        parent::__construct($context, $data);
    }

    //########################################

    protected function getGroupedActions(array $logs)
    {
        $actions = parent::getGroupedActions($logs);

        if (!$this->isVariationParent()) {
            return $actions;
        }

        foreach ($actions as &$actionsRow) {
            if (empty($actionsRow['items'])) {
                continue;
            }

            $firstItem = reset($actionsRow['items']);

            if ($firstItem['listing_product_id'] == $this->getEntityId()) {
                continue;
            }

            $actionsRow['action_in_progress'] = $this->isActionInProgress($firstItem['action_id']);

            $descArr = [];
            foreach ($actionsRow['items'] as $key => &$item) {
                if (array_key_exists((string)$item['description'], $descArr)) {
                    $descArr[(string)$item['description']]['count']++;
                    unset($actionsRow['items'][$key]);
                    continue;
                }
                $item['count'] = 1;
                $descArr[(string)$item['description']] = $item;
            }
            $actionsRow['items'] = array_values($descArr);
        }

        return $actions;
    }

    protected function isVariationParent()
    {
        if (!$this->hasData('is_variation_parent')) {
            return false;
        }

        return $this->getData('is_variation_parent');
    }

    protected function isActionInProgress($actionId)
    {
        $connection = $this->resourceConnection->getConnection();

        // Get last messages
        // ---------------------------------------
        $dbSelect = $connection->select()
            ->from(
                $this->activeRecordFactory->getObject('Processing')->getResource()->getMainTable()
            )
            ->where('params REGEXP \'"logs_action_id":'.$actionId.'\'')
            ->limit(1);

        $result = $connection->query($dbSelect)->fetch();
        return $result !== false;
    }

    //########################################
}
