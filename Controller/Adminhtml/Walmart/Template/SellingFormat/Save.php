<?php

declare(strict_types=1);

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Template\SellingFormat;

use Ess\M2ePro\Controller\Adminhtml\Walmart\Template;

class Save extends Template
{
    private \Ess\M2ePro\Helper\Data $dataHelper;
    private \Ess\M2ePro\Model\Walmart\Template\SellingFormat\SnapshotBuilderFactory $snapshotBuilderFactory;
    private \Ess\M2ePro\Model\Walmart\Template\SellingFormat\BuilderFactory $builderFactory;
    private \Ess\M2ePro\Model\Walmart\Template\SellingFormat\DiffFactory $diffFactory;
    private \Ess\M2ePro\Model\Walmart\Template\SellingFormat\AffectedListingsProductsFactory $affectedListingsProductsFactory;
    private \Ess\M2ePro\Model\Walmart\Template\SellingFormat\ChangeProcessorFactory $changeProcessorFactory;
    private \Ess\M2ePro\Model\Template\SellingFormatFactory $sellingFormatFactory;
    private \Ess\M2ePro\Model\Walmart\Template\SellingFormat\Promotion\BuilderFactory $promotionBuilderFactory;
    private \Ess\M2ePro\Model\Walmart\Template\SellingFormat\PromotionFactory $promotionFactory;
    private \Ess\M2ePro\Model\ResourceModel\Walmart\Template\SellingFormat\Promotion\CollectionFactory $promotionCollectionFactory;

    public function __construct(
        \Ess\M2ePro\Model\Walmart\Template\SellingFormat\SnapshotBuilderFactory $snapshotBuilderFactory,
        \Ess\M2ePro\Model\Walmart\Template\SellingFormat\BuilderFactory $builderFactory,
        \Ess\M2ePro\Model\Walmart\Template\SellingFormat\DiffFactory $diffFactory,
        \Ess\M2ePro\Model\Walmart\Template\SellingFormat\AffectedListingsProductsFactory $affectedListingsProductsFactory,
        \Ess\M2ePro\Model\Walmart\Template\SellingFormat\ChangeProcessorFactory $changeProcessorFactory,
        \Ess\M2ePro\Model\Template\SellingFormatFactory $sellingFormatFactory,
        \Ess\M2ePro\Model\Walmart\Template\SellingFormat\Promotion\BuilderFactory $promotionBuilderFactory,
        \Ess\M2ePro\Model\Walmart\Template\SellingFormat\PromotionFactory $promotionFactory,
        \Ess\M2ePro\Model\ResourceModel\Walmart\Template\SellingFormat\Promotion\CollectionFactory $promotionCollectionFactory,
        \Ess\M2ePro\Helper\Data $dataHelper,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($walmartFactory, $context);

        $this->dataHelper = $dataHelper;
        $this->snapshotBuilderFactory = $snapshotBuilderFactory;
        $this->builderFactory = $builderFactory;
        $this->diffFactory = $diffFactory;
        $this->affectedListingsProductsFactory = $affectedListingsProductsFactory;
        $this->changeProcessorFactory = $changeProcessorFactory;
        $this->sellingFormatFactory = $sellingFormatFactory;
        $this->promotionBuilderFactory = $promotionBuilderFactory;
        $this->promotionFactory = $promotionFactory;
        $this->promotionCollectionFactory = $promotionCollectionFactory;
    }

    public function execute()
    {
        $post = $this->getRequest()->getPost();

        if (!$post->count()) {
            $this->_forward('index');

            return;
        }

        $id = $this->getRequest()->getParam('id');

        // Add or update model
        // ---------------------------------------
        $sellingFormatTemplate = $this->sellingFormatFactory->createWithWalmartChildMode();
        if (!empty($id)) {
            $sellingFormatTemplate->load($id);
        }

        $oldData = [];
        if ($sellingFormatTemplate->getId()) {
            $snapshotBuilder = $this->snapshotBuilderFactory->create();
            $snapshotBuilder->setModel($sellingFormatTemplate);
            $oldData = $snapshotBuilder->getSnapshot();
        }

        $this->builderFactory->create()->build($sellingFormatTemplate, $post->toArray());

        $this->updatePromotions($post, $sellingFormatTemplate->getId());

        $snapshotBuilder = $this->snapshotBuilderFactory->create();
        $snapshotBuilder->setModel($sellingFormatTemplate);
        $newData = $snapshotBuilder->getSnapshot();

        $diff = $this->diffFactory->create();
        $diff->setNewSnapshot($newData);
        $diff->setOldSnapshot($oldData);

        $affectedListingsProducts = $this->affectedListingsProductsFactory->create();
        $affectedListingsProducts->setModel($sellingFormatTemplate);

        $changeProcessor = $this->changeProcessorFactory->create();
        $changeProcessor->process(
            $diff,
            $affectedListingsProducts->getObjectsData(['id', 'status'], ['only_physical_units' => true])
        );

        if ($this->isAjax()) {
            $this->setJsonContent([
                'status' => true,
            ]);

            return $this->getResult();
        }

        $id = $sellingFormatTemplate->getId();
        // ---------------------------------------

        $this->messageManager->addSuccess($this->__('Policy was saved'));

        return $this->_redirect(
            $this->dataHelper->getBackUrl('*/walmart_template/index', [], [
                'edit' => [
                    'id' => $id,
                    'wizard' => $this->getRequest()->getParam('wizard'),
                    'close_on_save' => $this->getRequest()->getParam('close_on_save'),
                ],
            ])
        );
    }

    private function updatePromotions($data, $templateId)
    {
        $collection = $this->promotionCollectionFactory->create();
        $collection->addFieldToFilter('template_selling_format_id', ['eq' => (int)$templateId]);

        foreach ($collection->getItems() as $item) {
            $item->delete();
        }

        if (empty($data['promotions'])) {
            return;
        }

        $builder = $this->promotionBuilderFactory->create();

        foreach ($data['promotions'] as $promotionData) {
            $promotionInstance = $this->promotionFactory->create();
            $builder->setTemplateSellingFormatId($templateId);
            $builder->build($promotionInstance, $promotionData);
        }
    }
}
