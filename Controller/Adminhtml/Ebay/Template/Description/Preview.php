<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Template\Description;

use Ess\M2ePro\Controller\Adminhtml\Context;
use Ess\M2ePro\Controller\Adminhtml\Ebay\Template;

class Preview extends Template
{
    protected $productModel;

    public function __construct(
        \Magento\Catalog\Model\Product $productModel,
        \Ess\M2ePro\Model\Ebay\Template\Manager $templateManager,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        Context $context
    )
    {
        $this->productModel = $productModel;

        parent::__construct($templateManager, $ebayFactory, $context);
    }

    public function execute()
    {
        // TODO NOT SUPPORTED FEATURES

        if (!(int)$this->getRequest()->getPost('show', 0)) {

            $templateData = $this->getRequest()->getPost('description');
            $this->_getSession()->setTemplateData($templateData);

            return $this->printOutput();
        }

        $productsEntities = $this->getProductsEntities();

        if (!$productsEntities['magento_product']) {

            $errorMessage = $this->__('This Product ID does not exist.');
            return $this->printOutput(NULL, NULL, $errorMessage);
        }

        $title = $productsEntities['magento_product']->getProduct()->getData('name');
        $description = $this->getDescription($productsEntities['magento_product'],
            $productsEntities['listing_product']);

        return $this->printOutput($title, $description);
    }

    //########################################

    private function printOutput($title = NULL, $description = NULL, $errorMessage = NULL)
    {
        $previewFormBlock = $this->createBlock(
            'Ebay\Template\Description\Preview\Form', '',
            array('error_message' => $errorMessage,
                'product_id'    => $this->getRequest()->getPost('id'),
                'store_id'      => $this->getRequest()->getPost('store_id'))
        );

        $previewBodyBlock = $this->createBlock(
            'Ebay\Template\Description\Preview\Body', '',
            array('title'       => $title,
                'description' => $description)
        );

        $this->addContent($previewFormBlock);
        $this->addContent($previewBodyBlock);

        return $this->resultPage;
    }

    private function getDescription(\Ess\M2ePro\Model\Magento\Product $magentoProduct,
                                    \Ess\M2ePro\Model\Listing\Product $listingProduct = NULL)
    {
        $descriptionTemplateData = $this->_getSession()->getTemplateData();

        $descriptionModeProduct = \Ess\M2ePro\Model\Ebay\Template\Description::DESCRIPTION_MODE_PRODUCT;
        $descriptionModeShort   = \Ess\M2ePro\Model\Ebay\Template\Description::DESCRIPTION_MODE_SHORT;
        $descriptionModeCustom  = \Ess\M2ePro\Model\Ebay\Template\Description::DESCRIPTION_MODE_CUSTOM;

        if ($descriptionTemplateData['description_mode'] == $descriptionModeProduct) {
            $description = $magentoProduct->getProduct()->getDescription();
        } elseif ($descriptionTemplateData['description_mode'] == $descriptionModeShort) {
            $description = $magentoProduct->getProduct()->getShortDescription();
        } elseif ($descriptionTemplateData['description_mode'] == $descriptionModeCustom) {
            $description = $descriptionTemplateData['description_template'];
        } else {
            $description = '';
        }

        if (empty($description)) {
            return $description;
        }

        $renderer = $this->getHelper('Module\Renderer\Description');
        $description = $renderer->parseTemplate($description, $magentoProduct);

        if (!is_null($listingProduct)) {

            /** @var \Ess\M2ePro\Model\Ebay\Listing\Product\Description\Renderer $renderer */
            $renderer = $this->modelFactory->getObject('Ebay\Listing\Product\Description\Renderer');
            $renderer->setListingProduct($listingProduct->getChildObject());
            $description = $renderer->parseTemplate($description);
        }

        $this->addWatermarkInfoToDescriptionIfNeed($description);
        return $description;
    }

    private function addWatermarkInfoToDescriptionIfNeed(&$description)
    {
        $descriptionTemplateData = $this->_getSession()->getTemplateData();
        if (!$descriptionTemplateData['watermark_mode'] || strpos($description, 'm2e_watermark') === false) {
            return;
        }

        preg_match_all('/<img [^>]*\bm2e_watermark[^>]*>/i', $description, $tagsArr);

        $count = count($tagsArr[0]);
        for ($i = 0; $i < $count; $i++) {

            $dom = new \DOMDocument();
            $dom->loadHTML($tagsArr[0][$i]);
            $tag = $dom->getElementsByTagName('img')->item(0);

            $newTag = str_replace(' m2e_watermark="1"', '', $tagsArr[0][$i]);
            $newTag = '<div class="description-preview-watermark-info">'.$newTag;

            if ($tag->getAttribute('width') == '' || $tag->getAttribute('width') > 100) {
                $newTag = $newTag.'<p>Watermark will be applied to this picture.</p></div>';
            } else {
                $newTag = $newTag.'<p>Watermark.</p></div>';
            }
            $description = str_replace($tagsArr[0][$i], $newTag, $description);
        }
    }

    // ---------------------------------------

    private function getProductsEntities()
    {
        $productId = $this->getRequest()->getPost('id');
        $storeId   = $this->getRequest()->getPost('store_id', 0);

        if ($productId) {

            return array(
                'magento_product' => $this->getMagentoProductById($productId, $storeId),
                'listing_product' => $this->getListingProductByMagentoProductId($productId, $storeId)
            );
        }

        $listingProduct = $this->getListingProductByRandom($storeId);

        if (!is_null($listingProduct)) {

            return array(
                'magento_product' => $listingProduct->getMagentoProduct(),
                'listing_product' => $listingProduct
            );
        }

        return array(
            'magento_product' => $this->getMagentoProductByRandom($storeId),
            'listing_product' => null
        );
    }

    private function getMagentoProductById($productId, $storeId)
    {
        $product = $this->productModel->load($productId);

        if (is_null($product->getId())) {
            return NULL;
        }

        $magentoProduct = $this->modelFactory->getObject('Magento\Product');
        $magentoProduct->setProductId($product->getId());
        $magentoProduct->setStoreId($storeId);

        return $magentoProduct;
    }

    private function getMagentoProductByRandom($storeId)
    {
        $products = $this->productModel
            ->getCollection()
            ->setPageSize(100)
            ->getItems();

        if (count($products) <= 0) {
            return NULL;
        }

        shuffle($products);
        $product = array_shift($products);

        $magentoProduct = $this->modelFactory->getObject('Magento\Product');
        $magentoProduct->setProductId($product->getId());
        $magentoProduct->setStoreId($storeId);

        return $magentoProduct;
    }

    // ---------------------------------------

    private function getListingProductByMagentoProductId($productId, $storeId)
    {
        $listingProductCollection = $this->ebayFactory->getObject('Listing\Product')
            ->getCollection()
            ->addFieldToFilter('product_id', $productId);

        $listingProductCollection->getSelect()->joinLeft(
            array('ml' => $this->activeRecordFactory->getObject('Listing')->getResource()->getMainTable()),
            '`ml`.`id` = `main_table`.`listing_id`',
            array('store_id')
        );

        $listingProductCollection->addFieldToFilter('store_id', $storeId);
        $listingProduct = $listingProductCollection->getFirstItem();

        if (is_null($listingProduct->getId())) {
            return NULL;
        }

        return $listingProduct;
    }

    private function getListingProductByRandom($storeId)
    {
        $listingProductCollection = $this->ebayFactory->getObject('Listing\Product')
            ->getCollection();

        $listingProductCollection->getSelect()->joinLeft(
            array('ml' => $this->activeRecordFactory->getObject('Listing')->getResource()->getMainTable()),
            '`ml`.`id` = `main_table`.`listing_id`',
            array('store_id')
        );

        $listingProducts = $listingProductCollection
            ->addFieldToFilter('store_id', $storeId)
            ->setPageSize(100)
            ->getItems();

        if (count($listingProducts) <= 0) {
            return NULL;
        }

        shuffle($listingProducts);
        return array_shift($listingProducts);
    }
}