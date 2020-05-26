<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Template;

use Ess\M2ePro\Controller\Adminhtml\Context;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Template\Shipping
 */
abstract class Shipping extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Template
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

    protected function setShippingTemplateForProducts($productsIds, $templateId)
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
                    ->getData('template_shipping_id');
                $listingProduct->getChildObject()->setData('template_shipping_id', $templateId);
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

        /** @var \Ess\M2ePro\Model\Amazon\Template\Shipping $newTemplate */
        $newTemplate = $this->activeRecordFactory->getObjectLoaded(
            'Amazon_Template_Shipping',
            $templateId,
            null,
            false
        );

        if ($newTemplate !== null && $newTemplate->getId()) {
            /** @var \Ess\M2ePro\Model\Amazon\Template\Shipping\SnapshotBuilder $snapshotBuilder */
            $snapshotBuilder = $this->modelFactory->getObject('Amazon_Template_Shipping_SnapshotBuilder');
            $snapshotBuilder->setModel($newTemplate);
            $newSnapshot = $snapshotBuilder->getSnapshot();
        } else {
            $newSnapshot = [];
        }

        /**@var \Ess\M2ePro\Model\Listing\Product $listingProduct */
        foreach ($collection->getItems() as $listingProduct) {
            /** @var \Ess\M2ePro\Model\Amazon\Template\Shipping $oldTemplate */
            $oldTemplate = $this->activeRecordFactory->getObjectLoaded(
                'Amazon_Template_Shipping',
                $oldTemplateIds[$listingProduct->getId()],
                null,
                false
            );

            if ($oldTemplate !== null && $oldTemplate->getId()) {
                $snapshotBuilder = $this->modelFactory->getObject('Amazon_Template_Shipping_SnapshotBuilder');
                $snapshotBuilder->setModel($oldTemplate);
                $oldSnapshot = $snapshotBuilder->getSnapshot();
            } else {
                $oldSnapshot = [];
            }

            if (empty($newSnapshot) && empty($oldSnapshot)) {
                continue;
            }

            /** @var \Ess\M2ePro\Model\Amazon\Template\Shipping\Diff $diff */
            $diff = $this->modelFactory->getObject('Amazon_Template_Shipping_Diff');
            $diff->setOldSnapshot($oldSnapshot);
            $diff->setNewSnapshot($newSnapshot);

            /** @var \Ess\M2ePro\Model\Amazon\Template\Shipping\ChangeProcessor $changeProcessor */
            $changeProcessor = $this->modelFactory->getObject('Amazon_Template_Shipping_ChangeProcessor');
            $changeProcessor->process(
                $diff,
                [['id' => $listingProduct->getId(), 'status' => $listingProduct->getStatus()]]
            );
        }
    }

    //########################################
}
