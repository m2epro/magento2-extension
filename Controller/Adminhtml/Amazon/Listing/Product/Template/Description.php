<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Template;

use Ess\M2ePro\Controller\Adminhtml\Context;

abstract class Description extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Template
{
    protected $transactionFactory;

    //########################################

    public function __construct(
        \Magento\Framework\DB\TransactionFactory $transactionFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        Context $context
    )
    {
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
            $tableAmazonListingProduct = $this->activeRecordFactory->getObject('Amazon\Listing\Product')
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
        $collection->addFieldToFilter('id', array('in' => $productsIds));
        // ---------------------------------------

        if ($collection->getSize() == 0) {
            return;
        }

        /** @var \Magento\Framework\DB\Transaction $transaction */
        $transaction = $this->transactionFactory->create();
        $oldSnapshots = array();

        try {
            foreach ($collection->getItems() as $listingProduct) {
                /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */

                $oldSnapshots[$listingProduct->getId()] = array_merge(
                    $listingProduct->getDataSnapshot(), $listingProduct->getChildObject()->getDataSnapshot()
                );

                $listingProduct->getChildObject()->setData('template_description_id', $templateId);
                $transaction->addObject($listingProduct);
            }

            $transaction->save();
        } catch (\Exception $e) {
            $oldSnapshots = false;
            $transaction->rollback();
        }

        if (!$oldSnapshots) {
            return;
        }

        foreach ($collection->getItems() as $listingProduct) {
            /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */

            $listingProduct->getChildObject()->setSynchStatusNeed(
                array_merge(
                    $listingProduct->getDataSnapshot(), $listingProduct->getChildObject()->getDataSnapshot()
                ),
                $oldSnapshots[$listingProduct->getId()]
            );
        }
    }

    //########################################
}