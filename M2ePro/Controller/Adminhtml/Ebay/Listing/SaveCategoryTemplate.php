<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\SaveCategoryTemplate
 */
class SaveCategoryTemplate extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing
{
    /** @var \Magento\Framework\DB\TransactionFactory  */
    protected $transactionFactory = null;

    //########################################

    public function __construct(
        \Magento\Framework\DB\TransactionFactory $transactionFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        $this->transactionFactory = $transactionFactory;
        parent::__construct($ebayFactory, $context);
    }

    //########################################

    public function execute()
    {
        if (!$post = $this->getRequest()->getPostValue()) {
            $this->setAjaxContent('0', false);
            return $this->getResult();
        }

        if (!isset($post['template_category_data'])) {
            $this->setAjaxContent('0', false);
            return $this->getResult();
        }

        // ---------------------------------------
        $listingId = $this->getRequest()->getParam('id');
        $listingProductIds = $this->getRequestIds();
        $categoryTemplateData = $post['template_category_data'];
        $categoryTemplateData = $this->getHelper('Data')->jsonDecode($categoryTemplateData);
        // ---------------------------------------

        // ---------------------------------------
        /** @var \Ess\M2ePro\Model\Listing $listing */
        $listing = $this->ebayFactory->getCachedObjectLoaded('Listing', $listingId);
        // ---------------------------------------

        // ---------------------------------------
        $this->getHelper('Component_Ebay_Category')->fillCategoriesPaths($categoryTemplateData, $listing);

        $builderData = $categoryTemplateData;
        $builderData['account_id'] = $listing->getAccountId();
        $builderData['marketplace_id'] = $listing->getMarketplaceId();

        // ---------------------------------------
        $builder = $this->modelFactory->getObject('Ebay_Template_Category_Builder');
        $categoryTemplate = $builder->build($builderData);
        // ---------------------------------------
        $builder = $this->modelFactory->getObject('Ebay_Template_OtherCategory_Builder');
        $otherCategoryTemplate = $builder->build($builderData);
        // ---------------------------------------

        $this->assignTemplatesToProducts(
            $categoryTemplate->getId(),
            $otherCategoryTemplate->getId(),
            $listingProductIds
        );

        $this->setAjaxContent('1', false);
        return $this->getResult();
    }

    //########################################

    private function assignTemplatesToProducts($categoryTemplateId, $otherCategoryTemplateId, $productsIds)
    {
        if (empty($productsIds)) {
            return;
        }

        $collection = $this->ebayFactory->getObject('Listing\Product')->getCollection();
        $collection->addFieldToFilter('id', ['in' => $productsIds]);
        // ---------------------------------------

        if ($collection->getSize() == 0) {
            return;
        }

        $snapshots   = [];
        $transaction = $this->transactionFactory->create();

        try {
            foreach ($collection->getItems() as $listingProduct) {
                /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */

                /** @var \Ess\M2ePro\Model\Ebay\Listing\Product\SnapshotBuilder $snapshotBuilder */
                $snapshotBuilder = $this->modelFactory->getObject('Ebay_Listing_Product_SnapshotBuilder');
                $snapshotBuilder->setModel($listingProduct);

                $snapshots[$listingProduct->getId()] = $snapshotBuilder->getSnapshot();

                $listingProduct->getChildObject()->setData('template_category_id', $categoryTemplateId);
                $listingProduct->getChildObject()->setData('template_other_category_id', $otherCategoryTemplateId);

                $transaction->addObject($listingProduct);
            }

            $transaction->save();
        } catch (\Exception $e) {
            $snapshots = false;
        }

        if (!$snapshots) {
            return;
        }

        $templateManager = $this->modelFactory->getObject('Ebay_Template_Manager');

        foreach ($collection->getItems() as $listingProduct) {
            /** @var \Ess\M2ePro\Model\Ebay\Listing\Product\SnapshotBuilder $snapshotBuilder */
            $snapshotBuilder = $this->modelFactory->getObject('Ebay_Listing_Product_SnapshotBuilder');
            $snapshotBuilder->setModel($listingProduct);

            $newData = $snapshotBuilder->getSnapshot();

            $newTemplates = $templateManager->getTemplatesFromData($newData);
            $oldTemplates = $templateManager->getTemplatesFromData($snapshots[$listingProduct->getId()]);

            foreach ($templateManager->getAllTemplates() as $template) {
                $templateManager->setTemplate($template);

                /** @var \Ess\M2ePro\Model\Template\SnapshotBuilder\AbstractModel $snapshotBuilder */
                if ($templateManager->isHorizontalTemplate()) {
                    $snapshotBuilder = $this->modelFactory->getObject(
                        'Ebay_'.$templateManager->getTemplateModelName().'_SnapshotBuilder'
                    );
                } else {
                    $snapshotBuilder = $this->modelFactory->getObject(
                        $templateManager->getTemplateModelName().'_SnapshotBuilder'
                    );
                }

                $snapshotBuilder->setModel($newTemplates[$template]);

                $newTemplateData = $snapshotBuilder->getSnapshot();

                /** @var \Ess\M2ePro\Model\Template\SnapshotBuilder\AbstractModel $snapshotBuilder */
                if ($templateManager->isHorizontalTemplate()) {
                    $snapshotBuilder = $this->modelFactory->getObject(
                        'Ebay_'.$templateManager->getTemplateModelName().'_SnapshotBuilder'
                    );
                } else {
                    $snapshotBuilder = $this->modelFactory->getObject(
                        $templateManager->getTemplateModelName().'_SnapshotBuilder'
                    );
                }

                $snapshotBuilder->setModel($oldTemplates[$template]);

                $oldTemplateData = $snapshotBuilder->getSnapshot();

                /** @var \Ess\M2ePro\Model\Template\Diff\AbstractModel $diff */
                if ($templateManager->isHorizontalTemplate()) {
                    $diff = $this->modelFactory->getObject('Ebay_'.$templateManager->getTemplateModelName().'_Diff');
                } else {
                    $diff = $this->modelFactory->getObject(''.$templateManager->getTemplateModelName().'_Diff');
                }

                $diff->setNewSnapshot($newTemplateData);
                $diff->setOldSnapshot($oldTemplateData);

                /** @var \Ess\M2ePro\Model\Template\ChangeProcessor\AbstractModel $changeProcessor */
                if ($templateManager->isHorizontalTemplate()) {
                    $changeProcessor = $this->modelFactory->getObject(
                        'Ebay_'.$templateManager->getTemplateModelName().'_ChangeProcessor'
                    );
                } else {
                    $changeProcessor = $this->modelFactory->getObject(
                        $templateManager->getTemplateModelName().'_ChangeProcessor'
                    );
                }

                $changeProcessor->process(
                    $diff,
                    [['id' => $listingProduct->getId(), 'status' => $listingProduct->getStatus()]]
                );
            }

            $this->processCategoryTemplateChange($listingProduct, $newData, $snapshots[$listingProduct->getId()]);
            $this->processOtherCategoryTemplateChange($listingProduct, $newData, $snapshots[$listingProduct->getId()]);
        }
    }

    private function processCategoryTemplateChange($listingProduct, array $newData, array $oldData)
    {
        /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */

        $newTemplateSnapshot = [];

        try {
            /** @var \Ess\M2ePro\Model\Ebay\Template\Category\SnapshotBuilder $snapshotBuilder */
            $snapshotBuilder = $this->modelFactory->getObject('Ebay_Template_Category_SnapshotBuilder');

            $newTemplate = $this->activeRecordFactory->getCachedObjectLoaded(
                'Ebay_Template_Category',
                $newData['template_category_id']
            );
            $snapshotBuilder->setModel($newTemplate);

            $newTemplateSnapshot = $snapshotBuilder->getSnapshot();
            // @codingStandardsIgnoreLine
        } catch (\Exception $exception) {}

        $oldTemplateSnapshot = [];

        try {
            /** @var \Ess\M2ePro\Model\Ebay\Template\Category\SnapshotBuilder $snapshotBuilder */
            $snapshotBuilder = $this->modelFactory->getObject('Ebay_Template_Category_SnapshotBuilder');

            $oldTemplate = $this->activeRecordFactory->getCachedObjectLoaded(
                'Ebay_Template_Category',
                $oldData['template_category_id']
            );
            $snapshotBuilder->setModel($oldTemplate);

            $oldTemplateSnapshot = $snapshotBuilder->getSnapshot();
            // @codingStandardsIgnoreLine
        } catch (\Exception $exception) {}

        /** @var \Ess\M2ePro\Model\Ebay\Template\Category\Diff $diff */
        $diff = $this->modelFactory->getObject('Ebay_Template_Category_Diff');
        $diff->setNewSnapshot($newTemplateSnapshot);
        $diff->setOldSnapshot($oldTemplateSnapshot);

        /** @var \Ess\M2ePro\Model\Ebay\Template\Category\ChangeProcessor $changeProcessor */
        $changeProcessor = $this->modelFactory->getObject('Ebay_Template_Category_ChangeProcessor');
        $changeProcessor->process(
            $diff,
            [['id' => $listingProduct->getId(), 'status' => $listingProduct->getStatus()]]
        );
    }

    private function processOtherCategoryTemplateChange($listingProduct, array $newData, array $oldData)
    {
        /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */

        $newTemplateSnapshot = [];

        try {
            /** @var \Ess\M2ePro\Model\Ebay\Template\OtherCategory\SnapshotBuilder $snapshotBuilder */
            $snapshotBuilder = $this->modelFactory->getObject('Ebay_Template_OtherCategory_SnapshotBuilder');

            $newTemplate = $this->activeRecordFactory->getCachedObjectLoaded(
                'Ebay_Template_OtherCategory',
                $newData['template_other_category_id']
            );
            $snapshotBuilder->setModel($newTemplate);

            $newTemplateSnapshot = $snapshotBuilder->getSnapshot();
            // @codingStandardsIgnoreLine
        } catch (\Exception $exception) {}

        $oldTemplateSnapshot = [];

        try {
            /** @var \Ess\M2ePro\Model\Ebay\Template\OtherCategory\SnapshotBuilder $snapshotBuilder */
            $snapshotBuilder = $this->modelFactory->getObject('Ebay_Template_OtherCategory_SnapshotBuilder');

            $oldTemplate = $this->activeRecordFactory->getCachedObjectLoaded(
                'Ebay_Template_OtherCategory',
                $oldData['template_other_category_id']
            );
            $snapshotBuilder->setModel($oldTemplate);

            $oldTemplateSnapshot = $snapshotBuilder->getSnapshot();
            // @codingStandardsIgnoreLine
        } catch (\Exception $exception) {}

        /** @var \Ess\M2ePro\Model\Ebay\Template\OtherCategory\Diff $diff */
        $diff = $this->modelFactory->getObject('Ebay_Template_OtherCategory_Diff');
        $diff->setNewSnapshot($newTemplateSnapshot);
        $diff->setOldSnapshot($oldTemplateSnapshot);

        /** @var \Ess\M2ePro\Model\Ebay\Template\OtherCategory\ChangeProcessor $changeProcessor */
        $changeProcessor = $this->modelFactory->getObject('Ebay_Template_OtherCategory_ChangeProcessor');
        $changeProcessor->process(
            $diff,
            [['id' => $listingProduct->getId(), 'status' => $listingProduct->getStatus()]]
        );
    }

    //########################################
}
