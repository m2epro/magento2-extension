<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Template;

use Ess\M2ePro\Controller\Adminhtml\Context;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Template\Description
 */
abstract class Description extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Template
{
    protected $transactionFactory;

    //########################################

    public function __construct(
        \Magento\Framework\DB\TransactionFactory $transactionFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        Context $context
    ) {
        $this->transactionFactory = $transactionFactory;
        parent::__construct($amazonFactory, $context);
    }

    //########################################

    protected function filterProductsForMapOrUnmapDescriptionTemplate($productsIdsParam)
    {
        $productsIds = [];
        $productsIdsParam = array_chunk($productsIdsParam, 1000);
        foreach ($productsIdsParam as $productsIdsParamChunk) {
            $connection = $this->resourceConnection->getConnection();
            $tableAmazonListingProduct = $this->activeRecordFactory->getObject('Amazon_Listing_Product')
                ->getResource()->getMainTable();

            $select = $connection->select();

            // selecting all except parents general_id owners or simple general_id owners without general_id
            $select->from($tableAmazonListingProduct, 'listing_product_id')
                ->where('is_general_id_owner = 0
                OR (is_general_id_owner = 1
                    AND is_variation_parent = 0 AND general_id IS NOT NULL)');

            $select->where('listing_product_id IN (?)', $productsIdsParamChunk);

            $productsIds = array_merge($productsIds, $connection->fetchCol($select));
        }

        return $productsIds;
    }

    protected function setDescriptionTemplateForProducts($productsIds, $templateId)
    {
        if (empty($productsIds)) {
            return;
        }

        $collection = $this->amazonFactory->getObject('Listing\Product')->getCollection();
        $collection->addFieldToFilter('id', ['in' => $productsIds]);
        // ---------------------------------------

        if ($collection->getSize() == 0) {
            return;
        }

        /** @var \Magento\Framework\DB\Transaction $transaction */
        $transaction = $this->transactionFactory->create();
        $oldTemplateIds = [];

        try {
            /**@var \Ess\M2ePro\Model\Listing\Product $listingProduct */
            foreach ($collection->getItems() as $listingProduct) {
                $oldTemplateIds[$listingProduct->getId()] = $listingProduct->getChildObject()
                    ->getData('template_description_id');
                $listingProduct->getChildObject()->setData('template_description_id', $templateId);
                $transaction->addObject($listingProduct);
            }

            $transaction->save();
        } catch (\Exception $e) {
            $oldTemplateIds = false;
            $transaction->rollback();
        }

        if (!$oldTemplateIds) {
            return;
        }

        /** @var \Ess\M2ePro\Model\Amazon\Template\Description $newTemplate */
        $newTemplate = $this->activeRecordFactory->getObjectLoaded(
            'Amazon_Template_Description',
            $templateId,
            null,
            false
        );

        if ($newTemplate !== null && $newTemplate->getId()) {
            /** @var \Ess\M2ePro\Model\Amazon\Template\Description\SnapshotBuilder $snapshotBuilder */
            $snapshotBuilder = $this->modelFactory->getObject('Amazon_Template_Description_SnapshotBuilder');
            $snapshotBuilder->setModel($newTemplate->getParentObject());
            $newSnapshot = $snapshotBuilder->getSnapshot();
        } else {
            $newSnapshot = [];
        }

        /**@var \Ess\M2ePro\Model\Listing\Product $listingProduct */
        foreach ($collection->getItems() as $listingProduct) {
            /** @var \Ess\M2ePro\Model\Amazon\Template\Description $oldTemplate */
            $oldTemplate = $this->activeRecordFactory->getObjectLoaded(
                'Amazon_Template_Description',
                $oldTemplateIds[$listingProduct->getId()],
                null,
                false
            );

            if ($oldTemplate !== null && $oldTemplate->getId()) {
                $snapshotBuilder = $this->modelFactory->getObject('Amazon_Template_Description_SnapshotBuilder');
                $snapshotBuilder->setModel($oldTemplate->getParentObject());
                $oldSnapshot = $snapshotBuilder->getSnapshot();
            } else {
                $oldSnapshot = [];
            }

            if (empty($newSnapshot) && empty($oldSnapshot)) {
                continue;
            }

            /** @var \Ess\M2ePro\Model\Amazon\Template\Description\Diff $diff */
            $diff = $this->modelFactory->getObject('Amazon_Template_Description_Diff');
            $diff->setOldSnapshot($oldSnapshot);
            $diff->setNewSnapshot($newSnapshot);

            /** @var \Ess\M2ePro\Model\Amazon\Template\Description\ChangeProcessor $changeProcessor */
            $changeProcessor = $this->modelFactory->getObject('Amazon_Template_Description_ChangeProcessor');
            $changeProcessor->process(
                $diff,
                [['id' => $listingProduct->getId(), 'status' => $listingProduct->getStatus()]]
            );
        }
    }

    //########################################
}
