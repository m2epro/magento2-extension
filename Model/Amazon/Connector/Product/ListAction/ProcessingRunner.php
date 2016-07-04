<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Connector\Product\ListAction;

class ProcessingRunner extends \Ess\M2ePro\Model\Amazon\Connector\Product\ProcessingRunner
{
    // ########################################

    protected function eventBefore()
    {
        parent::eventBefore();

        $params = $this->getParams();

        $skus = array();

        foreach ($params['request_data']['items'] as $productData) {
            $skus[] = $productData['sku'];
        }

        /** @var \Ess\M2ePro\Model\LockItem $lockItem */
        $lockItem = $this->activeRecordFactory->getObject('LockItem');
        $lockItem->setNick('amazon_list_skus_queue_' . $params['account_id']);

        if ($lockItem->isExist()) {
            $existSkus = $lockItem->getContentData();
        } else {
            $existSkus = array();
            $lockItem->create();
        }

        $skus = array_map('strval', $skus);
        $skus = array_merge($existSkus, $skus);

        $lockItem->setContentData($skus);
    }

    protected function eventAfter()
    {
        parent::eventAfter();

        $params = $this->getParams();

        /** @var \Ess\M2ePro\Model\LockItem $lockItem */
        $lockItem = $this->activeRecordFactory->getObject('LockItem');
        $lockItem->setNick('amazon_list_skus_queue_' . $params['account_id']);

        if (!$lockItem->isExist()) {
            return;
        }

        $skusToRemove = array();

        foreach ($params['request_data']['items'] as $productData) {
            $skusToRemove[] = (string)$productData['sku'];
        }

        $resultSkus = array_diff($lockItem->getContentData(), $skusToRemove);

        if (empty($resultSkus)) {
            $lockItem->remove();
            return;
        }

        $lockItem->setContentData($resultSkus);
    }

    // ########################################
}