<?php

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Listing;

class Save extends \Ess\M2ePro\Controller\Adminhtml\Walmart\Listing
{
    private \Ess\M2ePro\Model\Walmart\Listing\ChangeProcessor $walmartListingChangeProcessor;
    private \Ess\M2ePro\Model\Walmart\Listing\DiffFactory $walmartListingDiffFactory;
    private \Ess\M2ePro\Helper\Url $urlHelper;

    public function __construct(
        \Ess\M2ePro\Model\Walmart\Listing\ChangeProcessor $walmartListingChangeProcessor,
        \Ess\M2ePro\Model\Walmart\Listing\DiffFactory $walmartListingDiffFactory,
        \Ess\M2ePro\Helper\Url $urlHelper,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($walmartFactory, $context);

        $this->walmartListingChangeProcessor = $walmartListingChangeProcessor;
        $this->walmartListingDiffFactory = $walmartListingDiffFactory;
        $this->urlHelper = $urlHelper;
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Ess_M2ePro::walmart_listings_m2epro');
    }

    public function execute()
    {
        if (!$post = $this->getRequest()->getPost()) {
            $this->_redirect('*/walmart_listing/index');
        }

        $id = $this->getRequest()->getParam('id');
        /** @var \Ess\M2ePro\Model\Listing $listing */
        $listing = $this->walmartFactory->getObjectLoaded('Listing', $id, null, false);

        if ($listing === null && $id) {
            $this->getMessageManager()->addErrorMessage(__('Listing does not exist.'));

            return $this->_redirect('*/walmart_listing/index');
        }

        /** @var \Ess\M2ePro\Model\Walmart\Listing\SnapshotBuilder $snapshotBuilder */
        $snapshotBuilder = $this->modelFactory->getObject('Walmart_Listing_SnapshotBuilder');
        $snapshotBuilder->setModel($listing);

        $oldData = $snapshotBuilder->getSnapshot();

        $data = [];
        $keys = [
            \Ess\M2ePro\Model\ResourceModel\Walmart\Listing::COLUMN_TEMPLATE_SELLING_FORMAT_ID,
            \Ess\M2ePro\Model\ResourceModel\Walmart\Listing::COLUMN_TEMPLATE_DESCRIPTION_ID,
            \Ess\M2ePro\Model\ResourceModel\Walmart\Listing::COLUMN_TEMPLATE_SYNCHRONIZATION_ID,
        ];
        foreach ($keys as $key) {
            if (isset($post[$key])) {
                $data[$key] = $post[$key];
            }
        }

        $listing->addData($data);

        /** @var \Ess\M2ePro\Model\Walmart\Listing $walmartListing */
        $walmartListing = $listing->getChildObject();
        $walmartListing->addData($data);

        if (!empty($post['condition_value'])) {
            $walmartListing->installConditionModeRecommendedValue($post['condition_value']);
        } elseif (!empty($post['condition_custom_attribute'])) {
            $walmartListing->installConditionModeCustomAttribute($post['condition_custom_attribute']);
        }

        $listing->save();

        /** @var \Ess\M2ePro\Model\Walmart\Listing\SnapshotBuilder $snapshotBuilder */
        $snapshotBuilder = $this->modelFactory->getObject('Walmart_Listing_SnapshotBuilder');
        $snapshotBuilder->setModel($listing);

        $newData = $snapshotBuilder->getSnapshot();

        /** @var \Ess\M2ePro\Model\Walmart\Listing\AffectedListingsProducts $affectedListingsProducts */
        $affectedListingsProducts = $this->modelFactory->getObject('Walmart_Listing_AffectedListingsProducts');
        $affectedListingsProducts->setModel($listing);

        $affectedListingsProductsData = $affectedListingsProducts->getObjectsData(
            ['id', 'status'],
            ['only_physical_units' => true]
        );

        $this->walmartListingChangeProcessor->process(
            $this->walmartListingDiffFactory->create($newData, $oldData),
            $affectedListingsProductsData
        );

        $this->processDescriptionTemplateChange($oldData, $newData, $affectedListingsProductsData);
        $this->processSellingFormatTemplateChange($oldData, $newData, $affectedListingsProductsData);
        $this->processSynchronizationTemplateChange($oldData, $newData, $affectedListingsProductsData);

        $this->getMessageManager()->addSuccessMessage(__('The Listing was saved.'));

        return $this->_redirect($this->urlHelper->getBackUrl('list', [], ['edit' => ['id' => $id]]));
    }

    private function processDescriptionTemplateChange(
        array $oldData,
        array $newData,
        array $affectedListingsProductsData
    ): void {
        if (
            empty($affectedListingsProductsData) ||
            empty($oldData['template_description_id']) || empty($newData['template_description_id'])
        ) {
            return;
        }

        $oldTemplate = $this->walmartFactory->getObjectLoaded(
            'Template_Description',
            $oldData['template_description_id'],
            null,
            false
        );

        $snapshotBuilder = $this->modelFactory->getObject('Walmart_Template_Description_SnapshotBuilder');
        $snapshotBuilder->setModel($oldTemplate);
        $oldSnapshot = $snapshotBuilder->getSnapshot();

        $newTemplate = $this->walmartFactory->getObjectLoaded(
            'Template_Description',
            $newData['template_description_id'],
            null,
            false
        );

        $snapshotBuilder = $this->modelFactory->getObject('Walmart_Template_Description_SnapshotBuilder');
        $snapshotBuilder->setModel($newTemplate);
        $newSnapshot = $snapshotBuilder->getSnapshot();

        $diff = $this->modelFactory->getObject('Walmart_Template_Description_Diff');
        $diff->setNewSnapshot($newSnapshot);
        $diff->setOldSnapshot($oldSnapshot);

        $changeProcessor = $this->modelFactory->getObject('Walmart_Template_Description_ChangeProcessor');
        $changeProcessor->process($diff, $affectedListingsProductsData);
    }

    private function processSellingFormatTemplateChange(
        array $oldData,
        array $newData,
        array $affectedListingsProductsData
    ): void {
        if (
            empty($affectedListingsProductsData) ||
            empty($oldData['template_selling_format_id']) || empty($newData['template_selling_format_id'])
        ) {
            return;
        }

        $oldTemplate = $this->walmartFactory->getObjectLoaded(
            'Template_SellingFormat',
            $oldData['template_selling_format_id'],
            null,
            false
        );
        $snapshotBuilder = $this->modelFactory->getObject('Walmart_Template_SellingFormat_SnapshotBuilder');
        $snapshotBuilder->setModel($oldTemplate);
        $oldSnapshot = $snapshotBuilder->getSnapshot();

        $newTemplate = $this->walmartFactory->getObjectLoaded(
            'Template_SellingFormat',
            $newData['template_selling_format_id'],
            null,
            false
        );
        $snapshotBuilder = $this->modelFactory->getObject('Walmart_Template_SellingFormat_SnapshotBuilder');
        $snapshotBuilder->setModel($newTemplate);
        $newSnapshot = $snapshotBuilder->getSnapshot();

        $diff = $this->modelFactory->getObject('Walmart_Template_SellingFormat_Diff');
        $diff->setNewSnapshot($newSnapshot);
        $diff->setOldSnapshot($oldSnapshot);

        $changeProcessor = $this->modelFactory->getObject('Walmart_Template_SellingFormat_ChangeProcessor');
        $changeProcessor->process($diff, $affectedListingsProductsData);
    }

    private function processSynchronizationTemplateChange(
        array $oldData,
        array $newData,
        array $affectedListingsProductsData
    ): void {
        if (
            empty($affectedListingsProductsData) ||
            empty($oldData['template_synchronization_id']) || empty($newData['template_synchronization_id'])
        ) {
            return;
        }

        $oldTemplate = $this->walmartFactory->getObjectLoaded(
            'Template_Synchronization',
            $oldData['template_synchronization_id'],
            null,
            false
        );
        $snapshotBuilder = $this->modelFactory->getObject('Walmart_Template_Synchronization_SnapshotBuilder');
        $snapshotBuilder->setModel($oldTemplate);
        $oldSnapshot = $snapshotBuilder->getSnapshot();

        $newTemplate = $this->walmartFactory->getObjectLoaded(
            'Template_Synchronization',
            $newData['template_synchronization_id'],
            null,
            false
        );
        $snapshotBuilder = $this->modelFactory->getObject('Walmart_Template_Synchronization_SnapshotBuilder');
        $snapshotBuilder->setModel($newTemplate);
        $newSnapshot = $snapshotBuilder->getSnapshot();

        $diff = $this->modelFactory->getObject('Walmart_Template_Synchronization_Diff');
        $diff->setNewSnapshot($newSnapshot);
        $diff->setOldSnapshot($oldSnapshot);

        $changeProcessor = $this->modelFactory->getObject('Walmart_Template_Synchronization_ChangeProcessor');
        $changeProcessor->process($diff, $affectedListingsProductsData);
    }
}
