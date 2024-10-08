<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Listing\Wizard;

use Ess\M2ePro\Helper\Factory as HelperFactory;
use Ess\M2ePro\Model\ActiveRecord\AbstractModel;
use Ess\M2ePro\Model\ActiveRecord\Factory as ActiveRecordFactory;
use Ess\M2ePro\Model\Ebay\Listing\Wizard\Repository as WizardRepository;
use Ess\M2ePro\Model\Factory;
use Ess\M2ePro\Model\Ebay\Listing\Wizard as WizardModel;
use Ess\M2ePro\Model\Magento\Product\Cache;
use Ess\M2ePro\Model\Magento\Product\CacheFactory;
use Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Wizard\Product as WizardProductResource;
use Ess\M2ePro\Helper\Json as JsonHelper;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;

class Product extends AbstractModel
{
    public const SEARCH_STATUS_NONE = 0;
    public const SEARCH_STATUS_COMPLETED = 1;

    protected ?Cache $magentoProductModel = null;
    private CacheFactory $magentoProductFactory;
    private WizardRepository $wizardRepository;
    private WizardModel $wizard;

    public function __construct(
        CacheFactory $magentoProductFactory,
        WizardRepository $wizardRepository,
        Context $context,
        Registry $registry,
        Factory $modelFactory,
        ActiveRecordFactory $activeRecordFactory,
        HelperFactory $helperFactory,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $modelFactory,
            $activeRecordFactory,
            $helperFactory,
            $context,
            $registry,
            $resource,
            $resourceCollection,
            $data
        );
        $this->magentoProductFactory = $magentoProductFactory;
        $this->wizardRepository = $wizardRepository;
    }
    public function _construct(): void
    {
        parent::_construct();

        $this->_init(WizardProductResource::class);
    }

    public function init(WizardModel $wizard, int $magentoProductId): self
    {
        $this
            ->setData(WizardProductResource::COLUMN_WIZARD_ID, $wizard->getId())
            ->setData(WizardProductResource::COLUMN_MAGENTO_PRODUCT_ID, $magentoProductId);

        return $this;
    }

    public function initWizard(WizardModel $wizard): void
    {
        $this->wizard = $wizard;
    }

    public function getId(): int
    {
        return (int)parent::getId();
    }

    public function getWizard(): WizardModel
    {
        if (!isset($this->wizard)) {
            $this->wizard = $this->wizardRepository->get($this->getWizardId());
        }

        return $this->wizard;
    }

    public function getWizardId(): int
    {
        return (int)$this->getData(WizardProductResource::COLUMN_WIZARD_ID);
    }

    public function getMagentoProductId(): int
    {
        return (int)$this->getData(WizardProductResource::COLUMN_MAGENTO_PRODUCT_ID);
    }

    public function getEbayItemId(): string
    {
        return (string)$this->getData(WizardProductResource::COLUMN_EBAY_ITEM_ID);
    }

    public function setEbayItemId(string $productId): self
    {
        $this->setData(WizardProductResource::COLUMN_EBAY_ITEM_ID, $productId);

        return $this;
    }

    public function getValidationStatus(): int
    {
        return (int)$this->getData(WizardProductResource::COLUMN_VALIDATION_STATUS);
    }

    public function setValidationStatus(int $productId): self
    {
        $this->setData(WizardProductResource::COLUMN_VALIDATION_STATUS, $productId);

        return $this;
    }

    public function addErrorMessage(array $errorMessage): self
    {
        $errorMessages = $this->getValidationErrors();
        $errorMessages[] = $errorMessage;

        return $this->setValidationErrors($errorMessages);
    }

    public function getValidationErrors(): array
    {
        return $this->getData(WizardProductResource::COLUMN_VALIDATION_ERRORS) ?
            JsonHelper::decode($this->getData(WizardProductResource::COLUMN_VALIDATION_ERRORS)) : [];
    }

    public function setValidationErrors(array $errors): self
    {
        $this->setData(WizardProductResource::COLUMN_VALIDATION_ERRORS, JsonHelper::encode($errors));

        return $this;
    }

    public function getUnmanagedProductId(): ?int
    {
        $value = $this->getData(WizardProductResource::COLUMN_UNMANAGED_PRODUCT_ID);

        if ($value === null) {
            return null;
        }

        return (int)$value;
    }

    public function setUnmanagedProductId(int $value): self
    {
        $this->setData(WizardProductResource::COLUMN_UNMANAGED_PRODUCT_ID, $value);

        return $this;
    }

    public function setTemplateCategoryId($value): self
    {
        $this->setData(WizardProductResource::COLUMN_TEMPLATE_CATEGORY_ID, $value);

        return $this;
    }

    public function setTemplateCategorySecondaryId($value): self
    {
        $this->setData(WizardProductResource::COLUMN_TEMPLATE_CATEGORY_SECONDARY_ID, $value);

        return $this;
    }

    public function setStoreCategoryId($value): self
    {
        $this->setData(WizardProductResource::COLUMN_STORE_CATEGORY_ID, $value);

        return $this;
    }

    public function setStoreCategorySecondaryId($value): self
    {
        $this->setData(WizardProductResource::COLUMN_STORE_CATEGORY_SECONDARY_ID, $value);

        return $this;
    }

    public function getTemplateCategoryId()
    {
        $value = $this->getData(WizardProductResource::COLUMN_TEMPLATE_CATEGORY_ID);
        if ($value === null) {
            return null;
        }

        return (int)$value;
    }

    public function getTemplateCategorySecondaryId()
    {
        $value = $this->getData(WizardProductResource::COLUMN_TEMPLATE_CATEGORY_SECONDARY_ID);
        if ($value === null) {
            return null;
        }

        return $value;
    }

    public function getStoreCategoryId()
    {
        $value = $this->getData(WizardProductResource::COLUMN_STORE_CATEGORY_ID);
        if ($value === null) {
            return null;
        }

        return (int)$value;
    }

    public function getStoreCategorySecondaryId()
    {
        $value = $this->getData(WizardProductResource::COLUMN_STORE_CATEGORY_SECONDARY_ID);
        if ($value === null) {
            return null;
        }

        return $value;
    }

    public function isProcessed(): bool
    {
        return (bool)$this->getData(WizardProductResource::COLUMN_IS_PROCESSED);
    }

    public function processed(): self
    {
        $this->setData(WizardProductResource::COLUMN_IS_PROCESSED, 1);

        return $this;
    }

    /**
     * @return Cache
     */
    public function getMagentoProduct(): Cache
    {
        if ($this->magentoProductModel === null) {
            $this->magentoProductModel = $this->magentoProductFactory->create();
            $this->magentoProductModel->setProductId($this->getMagentoProductId());
        }

        return $this->prepareMagentoProduct($this->magentoProductModel);
    }

    protected function prepareMagentoProduct(
        Cache $instance
    ): Cache {
        $instance->setStoreId($this->getWizard()->getListing()->getStoreId());
        $instance->setStatisticId($this->getId());

        return $instance;
    }
}
