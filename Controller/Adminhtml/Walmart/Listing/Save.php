<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Listing;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Save
 */
class Save extends \Ess\M2ePro\Controller\Adminhtml\Walmart\Listing
{
    protected $dateTime;

    //########################################

    public function __construct(
        \Magento\Framework\Stdlib\DateTime $dateTime,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        $this->dateTime = $dateTime;
        parent::__construct($walmartFactory, $context);
    }

    //########################################

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Ess_M2ePro::walmart_listings_m2epro');
    }

    //########################################

    public function execute()
    {
        if (!$post = $this->getRequest()->getPost()) {
            $this->_redirect('*/walmart_listing/index');
        }

        $id = $this->getRequest()->getParam('id');
        $listing = $this->walmartFactory->getObjectLoaded('Listing', $id, null, false);

        if ($listing === null && $id) {
            $this->getMessageManager()->addError($this->__('Listing does not exist.'));
            return $this->_redirect('*/walmart_listing/index');
        }

        $snapshotBuilder = $this->modelFactory->getObject('Walmart_Listing_SnapshotBuilder');
        $snapshotBuilder->setModel($listing);

        $oldData = $snapshotBuilder->getSnapshot();

        $data = [];
        $keys = [
            'template_selling_format_id',
            'template_description_id',
            'template_synchronization_id',
        ];
        foreach ($keys as $key) {
            if (isset($post[$key])) {
                $data[$key] = $post[$key];
            }
        }

        $listing->addData($data);
        $listing->getChildObject()->addData($data);
        $listing->save();

        $snapshotBuilder = $this->modelFactory->getObject('Walmart_Listing_SnapshotBuilder');
        $snapshotBuilder->setModel($listing);

        $newData = $snapshotBuilder->getSnapshot();

        $affectedListingsProducts = $this->modelFactory->getObject('Walmart_Listing_AffectedListingsProducts');
        $affectedListingsProducts->setModel($listing);

        $affectedListingsProductsData = $affectedListingsProducts->getObjectsData(
            ['id', 'status'],
            ['only_physical_units' => true]
        );

        $this->processDescriptionTemplateChange($oldData, $newData, $affectedListingsProductsData);
        $this->processSellingFormatTemplateChange($oldData, $newData, $affectedListingsProductsData);
        $this->processSynchronizationTemplateChange($oldData, $newData, $affectedListingsProductsData);

        $this->getMessageManager()->addSuccess($this->__('The Listing was saved.'));

        return $this->_redirect($this->getHelper('Data')->getBackUrl('list', [], ['edit'=>['id'=>$id]]));
    }

    //########################################

    protected function processDescriptionTemplateChange(
        array $oldData,
        array $newData,
        array $affectedListingsProductsData
    ) {
        if (empty($affectedListingsProductsData) ||
            empty($oldData['template_description_id']) || empty($newData['template_description_id'])) {
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

    protected function processSellingFormatTemplateChange(
        array $oldData,
        array $newData,
        array $affectedListingsProductsData
    ) {
        if (empty($affectedListingsProductsData) ||
            empty($oldData['template_selling_format_id']) || empty($newData['template_selling_format_id'])) {
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

    protected function processSynchronizationTemplateChange(
        array $oldData,
        array $newData,
        array $affectedListingsProductsData
    ) {
        if (empty($affectedListingsProductsData) ||
            empty($oldData['template_synchronization_id']) || empty($newData['template_synchronization_id'])) {
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

    //########################################
}
