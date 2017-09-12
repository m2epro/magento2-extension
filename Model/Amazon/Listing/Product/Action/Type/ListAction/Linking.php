<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\ListAction;

use \Ess\M2ePro\Model\Amazon\Listing\Product\Variation\Manager\Type\Relation\ChildRelation;
use \Ess\M2ePro\Model\Amazon\Listing\Product\Variation\Manager\Type\Relation\ParentRelation;

class Linking extends \Ess\M2ePro\Model\AbstractModel
{
    /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
    private $listingProduct = null;

    private $generalId = null;

    private $sku = null;

    private $additionalData = array();

    protected $activeRecordFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    )
    {
        $this->activeRecordFactory = $activeRecordFactory;
        parent::__construct($helperFactory, $modelFactory);
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
     * @param $generalId
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function setGeneralId($generalId)
    {
        if (!$this->getHelper('Component\Amazon')->isASIN($generalId) &&
            !$this->getHelper('Data')->isISBN10($generalId)
        ) {
            throw new \InvalidArgumentException('General ID "'.$generalId.'" is invalid.');
        }

        $this->generalId = $generalId;
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

    /**
     * @param array $data
     * @return bool
     */
    public function setAdditionalData(array $data)
    {
        $this->additionalData = $data;
        return true;
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

        if ($this->getVariationManager()->isRelationParentType()) {
            return $this->linkParentProduct();
        }

        return false;
    }

    /**
     * @return \Ess\M2ePro\Model\Amazon\Item
     * @throws \Ess\M2ePro\Model\Exception
     * @throws \Exception
     */
    public function createAmazonItem()
    {
        $data = array(
            'account_id'     => $this->getListingProduct()->getListing()->getAccountId(),
            'marketplace_id' => $this->getListingProduct()->getListing()->getMarketplaceId(),
            'sku'            => $this->getSku(),
            'product_id'     => $this->getListingProduct()->getProductId(),
            'store_id'       => $this->getListingProduct()->getListing()->getStoreId(),
        );

        if ($this->getVariationManager()->isPhysicalUnit() &&
            $this->getVariationManager()->getTypeModel()->isVariationProductMatched()
        ) {

            /** @var \Ess\M2ePro\Model\Amazon\Listing\Product\Variation\Manager\PhysicalUnit $typeModel */
            $typeModel = $this->getVariationManager()->getTypeModel();
            $data['variation_product_options'] = $this->getHelper('Data')->jsonEncode($typeModel->getProductOptions());
        }

        if ($this->getVariationManager()->isRelationChildType()) {
            $typeModel = $this->getVariationManager()->getTypeModel();

            if ($typeModel->isVariationProductMatched()) {
                $data['variation_product_options'] = $this->getHelper('Data')->jsonEncode(
                    $typeModel->getRealProductOptions()
                );
            }

            if ($typeModel->isVariationChannelMatched()) {
                $data['variation_channel_options'] = $this->getHelper('Data')->jsonEncode(
                    $typeModel->getRealChannelOptions()
                );
            }
        }

        /** @var \Ess\M2ePro\Model\Amazon\Item $object */
        $object = $this->activeRecordFactory->getObject('Amazon\Item');
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

        $generalId = $this->getGeneralId();
        if (empty($generalId)) {
            throw new \InvalidArgumentException('General ID was not set.');
        }

        $sku = $this->getSku();
        if (empty($sku)) {
            throw new \InvalidArgumentException('SKU was not set.');
        }
    }

    //########################################

    private function linkSimpleOrIndividualProduct()
    {
        $this->getListingProduct()->addData(array(
            'status' => \Ess\M2ePro\Model\Listing\Product::STATUS_STOPPED,
        ));
        $this->getAmazonListingProduct()->addData(array(
            'general_id'         => $this->getGeneralId(),
            'is_isbn_general_id' => $this->getHelper('Data')->isISBN($this->getGeneralId()),
            'general_id_owner'   => \Ess\M2ePro\Model\Amazon\Listing\Product::IS_GENERAL_ID_OWNER_NO,
            'sku'                => $this->getSku(),
        ));
        $this->getListingProduct()->save();

        $this->createAmazonItem();

        return true;
    }

    private function linkChildProduct()
    {
        $this->getListingProduct()->addData(array(
            'status' => \Ess\M2ePro\Model\Listing\Product::STATUS_STOPPED
        ));
        $this->getAmazonListingProduct()->addData(array(
            'general_id'         => $this->getGeneralId(),
            'is_isbn_general_id' => $this->getHelper('Data')->isISBN($this->getGeneralId()),
            'sku'                => $this->getSku(),
        ));

        /** @var ChildRelation $typeModel */
        $typeModel = $this->getVariationManager()->getTypeModel();

        /** @var ParentRelation $parentTypeModel */
        $parentTypeModel = $typeModel->getParentListingProduct()
            ->getChildObject()
            ->getVariationManager()
            ->getTypeModel();

        $parentVariations = $parentTypeModel->getChannelVariations();
        if (!isset($parentVariations[$this->generalId])) {
            return false;
        }

        $typeModel->setChannelVariation($parentVariations[$this->generalId]);

        $this->createAmazonItem();

        $this->getListingProduct()->save();

        $parentTypeModel->getProcessor()->process();

        return true;
    }

    private function linkParentProduct()
    {
        $data = $this->getAdditionalData();
        if (empty($data['parentage']) || $data['parentage'] != 'parent' || !empty($data['bad_parent'])) {
            return false;
        }

        $dataForUpdate = array(
            'general_id'         => $this->getGeneralId(),
            'is_isbn_general_id' => $this->getHelper('Data')->isISBN($this->getGeneralId()),
            'sku'                => $this->getSku(),
        );

        $descriptionTemplate = $this->getAmazonListingProduct()->getAmazonDescriptionTemplate();
        $listingProductSku = $this->getAmazonListingProduct()->getSku();

        // improve check is sku existence
        if (empty($listingProductSku) && !empty($descriptionTemplate) && $descriptionTemplate->isNewAsinAccepted()) {
            $dataForUpdate['general_id_owner'] = \Ess\M2ePro\Model\Amazon\Listing\Product::IS_GENERAL_ID_OWNER_YES;
        } else {
            $dataForUpdate['general_id_owner'] = \Ess\M2ePro\Model\Amazon\Listing\Product::IS_GENERAL_ID_OWNER_NO;
        }

        $this->getAmazonListingProduct()->addData($dataForUpdate);

        /** @var ParentRelation $typeModel */
        $typeModel = $this->getVariationManager()->getTypeModel();

        $typeModel->setChannelAttributesSets($data['variations']['set'], false);

        $channelVariations = array();
        foreach ($data['variations']['asins'] as $generalId => $options) {
            $channelVariations[$generalId] = $options['specifics'];
        }
        $typeModel->setChannelVariations($channelVariations, false);

        $this->getListingProduct()->save();

        $typeModel->getProcessor()->process();

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
     * @return \Ess\M2ePro\Model\Amazon\Listing\Product
     */
    private function getAmazonListingProduct()
    {
        return $this->getListingProduct()->getChildObject();
    }

    /**
     * @return \Ess\M2ePro\Model\Amazon\Listing\Product\Variation\Manager
     */
    private function getVariationManager()
    {
        return $this->getAmazonListingProduct()->getVariationManager();
    }

    // ---------------------------------------

    private function getGeneralId()
    {
        return $this->generalId;
    }

    private function getSku()
    {
        if (!is_null($this->sku)) {
            return $this->sku;
        }

        return $this->getAmazonListingProduct()->getSku();
    }

    private function getAdditionalData()
    {
        if (!empty($this->additionalData)) {
            return $this->additionalData;
        }

        return $this->additionalData = $this->getDataFromAmazon();
    }

    //########################################

    private function getDataFromAmazon()
    {
        $params = array(
            'item' => $this->generalId,
            'variation_child_modification' => 'none',
        );

        $dispatcherObject = $this->modelFactory->getObject('Amazon\Connector\Dispatcher');
        $connectorObj = $dispatcherObject->getVirtualConnector('product', 'search', 'byAsin',
                                                               $params, 'item',
                                                               $this->getListingProduct()->getListing()->getAccount());

        $dispatcherObject->process($connectorObj);

        return $connectorObj->getResponseData();
    }

    //########################################
}