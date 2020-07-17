<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Template\AffectedListingsProducts;

/**
 * Class \Ess\M2ePro\Model\Ebay\Template\AffectedListingsProducts\Processor
 */
class Processor extends \Ess\M2ePro\Model\AbstractModel
{
    /** @var \Ess\M2ePro\Model\Listing\Product */
    protected $listingProduct;

    protected $ebayFactory;
    protected $activeRecordFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $data = []
    ) {
        $this->ebayFactory = $ebayFactory;
        $this->activeRecordFactory = $activeRecordFactory;
        parent::__construct($helperFactory, $modelFactory, $data);
    }

    //########################################

    public function processChanges(array $newData, array $oldData)
    {
        $this->templateManagerTemplatesChange($newData, $oldData);

        $this->categoryTemplatesChange(
            $newData,
            $oldData,
            'Ebay_Template_Category',
            'template_category_id'
        );
        $this->categoryTemplatesChange(
            $newData,
            $oldData,
            'Ebay_Template_Category',
            'template_category_secondary_id'
        );
        $this->categoryTemplatesChange(
            $newData,
            $oldData,
            'Ebay_Template_StoreCategory',
            'template_store_category_id'
        );
        $this->categoryTemplatesChange(
            $newData,
            $oldData,
            'Ebay_Template_StoreCategory',
            'template_store_category_secondary_id'
        );
    }

    //########################################

    public function templateManagerTemplatesChange(
        array $newData,
        array $oldData
    ) {
        /** @var \Ess\M2ePro\Model\Ebay\Template\Manager $manager */
        $templateManager = $this->modelFactory->getObject('Ebay_Template_Manager');

        $newTemplates = $templateManager->getTemplatesFromData($newData);
        $oldTemplates = $templateManager->getTemplatesFromData($oldData);

        foreach ($templateManager->getAllTemplates() as $template) {
            $templateManager->setTemplate($template);

            /** @var \Ess\M2ePro\Model\ActiveRecord\SnapshotBuilder $snapshotBuilder */
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

            /** @var \Ess\M2ePro\Model\ActiveRecord\SnapshotBuilder $snapshotBuilder */
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

            /** @var \Ess\M2ePro\Model\ActiveRecord\Diff $diff */
            if ($templateManager->isHorizontalTemplate()) {
                $diff = $this->modelFactory->getObject('Ebay_'.$templateManager->getTemplateModelName().'_Diff');
            } else {
                $diff = $this->modelFactory->getObject($templateManager->getTemplateModelName().'_Diff');
            }

            $diff->setNewSnapshot($newTemplateData);
            $diff->setOldSnapshot($oldTemplateData);

            /** @var \Ess\M2ePro\Model\Template\ChangeProcessor\ChangeProcessorAbstract $changeProcessor */
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
                [
                    [
                        'id'     => $this->listingProduct->getId(),
                        'status' => $this->listingProduct->getStatus()
                    ]
                ]
            );
        }
    }

    public function categoryTemplatesChange(
        array $newData,
        array $oldData,
        $templateModel,
        $templateIdField
    ) {
        $newTemplateSnapshot = [];

        try {
            if (!empty($newData[$templateIdField])) {
                $newTemplate = $this->activeRecordFactory->getCachedObjectLoaded(
                    $templateModel,
                    $newData[$templateIdField],
                    null,
                    ['template']
                );

                /** @var \Ess\M2ePro\Model\ActiveRecord\SnapshotBuilder $snapshotBuilder */
                $snapshotBuilder = $this->modelFactory->getObject($templateModel.'_SnapshotBuilder');
                $snapshotBuilder->setModel($newTemplate);

                $newTemplateSnapshot = $snapshotBuilder->getSnapshot();
            }
        } catch (\Exception $exception) {
            $this->getHelper('Module_Exception')->process($exception);
        }

        $oldTemplateSnapshot = [];

        try {
            if (!empty($oldData[$templateIdField])) {
                $oldTemplate = $this->activeRecordFactory->getCachedObjectLoaded(
                    $templateModel,
                    $oldData[$templateIdField],
                    null,
                    ['template']
                );

                /** @var \Ess\M2ePro\Model\ActiveRecord\SnapshotBuilder $snapshotBuilder */
                $snapshotBuilder = $this->modelFactory->getObject($templateModel.'_SnapshotBuilder');
                $snapshotBuilder->setModel($oldTemplate);

                $oldTemplateSnapshot = $snapshotBuilder->getSnapshot();
            }
        } catch (\Exception $exception) {
            $this->getHelper('Module_Exception')->process($exception);
        }

        /** @var \Ess\M2ePro\Model\ActiveRecord\Diff $diff */
        $diff = $this->modelFactory->getObject($templateModel.'_Diff');
        $diff->setNewSnapshot($newTemplateSnapshot);
        $diff->setOldSnapshot($oldTemplateSnapshot);

        /** @var \Ess\M2ePro\Model\Template\ChangeProcessor\ChangeProcessorAbstract $changeProcessor */
        $changeProcessor = $this->modelFactory->getObject($templateModel.'_ChangeProcessor');
        $changeProcessor->process(
            $diff,
            [
                [
                    'id'     => $this->listingProduct->getId(),
                    'status' => $this->listingProduct->getStatus()
                ]
            ]
        );
    }

    //########################################

    /**
     * @param \Ess\M2ePro\Model\Listing\Product $listingProduct
     */
    public function setListingProduct($listingProduct)
    {
        $this->listingProduct = $listingProduct;
    }

    //########################################
}
