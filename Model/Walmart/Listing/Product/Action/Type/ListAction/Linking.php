<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Listing\Product\Action\Type\ListAction;

use Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory;
use Ess\M2ePro\Model\Walmart\Listing\Product\Variation\Manager\Type\Relation\ParentRelation;

/**
 * Class \Ess\M2ePro\Model\Walmart\Listing\Product\Action\Type\ListAction\Linking
 */
class Linking extends \Ess\M2ePro\Model\AbstractModel
{
    /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
    private $listingProduct = null;

    private $productIdentifiers = [];

    private $sku = null;

    protected $activeRecordFactory;
    protected $walmartFactory;

    //########################################

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

    //########################################

    /**
     * @param \Ess\M2ePro\Model\Listing\Product $listingProduct
     * @return $this
     */
    public function setListingProduct(\Ess\M2ePro\Model\Listing\Product $listingProduct)
    {
        $this->listingProduct = $listingProduct;
        return $this;
    }

    /**
     * @param array $productIdentifiers
     * @return $this
     */
    public function setProductIdentifiers(array $productIdentifiers)
    {
        $this->productIdentifiers = $productIdentifiers;
        return $this;
    }

    /**
     * @param $sku
     * @return $this
     */
    public function setSku($sku)
    {
        $this->sku = $sku;
        return $this;
    }

    //########################################

    /**
     * @return bool
     */
    public function link()
    {
        $this->validate();

        if (!$this->getVariationManager()->isRelationMode()) {
            return $this->linkSimpleOrIndividualProduct();
        }

        if ($this->getVariationManager()->isRelationChildType()) {
            return $this->linkChildProduct();
        }

        return false;
    }

    /**
     * @return \Ess\M2ePro\Model\Walmart\Item
     * @throws \Ess\M2ePro\Model\Exception
     * @throws Exception
     */
    public function createWalmartItem()
    {
        $data = [
            'account_id'     => $this->getListingProduct()->getListing()->getAccountId(),
            'marketplace_id' => $this->getListingProduct()->getListing()->getMarketplaceId(),
            'sku'            => $this->getSku(),
            'product_id'     => $this->getListingProduct()->getProductId(),
            'store_id'       => $this->getListingProduct()->getListing()->getStoreId(),
        ];

        $helper = $this->getHelper('Data');

        if ($this->getVariationManager()->isPhysicalUnit() &&
            $this->getVariationManager()->getTypeModel()->isVariationProductMatched()
        ) {

            /** @var \Ess\M2ePro\Model\Walmart\Listing\Product\Variation\Manager\PhysicalUnit $typeModel */
            $typeModel = $this->getVariationManager()->getTypeModel();
            $data['variation_product_options'] = $helper->jsonEncode($typeModel->getProductOptions());
        }

        if ($this->getVariationManager()->isRelationChildType()) {
            /** @var \Ess\M2ePro\Model\Walmart\Listing\Product\Variation\Manager\Type\Relation\Child $typeModel */
            $typeModel = $this->getVariationManager()->getTypeModel();

            if ($typeModel->isVariationProductMatched()) {
                $data['variation_product_options'] = $helper->jsonEncode($typeModel->getRealProductOptions());
            }
        }

        /** @var \Ess\M2ePro\Model\Walmart\Item $object */
        $object = $this->activeRecordFactory->getObject('Walmart\Item');
        $object->setData($data);
        $object->save();

        return $object;
    }

    //########################################

    private function validate()
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

    //########################################

    private function linkSimpleOrIndividualProduct()
    {
        $data = [
            'sku' => $this->getSku(),
        ];

        $data = array_merge($data, $this->getProductIdentifiers());

        $this->getListingProduct()->addData($data);
        $this->getListingProduct()->save();

        return true;
    }

    private function linkChildProduct()
    {
        $data = [
            'sku' => $this->getSku(),
        ];

        $data = array_merge($data, $this->getProductIdentifiers());

        $this->getListingProduct()->addData($data);
        $this->getListingProduct()->save();

        /** @var \Ess\M2ePro\Model\Walmart\Listing\Product\Variation\Manager\Type\Relation\Child $typeModel */
        $typeModel = $this->getVariationManager()->getTypeModel();

        /** @var ParentRelation $parentTypeModel */
        $parentTypeModel = $typeModel->getParentListingProduct()
            ->getChildObject()
            ->getVariationManager()
            ->getTypeModel();

        $parentTypeModel->getProcessor()->process();

        return true;
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Listing\Product
     */
    private function getListingProduct()
    {
        return $this->listingProduct;
    }

    /**
     * @return \Ess\M2ePro\Model\Walmart\Listing\Product
     */
    private function getWalmartListingProduct()
    {
        return $this->getListingProduct()->getChildObject();
    }

    /**
     * @return \Ess\M2ePro\Model\Walmart\Listing\Product\Variation\Manager
     */
    private function getVariationManager()
    {
        return $this->getWalmartListingProduct()->getVariationManager();
    }

    // ---------------------------------------

    private function getProductIdentifiers()
    {
        return $this->productIdentifiers;
    }

    private function getSku()
    {
        if ($this->sku !== null) {
            return $this->sku;
        }

        return $this->getWalmartListingProduct()->getSku();
    }

    //########################################
}
