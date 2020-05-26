<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Template;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Template\SaveListingProductsPolicy
 */
class SaveListingProductsPolicy extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Template
{
    /** @var \Magento\Framework\DB\TransactionFactory */
    protected $transactionFactory = null;

    //########################################

    public function __construct(
        \Magento\Framework\DB\TransactionFactory $transactionFactory,
        \Ess\M2ePro\Model\Ebay\Template\Manager $templateManager,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        $this->transactionFactory = $transactionFactory;
        parent::__construct($templateManager, $ebayFactory, $context);
    }

    //########################################

    public function execute()
    {
        $ids = $this->getRequestIds();

        if (!$post = $this->getRequest()->getPostValue() || empty($ids)) {
            $this->setAjaxContent('', false);
            return $this->getResult();
        }

        // ---------------------------------------
        $collection = $this->ebayFactory->getObject('Listing\Product')->getCollection();
        $collection->addFieldToFilter('id', ['in' => $ids]);
        // ---------------------------------------

        if ($collection->getSize() == 0) {
            $this->setAjaxContent('', false);
            return $this->getResult();
        }

        // ---------------------------------------
        $data = $this->getPostedTemplatesData();
        // ---------------------------------------

        // ---------------------------------------
        $transaction = $this->transactionFactory->create();

        $snapshots = [];

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
        }

        // ---------------------------------------

        if ($snapshots) {

            $templateManager = $this->templateManager;

            foreach ($collection->getItems() as $listingProduct) {
                /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
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
                            'Ebay_' . $templateManager->getTemplateModelName() . '_SnapshotBuilder'
                        );
                    } else {
                        $snapshotBuilder = $this->modelFactory->getObject(
                            $templateManager->getTemplateModelName() . '_SnapshotBuilder'
                        );
                    }

                    $snapshotBuilder->setModel($newTemplates[$template]);

                    $newTemplateData = $snapshotBuilder->getSnapshot();

                    /** @var \Ess\M2ePro\Model\Template\SnapshotBuilder\AbstractModel $snapshotBuilder */
                    if ($templateManager->isHorizontalTemplate()) {
                        $snapshotBuilder = $this->modelFactory->getObject(
                            'Ebay_' . $templateManager->getTemplateModelName() . '_SnapshotBuilder'
                        );
                    } else {
                        $snapshotBuilder = $this->modelFactory->getObject(
                            $templateManager->getTemplateModelName() . '_SnapshotBuilder'
                        );
                    }

                    $snapshotBuilder->setModel($oldTemplates[$template]);

                    $oldTemplateData = $snapshotBuilder->getSnapshot();

                    /** @var \Ess\M2ePro\Model\Template\Diff\AbstractModel $diff */
                    if ($templateManager->isHorizontalTemplate()) {
                        $diff = $this->modelFactory->getObject(
                            'Ebay_' . $templateManager->getTemplateModelName() . '_Diff'
                        );
                    } else {
                        $diff = $this->modelFactory->getObject($templateManager->getTemplateModelName() . '_Diff');
                    }

                    $diff->setNewSnapshot($newTemplateData);
                    $diff->setOldSnapshot($oldTemplateData);

                    /** @var \Ess\M2ePro\Model\Template\ChangeProcessor\AbstractModel $changeProcessor */
                    if ($templateManager->isHorizontalTemplate()) {
                        $changeProcessor = $this->modelFactory->getObject(
                            'Ebay_' . $templateManager->getTemplateModelName() . '_ChangeProcessor'
                        );
                    } else {
                        $changeProcessor = $this->modelFactory->getObject(
                            $templateManager->getTemplateModelName() . '_ChangeProcessor'
                        );
                    }

                    $changeProcessor->process(
                        $diff,
                        [['id' => $listingProduct->getId(), 'status' => $listingProduct->getStatus()]]
                    );
                }

                $this->processCategoryTemplateChange(
                    $listingProduct,
                    $newData,
                    $snapshots[$listingProduct->getId()]
                );

                $this->processOtherCategoryTemplateChange(
                    $listingProduct,
                    $newData,
                    $snapshots[$listingProduct->getId()]
                );
            }
        }

        $this->setAjaxContent('', false);
        return $this->getResult();
    }

    //########################################

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

    private function getPostedTemplatesData()
    {
        if (!$post = $this->getRequest()->getPostValue()) {
            return [];
        }

        // ---------------------------------------
        $data = [];
        foreach ($this->templateManager->getAllTemplates() as $nick) {
            $manager = $this->templateManager->setTemplate($nick);

            if (!isset($post["template_{$nick}"])) {
                continue;
            }

            // @codingStandardsIgnoreLine
            $templateData = $this->getHelper('Data')->jsonDecode(base64_decode($post["template_{$nick}"]));

            $templateId = $templateData['id'];
            $templateMode = $templateData['mode'];

            $idColumn = $manager->getIdColumnNameByMode($templateMode);
            $modeColumn = $manager->getModeColumnName();

            if ($idColumn !== null) {
                $data[$idColumn] = (int)$templateId;
            }

            $data[$modeColumn] = $templateMode;

            $this->clearTemplatesFieldsNotRelatedToMode($data, $nick, $templateMode);
        }
        // ---------------------------------------

        return $data;
    }

    // ---------------------------------------

    private function clearTemplatesFieldsNotRelatedToMode(array &$data, $nick, $mode)
    {
        $modes = [
            \Ess\M2ePro\Model\Ebay\Template\Manager::MODE_PARENT,
            \Ess\M2ePro\Model\Ebay\Template\Manager::MODE_CUSTOM,
            \Ess\M2ePro\Model\Ebay\Template\Manager::MODE_TEMPLATE
        ];

        unset($modes[array_search($mode, $modes)]);

        foreach ($modes as $mode) {
            $column = $this->templateManager->setTemplate($nick)->getIdColumnNameByMode($mode);

            if ($column === null) {
                continue;
            }

            $data[$column] = null;
        }
    }

    //########################################
}
