<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Listing\Product\Action\Type\ListAction;

use Ess\M2ePro\Model\Walmart\Listing\Product\Variation\Manager\Type\Relation\ParentRelation;

class Linking extends \Ess\M2ePro\Model\AbstractModel
{
    /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
    private $listingProduct = null;

    private $productIdentifiers = [];

    private $sku = null;

    protected $activeRecordFactory;
    protected $walmartFactory;

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $data = []
    ) {
        $this->activeRecordFactory = $activeRecordFactory;
        $this->walmartFactory = $walmartFactory;
        parent::__construct($helperFactory, $modelFactory, $data);
    }

    /**
     * @param \Ess\M2ePro\Model\Listing\Product $listingProduct
     *
     * @return $this
     */
    public function setListingProduct(\Ess\M2ePro\Model\Listing\Product $listingProduct): Linking
    {
        $this->listingProduct = $listingProduct;

        return $this;
    }

    /**
     * @param array $productIdentifiers
     *
     * @return $this
     */
    public function setProductIdentifiers(array $productIdentifiers): Linking
    {
        $this->productIdentifiers = $productIdentifiers;

        return $this;
    }

    /**
     * @param $sku
     *
     * @return $this
     */
    public function setSku($sku): Linking
    {
        $this->sku = $sku;

        return $this;
    }

    /**
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function link(): bool
    {
        $this->validate();

        if (!$this->getVariationManager()->isRelationMode()) {
            $this->linkSimpleOrIndividualProduct();

            return true;
        }

        if ($this->getVariationManager()->isRelationChildType()) {
            $this->linkChildProduct();

            return true;
        }

        return false;
    }

    /**
     * @return \Ess\M2ePro\Model\Walmart\Item
     * @throws \Ess\M2ePro\Model\Exception
     * @throws \Exception
     */
    public function createWalmartItem(): \Ess\M2ePro\Model\Walmart\Item
    {
        $data = [
            'account_id' => $this->getListingProduct()->getListing()->getAccountId(),
            'marketplace_id' => $this->getListingProduct()->getListing()->getMarketplaceId(),
            'sku' => $this->getSku(),
            'product_id' => $this->getListingProduct()->getProductId(),
            'store_id' => $this->getListingProduct()->getListing()->getStoreId(),
        ];

        if (
            $this->getVariationManager()->isPhysicalUnit() &&
            $this->getVariationManager()->getTypeModel()->isVariationProductMatched()
        ) {
            /** @var \Ess\M2ePro\Model\Walmart\Listing\Product\Variation\Manager\PhysicalUnit $typeModel */
            $typeModel = $this->getVariationManager()->getTypeModel();
            $data['variation_product_options'] = \Ess\M2ePro\Helper\Json::encode($typeModel->getProductOptions());
        }

        if ($this->getVariationManager()->isRelationChildType()) {
            /** @var \Ess\M2ePro\Model\Walmart\Listing\Product\Variation\Manager\Type\Relation\Child $typeModel */
            $typeModel = $this->getVariationManager()->getTypeModel();

            if ($typeModel->isVariationProductMatched()) {
                $data['variation_product_options'] = \Ess\M2ePro\Helper\Json::encode(
                    $typeModel->getRealProductOptions()
                );
            }
        }

        if ($this->getListingProduct()->getMagentoProduct()->isGroupedType()) {
            $additionalData = $this->getListingProduct()->getAdditionalData();
            $data['additional_data'] = \Ess\M2ePro\Helper\Json::encode([
                'grouped_product_mode' => $additionalData['grouped_product_mode'],
            ]);
        }

        /** @var \Ess\M2ePro\Model\Walmart\Item $object */
        $object = $this->activeRecordFactory->getObject('Walmart\Item');
        $object->setData($data);
        $object->save();

        return $object;
    }

    /**
     * @return void
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function validate(): void
    {
        $listingProduct = $this->getListingProduct();
        if (empty($listingProduct)) {
            throw new \InvalidArgumentException('Listing Product was not set.');
        }

        $generalId = $this->getProductIdentifiers();
        if (empty($generalId)) {
            throw new \InvalidArgumentException('Product identifiers were not set.');
        }

        $sku = $this->getSku();
        if (empty($sku)) {
            throw new \InvalidArgumentException('SKU was not set.');
        }
    }

    /**
     * @return void
     * @throws \Ess\M2ePro\Model\Exception
     */
    private function linkSimpleOrIndividualProduct(): void
    {
        $this->getListingProduct()->addData([
            'status' => \Ess\M2ePro\Model\Listing\Product::STATUS_STOPPED,
        ]);

        $productIdentifiers = $this->getProductIdentifiers();

        $this->getWalmartListingProduct()->addData([
            'wpid' => $productIdentifiers['wpid'],
            'item_id' => $productIdentifiers['item_id'],
            'gtin' => $productIdentifiers['gtin'],
            'upc' => $productIdentifiers['upc'] ?? null,
            'ean' => $productIdentifiers['ean'] ?? null,
            'isbn' => $productIdentifiers['isbn'] ?? null,
            'sku' => $this->getSku(),
        ]);

        $this->getListingProduct()->save();

        $this->createWalmartItem();
    }

    /**
     * @return void
     * @throws \Ess\M2ePro\Model\Exception
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function linkChildProduct(): void
    {
        $this->linkSimpleOrIndividualProduct();

        /** @var \Ess\M2ePro\Model\Walmart\Listing\Product\Variation\Manager\Type\Relation\Child $typeModel */
        $typeModel = $this->getVariationManager()->getTypeModel();

        /** @var ParentRelation $parentTypeModel */
        $parentTypeModel = $typeModel->getParentListingProduct()
                                     ->getChildObject()
                                     ->getVariationManager()
                                     ->getTypeModel();

        $parentTypeModel->getProcessor()->process();
    }

    /**
     * @return \Ess\M2ePro\Model\Listing\Product|null
     */
    private function getListingProduct(): ?\Ess\M2ePro\Model\Listing\Product
    {
        return $this->listingProduct;
    }

    /**
     * @return \Ess\M2ePro\Model\Walmart\Listing\Product
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function getWalmartListingProduct(): \Ess\M2ePro\Model\Walmart\Listing\Product
    {
        return $this->getListingProduct()->getChildObject();
    }

    /**
     * @return \Ess\M2ePro\Model\Walmart\Listing\Product\Variation\Manager
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function getVariationManager(): \Ess\M2ePro\Model\Walmart\Listing\Product\Variation\Manager
    {
        return $this->getWalmartListingProduct()->getVariationManager();
    }

    /**
     * @return array
     */
    private function getProductIdentifiers(): array
    {
        return $this->productIdentifiers;
    }

    /**
     * @return string|null
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function getSku(): ?string
    {
        return $this->sku ?? $this->getWalmartListingProduct()->getSku();
    }
}
