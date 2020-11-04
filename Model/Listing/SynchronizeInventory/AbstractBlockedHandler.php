<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Listing\SynchronizeInventory;

/**
 * Class \Ess\M2ePro\Model\Listing\SynchronizeInventory\AbstractBlockedHandler
 */
abstract class AbstractBlockedHandler extends AbstractHandler
{
    /** @var string */
    protected $listingProductTable;

    /** @var string */
    protected $listingProductChildTable;

    //########################################

    /**
     * @param array $responseData
     * @return array|void
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Db_Statement_Exception
     * @throws \Exception
     */
    public function handle(array $responseData = [])
    {
        $this->markNotReceivedListingProductsAsBlocked();

        if ($this->getAccount()->getChildObject()->getOtherListingsSynchronization()) {
            $this->markNotReceivedOtherListingsAsBlocked();
        }
    }

    /**
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Db_Statement_Exception
     * @throws \Exception
     */
    protected function markNotReceivedListingProductsAsBlocked()
    {
        $tempLog = $this->activeRecordFactory->getObject('Listing\Log');
        $tempLog->setComponentMode($this->getComponentMode());

        $notReceivedIds = [];
        $stmt = $this->getPdoStatementNotReceivedListingProducts();

        $uppercasedComponent = ucfirst($this->getComponentMode());

        /** @var \Ess\M2ePro\Helper\Component\Amazon|\Ess\M2ePro\Helper\Component\Walmart $componentHelper */
        $componentHelper = $this->helperFactory->getObject("Component\\{$uppercasedComponent}");

        while ($notReceivedItem = $stmt->fetch()) {

            if (!in_array((int)$notReceivedItem['id'], $notReceivedIds)) {
                $statusChangedFrom = $componentHelper->getHumanTitleByListingProductStatus($notReceivedItem['status']);
                $statusChangedTo = $componentHelper->getHumanTitleByListingProductStatus(
                    \Ess\M2ePro\Model\Listing\Product::STATUS_BLOCKED
                );

                $tempLogMessage = $this->helperFactory->getObject('Module_Translation')->__(
                    'Item Status was changed from "%from%" to "%to%" .',
                    $statusChangedFrom,
                    $statusChangedTo
                );

                $tempLog->addProductMessage(
                    $notReceivedItem['listing_id'],
                    $notReceivedItem['product_id'],
                    $notReceivedItem['id'],
                    \Ess\M2ePro\Helper\Data::INITIATOR_EXTENSION,
                    $this->getLogsActionId(),
                    \Ess\M2ePro\Model\Listing\Log::ACTION_CHANNEL_CHANGE,
                    $tempLogMessage,
                    \Ess\M2ePro\Model\Log\AbstractModel::TYPE_SUCCESS
                );

                if (!empty($notReceivedItem['is_variation_product']) &&
                    !empty($notReceivedItem['variation_parent_id'])
                ) {
                    $parentIdsForProcessing[] = $notReceivedItem['variation_parent_id'];
                }
            }

            $notReceivedIds[] = (int)$notReceivedItem['id'];
        }

        $notReceivedIds = array_unique($notReceivedIds);

        if (empty($notReceivedIds)) {
            return;
        }

        $this->listingProductTable = $this->activeRecordFactory
            ->getObject('Listing\Product')
            ->getResource()
            ->getMainTable();

        $this->listingProductChildTable = $this->activeRecordFactory
            ->getObject("{$uppercasedComponent}\Listing\Product")
            ->getResource()
            ->getMainTable();

        foreach (array_chunk($notReceivedIds, 1000) as $idsPart) {
            $this->updateListingProductStatuses($idsPart);
        }

        if (!empty($parentIdsForProcessing)) {
            $this->resourceConnection->getConnection()->update(
                $this->listingProductChildTable,
                ['variation_parent_need_processor' => 1],
                [
                    'is_variation_parent = ?'   => 1,
                    'listing_product_id IN (?)' => $parentIdsForProcessing,
                ]
            );
        }
    }

    /**
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function markNotReceivedOtherListingsAsBlocked()
    {
        /** @var \Ess\M2ePro\Helper\Module\Database\Structure $structureHelper */
        $structureHelper = $this->helperFactory->getObject('Module_Database_Structure');

        $statusBlocked = \Ess\M2ePro\Model\Listing\Product::STATUS_BLOCKED;
        $statusNotListed = \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED;
        $statusChangerComponent = \Ess\M2ePro\Model\Listing\Product::STATUS_CHANGER_COMPONENT;

        $sql = <<<SQL
UPDATE {$structureHelper->getTableNameWithPrefix('m2epro_listing_other')} AS `lo`
    INNER JOIN {$this->getComponentOtherListingTable()} AS `clo` 
        ON `lo`.`id` = `clo`.`listing_other_id`
    LEFT JOIN {$this->getComponentInventoryTable()} AS `it` 
        ON `clo`.`{$this->getInventoryIdentifier()}` = `it`.`{$this->getInventoryIdentifier()}` 
        AND `lo`.`account_id` = `it`.`account_id`
SET `lo`.`status` = {$statusBlocked}, `lo`.`status_changer` = {$statusChangerComponent}
WHERE `lo`.`account_id` = {$this->getAccount()->getId()}
  AND `lo`.`status` != {$statusBlocked} 
  AND `lo`.`status` != {$statusNotListed} 
  AND `it`.`{$this->getInventoryIdentifier()}` IS NULL
SQL;

        $this->resourceConnection->getConnection()->query($sql);
    }

    /**
     * @param array $listingProductIds
     */
    protected function updateListingProductStatuses(array $listingProductIds)
    {
        $this->resourceConnection->getConnection()->update(
            $this->listingProductTable,
            [
                'status'         => \Ess\M2ePro\Model\Listing\Product::STATUS_BLOCKED,
                'status_changer' => \Ess\M2ePro\Model\Listing\Product::STATUS_CHANGER_COMPONENT,
            ],
            '`id` IN ('.implode(',', $listingProductIds).')'
        );
    }

    /**
     * @return string
     */
    abstract protected function getComponentOtherListingTable();

    /**
     * @return string
     */
    abstract protected function getComponentInventoryTable();

    /**
     * @return \Zend_Db_Statement_Interface
     * @throws \Exception
     */
    abstract protected function getPdoStatementNotReceivedListingProducts();

    //########################################
}
