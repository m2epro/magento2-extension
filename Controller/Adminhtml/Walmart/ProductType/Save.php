<?php

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\ProductType;

use Ess\M2ePro\Model\Walmart\ProductType\Builder\AffectedListingsProductsFactory
    as ProductTypeAffectedListingsProductsFactory;

class Save extends \Ess\M2ePro\Controller\Adminhtml\Walmart\AbstractProductType
{
    private \Ess\M2ePro\Model\Walmart\ProductType\Repository $productTypeRepository;
    private \Ess\M2ePro\Helper\Data $dataHelper;
    private \Ess\M2ePro\Model\Walmart\ProductTypeFactory $productTypeFactory;
    private \Ess\M2ePro\Model\Walmart\ProductType\BuilderFactory $productTypeBuilderFactory;
    private \Ess\M2ePro\Model\Walmart\ProductType\Builder\SnapshotBuilderFactory $productTypeSnapshotBuilderFactory;
    private \Ess\M2ePro\Model\Walmart\ProductType\Builder\DiffFactory $productTypeDiffFactory;
    private ProductTypeAffectedListingsProductsFactory $productTypeAffectedProductsFactory;
    private \Ess\M2ePro\Model\Walmart\ProductType\Builder\ChangeProcessorFactory $productTypeChangeProcessorFactory;

    public function __construct(
        \Ess\M2ePro\Model\Walmart\ProductType\Repository $productTypeRepository,
        \Ess\M2ePro\Helper\Data $dataHelper,
        \Ess\M2ePro\Model\Walmart\ProductTypeFactory $productTypeFactory,
        \Ess\M2ePro\Model\Walmart\ProductType\BuilderFactory $productTypeBuilderFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Model\Walmart\ProductType\Builder\SnapshotBuilderFactory $productTypeSnapshotBuilderFactory,
        \Ess\M2ePro\Model\Walmart\ProductType\Builder\DiffFactory $productTypeDiffFactory,
        ProductTypeAffectedListingsProductsFactory $productTypeAffectedProductsFactory,
        \Ess\M2ePro\Model\Walmart\ProductType\Builder\ChangeProcessorFactory $productTypeChangeProcessorFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($walmartFactory, $context);

        $this->dataHelper = $dataHelper;
        $this->productTypeFactory = $productTypeFactory;
        $this->productTypeBuilderFactory = $productTypeBuilderFactory;
        $this->productTypeSnapshotBuilderFactory = $productTypeSnapshotBuilderFactory;
        $this->productTypeDiffFactory = $productTypeDiffFactory;
        $this->productTypeAffectedProductsFactory = $productTypeAffectedProductsFactory;
        $this->productTypeChangeProcessorFactory = $productTypeChangeProcessorFactory;
        $this->productTypeRepository = $productTypeRepository;
    }

    public function execute()
    {
        $post = $this->getRequest()->getPostValue();
        if (empty($post)) {
            if ($this->isAjax()) {
                $this->setJsonContent([
                    'status' => false,
                    'message' => 'Incorrect input',
                ]);

                return $this->getResult();
            }

            $this->_forward('index');

            return;
        }

        $id = !empty($post['general']['id']) ? $post['general']['id'] : null;

        if (!$id) {
            $temp = [];
            $keys = ['marketplace_id', 'nick'];
            foreach ($keys as $key) {
                if (empty($post['general'][$key])) {
                    $message = "Missing required field for Product Type Settings: $key";
                    if ($this->isAjax()) {
                        $this->setJsonContent([
                            'status' => false,
                            'message' => $message,
                        ]);

                        return $this->getResult();
                    }

                    $this->messageManager->addErrorMessage($message);

                    return $this->_redirect('*/walmart_productType/edit');
                }

                $temp[$key] = $post['general'][$key];
            }

            if ($this->isTryingOverrideExistingSettings((int)$temp['marketplace_id'], (string)$temp['nick'])) {
                $message = $this->__(
                    'Product Type Settings were not saved: duplication of Product Type Settings'
                    . ' for marketplace is not allowed.'
                );

                if ($this->isAjax()) {
                    $this->setJsonContent([
                        'status' => false,
                        'message' => $message,
                    ]);
                    return $this->getResult();
                }

                $this->messageManager->addErrorMessage($message);

                return $this->_redirect('*/walmart_productType/index');
            }
        }

        $builder = $this->productTypeBuilderFactory->create();
        $productType = $this->productTypeFactory->createEmpty();

        if ($id) {
            $productType->load($id);
        }

        $oldData = [];
        if ($productType->getId()) {
            $oldData = $this->makeSnapshot($productType);
        }

        $builder->build($productType, $post);
        /** @var \Ess\M2ePro\Model\Walmart\ProductType $productType $productType */
        $productType = $builder->getModel();
        $this->messageManager->addSuccessMessage((string)__('Product Type Settings were saved'));

        $newData = $this->makeSnapshot($productType);

        $diff = $this->productTypeDiffFactory->create();
        $diff->setNewSnapshot($newData);
        $diff->setOldSnapshot($oldData);

        $affectedListingsProducts = $this->productTypeAffectedProductsFactory->create();
        $affectedListingsProducts->setModel($productType);

        $changeProcessor = $this->productTypeChangeProcessorFactory->create();
        $changeProcessor->process(
            $diff,
            $affectedListingsProducts->getObjectsData(['id', 'status'])
        );

        $backUrl = $this->dataHelper->getBackUrl(
            '*/walmart_productType/index',
            [],
            ['edit' => ['id' => $productType->getId()]]
        );

        $editUrl = $this->_url->getUrl(
            '*/*/edit',
            ['id' => $productType->getId()]
        );

        if ($this->isAjax()) {
            $jsonContent = [
                'status' => true,
                'product_type_id' => $productType->getId(),
                'back_url' => $backUrl,
                'edit_url' => $editUrl,
            ];

            $this->setJsonContent($jsonContent);

            return $this->getResult();
        }

        return $this->_redirect($backUrl);
    }

    /**
     * Product type settings must be unique for pair (marketplace_id, nick).
     * This code prevents attempt to create duplicate when user tries to create new product type settings.
     * Situation like this possible when one user starts to create product type, another user creates the same one,
     * and first user saves settings for same (marketplace_id, nick).
     */
    private function isTryingOverrideExistingSettings(
        int $marketplaceId,
        string $nick
    ): bool {
        $productType = $this->productTypeRepository->findByMarketplaceIdAndNick($marketplaceId, $nick);

        return $productType !== null;
    }

    private function makeSnapshot(\Ess\M2ePro\Model\Walmart\ProductType $productType): array
    {
        $snapshotBuilder = $this->productTypeSnapshotBuilderFactory->create($productType);

        return $snapshotBuilder->getSnapshot();
    }
}
