<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Template\ProductType;

use Ess\M2ePro\Model\ResourceModel\Amazon\Template\ProductType\CollectionFactory as ProductTypeCollectionFactory;
use Ess\M2ePro\Model\Amazon\Template\ProductType\AffectedListingsProductsFactory
    as ProductTypeAffectedListingsProductsFactory;

class Save extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Template\ProductType
{
    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;
    /** @var \Ess\M2ePro\Model\Amazon\Template\ProductTypeFactory */
    private $productTypeFactory;
    /** @var \Ess\M2ePro\Model\Amazon\Template\ProductType\BuilderFactory */
    private $productTypeBuilderFactory;
    /** @var \Ess\M2ePro\Model\ResourceModel\Amazon\Template\ProductType\CollectionFactory */
    private $productTypeCollectionFactory;
    /** @var \Ess\M2ePro\Model\Amazon\Template\ProductType\SnapshotBuilderFactory */
    private $productTypeSnapshotBuilderFactory;
    /** @var \Ess\M2ePro\Model\Amazon\Template\ProductType\DiffFactory */
    private $productTypeDiffFactory;
    /** @var \Ess\M2ePro\Model\Amazon\Template\ProductType\AffectedListingsProductsFactory */
    private $productTypeAffectedProductsFactory;
    /** @var \Ess\M2ePro\Model\Amazon\Template\ProductType\ChangeProcessorFactory */
    private $productTypeChangeProcessorFactory;

    /**
     * @param \Ess\M2ePro\Helper\Data $dataHelper
     * @param \Ess\M2ePro\Model\Amazon\Template\ProductTypeFactory $productTypeFactory
     * @param \Ess\M2ePro\Model\Amazon\Template\ProductType\BuilderFactory $productTypeBuilderFactory
     * @param ProductTypeCollectionFactory $productTypeCollectionFactory
     * @param \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory
     * @param \Ess\M2ePro\Model\Amazon\Template\ProductType\SnapshotBuilderFactory $productTypeSnapshotBuilderFactory
     * @param \Ess\M2ePro\Model\Amazon\Template\ProductType\DiffFactory $productTypeDiffFactory
     * @param ProductTypeAffectedListingsProductsFactory $productTypeAffectedProductsFactory
     * @param \Ess\M2ePro\Model\Amazon\Template\ProductType\ChangeProcessorFactory $productTypeChangeProcessorFactory
     * @param \Ess\M2ePro\Controller\Adminhtml\Context $context
     */
    public function __construct(
        \Ess\M2ePro\Helper\Data $dataHelper,
        \Ess\M2ePro\Model\Amazon\Template\ProductTypeFactory $productTypeFactory,
        \Ess\M2ePro\Model\Amazon\Template\ProductType\BuilderFactory $productTypeBuilderFactory,
        ProductTypeCollectionFactory $productTypeCollectionFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Model\Amazon\Template\ProductType\SnapshotBuilderFactory $productTypeSnapshotBuilderFactory,
        \Ess\M2ePro\Model\Amazon\Template\ProductType\DiffFactory $productTypeDiffFactory,
        ProductTypeAffectedListingsProductsFactory $productTypeAffectedProductsFactory,
        \Ess\M2ePro\Model\Amazon\Template\ProductType\ChangeProcessorFactory $productTypeChangeProcessorFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($amazonFactory, $context);
        $this->dataHelper = $dataHelper;
        $this->productTypeFactory = $productTypeFactory;
        $this->productTypeBuilderFactory = $productTypeBuilderFactory;
        $this->productTypeCollectionFactory = $productTypeCollectionFactory;
        $this->productTypeSnapshotBuilderFactory = $productTypeSnapshotBuilderFactory;
        $this->productTypeDiffFactory = $productTypeDiffFactory;
        $this->productTypeAffectedProductsFactory = $productTypeAffectedProductsFactory;
        $this->productTypeChangeProcessorFactory = $productTypeChangeProcessorFactory;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|\Magento\Framework\View\Result\Page
     * @throws \Ess\M2ePro\Model\Exception\Logic|\Magento\Framework\Exception\LocalizedException
     */
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

                    return $this->_redirect('*/amazon_template_productType/edit');
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

                return $this->_redirect('*/amazon_template_productType/index');
            }
        }

        $builder = $this->productTypeBuilderFactory->create();
        $productType = $this->productTypeFactory->create();

        if ($id) {
            $productType->load($id);
        }

        $oldData = [];
        if ($productType->getId()) {
            $oldData = $this->makeSnapshot($productType);
        }

        $builder->build($productType, $post);
        $productType = $builder->getModel();
        $this->messageManager->addSuccessMessage($this->__('Product Type Settings were saved'));

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

        if ($this->isAjax()) {
            $this->setJsonContent([
                'status' => true,
            ]);
            return $this->getResult();
        }

        return $this->_redirect(
            $this->dataHelper->getBackUrl(
                'index',
                [],
                ['edit' => [
                    'id' => $productType->getId()
                ]]
            )
        );
    }

    /**
     * Product type settings must be unique for pair (marketplace_id, nick).
     * This code prevents attempt to create duplicate when user tries to create new product type settings.
     * Situation like this possible when one user starts to create product type, another user creates the same one,
     * and first user saves settings for same (marketplace_id, nick).
     *
     * @param int $marketplaceId
     * @param string $nick
     *
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function isTryingOverrideExistingSettings(
        int $marketplaceId,
        string $nick
    ): bool {
        /** @var \Ess\M2ePro\Model\ResourceModel\Amazon\Template\ProductType\Collection $collection */
        $collection = $this->productTypeCollectionFactory->create()
            ->appendFilterMarketplaceId($marketplaceId)
            ->appendFilterNick($nick);

        /** @var \Ess\M2ePro\Model\Amazon\Template\ProductType $item */
        $item = $collection->getFirstItem();

        return (bool)$item->getId();
    }

    /**
     * @param \Ess\M2ePro\Model\Amazon\Template\ProductType $productType
     *
     * @return array
     */
    private function makeSnapshot(
        \Ess\M2ePro\Model\Amazon\Template\ProductType $productType
    ): array {
        $snapshotBuilder = $this->productTypeSnapshotBuilderFactory->create();
        $snapshotBuilder->setModel($productType);

        return $snapshotBuilder->getSnapshot();
    }
}
