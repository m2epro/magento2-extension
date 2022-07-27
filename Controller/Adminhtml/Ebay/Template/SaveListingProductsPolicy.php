<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Template;

class SaveListingProductsPolicy extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Template
{
    /** @var \Magento\Framework\DB\TransactionFactory */
    private $transactionFactory = null;

    public function __construct(
        \Magento\Framework\DB\TransactionFactory $transactionFactory,
        \Ess\M2ePro\Model\Ebay\Template\Manager $templateManager,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($templateManager, $ebayFactory, $context);

        $this->transactionFactory = $transactionFactory;
    }

    public function execute()
    {
        $ids = $this->getRequestIds('products_id');

        if (!$post = $this->getRequest()->getPostValue() || empty($ids)) {
            $this->setAjaxContent('', false);
            return $this->getResult();
        }

        $collection = $this->ebayFactory->getObject('Listing\Product')->getCollection();
        $collection->addFieldToFilter('id', ['in' => $ids]);

        if ($collection->getSize() == 0) {
            $this->setAjaxContent('', false);
            return $this->getResult();
        }

        $data = $this->getPostedTemplatesData();

        $snapshots = [];
        $transaction = $this->transactionFactory->create();

        try {
            foreach ($collection->getItems() as $listingProduct) {
                /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
                /** @var \Ess\M2ePro\Model\Ebay\Listing\Product\SnapshotBuilder $snapshotBuilder */
                $snapshotBuilder = $this->modelFactory->getObject('Ebay_Listing_Product_SnapshotBuilder');
                $snapshotBuilder->setModel($listingProduct);

                $snapshots[$listingProduct->getId()] = $snapshotBuilder->getSnapshot();

                $listingProduct->addData($data);
                $listingProduct->getChildObject()->addData($data);
                $transaction->addObject($listingProduct);
            }

            $transaction->save();
        } catch (\Exception $e) {
            $snapshots = false;
            $transaction->rollback();
        }

        $this->setAjaxContent('', false);

        if (!$snapshots) {
            return $this->getResult();
        }

        /** @var \Ess\M2ePro\Model\Ebay\Template\AffectedListingsProducts\Processor $changesProcessor */
        $changesProcessor = $this->modelFactory->getObject('Ebay_Template_AffectedListingsProducts_Processor');

        foreach ($collection->getItems() as $listingProduct) {
            /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
            /** @var \Ess\M2ePro\Model\Ebay\Listing\Product\SnapshotBuilder $snapshotBuilder */
            $snapshotBuilder = $this->modelFactory->getObject('Ebay_Listing_Product_SnapshotBuilder');
            $snapshotBuilder->setModel($listingProduct);

            $newSnapshot = $snapshotBuilder->getSnapshot();
            $oldSnapshot = $snapshots[$listingProduct->getId()];

            $changesProcessor->setListingProduct($listingProduct);
            $changesProcessor->processChanges($newSnapshot, $oldSnapshot);
        }

        return $this->getResult();
    }

    private function getPostedTemplatesData(): array
    {
        if (!$post = $this->getRequest()->getPost()) {
            return [];
        }

        $data = [];

        foreach ($post as $keyId => $val) {
            $key = str_replace('_id', '', $keyId);
            $keyMode = $key . '_mode';

            if ($val) {
                $data[$keyMode] = \Ess\M2ePro\Model\Ebay\Template\Manager::MODE_TEMPLATE;
                $data[$keyId] = $val;
            } else {
                $data[$keyMode] = \Ess\M2ePro\Model\Ebay\Template\Manager::MODE_PARENT;
                $data[$keyId] = null;
            }
        }

        return $data;
    }
}
