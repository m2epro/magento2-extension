<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Template\Description;

use Ess\M2ePro\Controller\Adminhtml\Ebay\Template\Description;

class Preview extends Description
{
    private $description = [];

    protected function getLayoutType()
    {
        return self::LAYOUT_BLANK;
    }

    public function execute()
    {
        $this->description = $this->getRequest()->getPost('description_preview', []);

        if (empty($this->description)) {
            $this->messageManager->addError($this->__('Description Policy data is not specified.'));
            return $this->getResult();
        }

        $productsEntities = $this->getProductsEntities();

        if (is_null($productsEntities['magento_product'])) {
            $this->messageManager->addError($this->__('Magento Product does not exist.'));
            return $this->getResult();
        }

        $description = $this->getDescription(
            $productsEntities['magento_product'],
            $productsEntities['listing_product']
        );

        if (!$description) {
            $this->messageManager->addWarning(
                $this->__(
                    'The Product Description attribute is selected as a source of the eBay Item Description,
                    but this Product has empty description.'
                )
            );
        } elseif (is_null($productsEntities['listing_product'])) {
            $this->messageManager->addWarning(
                $this->__(
                    'The Product you selected is not presented in any M2E Pro Listing.
                    Thus, the values of the M2E Pro Attribute(s), which are used in the Item Description,
                    will be ignored and displayed like #attribute label#.
                    Please, change the Product ID to preview the data.'
                )
            );
        }

        $previewBlock = $this->createBlock('Ebay\Template\Description\Preview')->setData([
            'title' => $productsEntities['magento_product']->getProduct()->getData('name'),
            'magento_product_id' => $productsEntities['magento_product']->getProductId(),
            'description' => $description
        ]);

        $this->getResultPage()->getConfig()->getTitle()->prepend($this->__('Preview Description'));
        $this->addContent($previewBlock);

        return $this->getResult();
    }

    //########################################

    private function getDescription(\Ess\M2ePro\Model\Magento\Product $magentoProduct,
                                    \Ess\M2ePro\Model\Listing\Product $listingProduct = NULL)
    {
        $descriptionModeProduct = \Ess\M2ePro\Model\Ebay\Template\Description::DESCRIPTION_MODE_PRODUCT;
        $descriptionModeShort   = \Ess\M2ePro\Model\Ebay\Template\Description::DESCRIPTION_MODE_SHORT;
        $descriptionModeCustom  = \Ess\M2ePro\Model\Ebay\Template\Description::DESCRIPTION_MODE_CUSTOM;

        if ($this->description['description_mode'] == $descriptionModeProduct) {
            $description = $magentoProduct->getProduct()->getDescription();
        } elseif ($this->description['description_mode'] == $descriptionModeShort) {
            $description = $magentoProduct->getProduct()->getShortDescription();
        } elseif ($this->description['description_mode'] == $descriptionModeCustom) {
            $description = $this->description['description_template'];
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
            $renderer->setRenderMode(\Ess\M2ePro\Model\Ebay\Listing\Product\Description\Renderer::MODE_PREVIEW);
            $renderer->setListingProduct($listingProduct->getChildObject());
            $description = $renderer->parseTemplate($description);
        }

        $this->addWatermarkInfoToDescriptionIfNeed($description);
        return $description;
    }

    private function addWatermarkInfoToDescriptionIfNeed(&$description)
    {
        if (!$this->description['watermark_mode'] || strpos($description, 'm2e_watermark') === false) {
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
        $productId = isset($this->description['magento_product_id'])
            ? $this->description['magento_product_id'] : -1;
        $storeId = isset($this->description['store_id'])
            ? $this->description['store_id'] : \Magento\Store\Model\Store::DEFAULT_STORE_ID;

        $magentoProduct = $this->getMagentoProductById($productId, $storeId);
        $listingProduct = $this->getListingProductByMagentoProductId($productId, $storeId);

        return [
            'magento_product' => $magentoProduct,
            'listing_product' => $listingProduct
        ];
    }

    private function getMagentoProductById($productId, $storeId)
    {
        if (!$this->isMagentoProductExists($productId)) {
            return NULL;
        }

        /** @var \Ess\M2ePro\Model\Magento\Product $magentoProduct */
        $magentoProduct = $this->modelFactory->getObject('Magento\Product');

        return $magentoProduct->loadProduct($productId, $storeId);
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
}