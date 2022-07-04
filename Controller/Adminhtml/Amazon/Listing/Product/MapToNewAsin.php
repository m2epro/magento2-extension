<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Main;

class MapToNewAsin extends Main
{
    /** @var \Ess\M2ePro\Helper\Component\Amazon\Variation */
    protected $variationHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Component\Amazon\Variation $variationHelper,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($amazonFactory, $context);
        $this->variationHelper = $variationHelper;
    }

    //########################################

    public function execute()
    {
        $productsIds = $this->getRequestIds('products_id');

        if (empty($productsIds)) {
            $this->setAjaxContent('You should provide correct parameters.', false);
            return $this->getResult();
        }

        if (!is_array($productsIds)) {
            $productsIds = explode(',', $productsIds);
        }

        $messages = [];

        $badDescriptionProductsIds = [];
        $descriptionTemplatesBlock = '';

        $errorMsg = $this->__(
            'The new ASIN/ISBN creation feature was not added to some Items because '
        );
        $errors = [];
        $errorMsgProductsCount = 0;

        $filteredByGeneralId = $this->variationHelper->filterProductsByGeneralId($productsIds);

        if (count($productsIds) != count($filteredByGeneralId)) {
            $tempCount = count($productsIds) - count($filteredByGeneralId);
            $errors[] = $this->__('%count% Item(s) already have ASIN(s)/ISBN(s).', $tempCount);
            $errorMsgProductsCount += $tempCount;
        }

        $filteredByGeneralIdOwner = $this->variationHelper->filterProductsByGeneralIdOwner($filteredByGeneralId);

        if (count($filteredByGeneralId) != count($filteredByGeneralIdOwner)) {
            $tempCount = count($filteredByGeneralId) - count($filteredByGeneralIdOwner);
            $errors[] = $this->__(
                '%count% Item(s) already have possibility to create ASIN(s)/ISBN(s).',
                $tempCount
            );
            $errorMsgProductsCount += $tempCount;
        }

        $filteredByStatus = $this->variationHelper->filterProductsByStatus($filteredByGeneralIdOwner);

        if (count($filteredByGeneralIdOwner) != count($filteredByStatus)) {
            $tempCount = count($filteredByGeneralIdOwner) - count($filteredByStatus);
            $errors[] = $this->__(
                '%count% Items have the Status different from “Not Listed”.',
                $tempCount
            );
            $errorMsgProductsCount += $tempCount;
        }

        $filteredLockedProducts = $this->variationHelper->filterLockedProducts($filteredByStatus);

        if (count($filteredByStatus) != count($filteredLockedProducts)) {
            $tempCount = count($filteredByStatus) - count($filteredLockedProducts);
            $errors[] = $this->__(
                'There are some other actions performed on %count% Items.',
                $tempCount
            );
            $errorMsgProductsCount += $tempCount;
        }

        $filteredProductsIdsByType = $this->variationHelper->filterProductsByMagentoProductType($filteredLockedProducts);

        if (count($filteredLockedProducts) != count($filteredProductsIdsByType)) {
            $tempCount = count($filteredLockedProducts) - count($filteredProductsIdsByType);
            $errors[] = $this->__(
                '%count% Items are Simple with Custom Options,
                Bundle or Downloadable with Separated Links Magento Products.',
                $tempCount
            );
            $errorMsgProductsCount += $tempCount;
        }

        $filteredProductsIdsByTpl = $this->variationHelper->filterProductsByDescriptionTemplate($filteredProductsIdsByType);

        if (count($filteredProductsIdsByType) != count($filteredProductsIdsByTpl)) {
            $badDescriptionProductsIds = array_diff($filteredProductsIdsByType, $filteredProductsIdsByTpl);

            $tempCount = count($filteredProductsIdsByType) - count($filteredProductsIdsByTpl);
            $errors[] = $this->__(
                '%count% Item(s) haven’t got the Description Policy assigned with enabled ability to create
                 new ASIN(s)/ISBN(s).',
                $tempCount
            );
            $errorMsgProductsCount += $tempCount;
        }

        $filteredProductsIdsByParent = $this->variationHelper->filterParentProductsByVariationTheme(
            $filteredProductsIdsByTpl
        );

        if (count($filteredProductsIdsByTpl) != count($filteredProductsIdsByParent)) {
            $badThemeProductsIds = array_diff($filteredProductsIdsByTpl, $filteredProductsIdsByParent);
            $badDescriptionProductsIds = array_merge(
                $badDescriptionProductsIds,
                $badThemeProductsIds
            );

            $tempCount = count($filteredProductsIdsByTpl) - count($filteredProductsIdsByParent);
            $errors[] = $this->__(
                'The Category chosen in the Description Policies of %count% Items does not support creation of
                 Variational Products at all.',
                $tempCount
            );
            $errorMsgProductsCount += $tempCount;
        }

        if (!empty($errors)) {
            $messages[] =  [
                'type' => 'warning',
                'text' => $errorMsg . implode(', ', $errors) . ' ('. $errorMsgProductsCount . ')'
            ];
        }

        if (!empty($filteredProductsIdsByParent)) {
            $this->mapToNewAsinByChunks($filteredProductsIdsByParent);
            $this->runProcessorForParents($filteredProductsIdsByParent);
            array_unshift(
                $messages,
                [
                    'type' => 'success',
                    'text' => $this->__(
                        'New ASIN/ISBN creation feature was added to %count% Products.',
                        count($filteredProductsIdsByParent)
                    )
                ]
            );
        }

        if (!empty($badDescriptionProductsIds)) {
            $badDescriptionProductsIds = $this->variationHelper
                ->filterProductsByMagentoProductType($badDescriptionProductsIds);

            $descriptionTemplatesBlock = $this->getLayout()
                          ->createBlock(\Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Product\Template\Description::class);
            $descriptionTemplatesBlock->setNewAsin(true);
            $descriptionTemplatesBlock->setMessages($messages);
            $descriptionTemplatesBlock = $descriptionTemplatesBlock->toHtml();
        }

        $this->setJsonContent([
            'messages' => $messages,
            'html' => $descriptionTemplatesBlock,
            'products_ids' => implode(',', $badDescriptionProductsIds)
        ]);

        return $this->getResult();
    }

    protected function mapToNewAsinByChunks($productsIds)
    {
        $connection = $this->resourceConnection->getConnection();
        $tableAmazonListingProduct = $this->activeRecordFactory
            ->getObject('Amazon_Listing_Product')->getResource()->getMainTable();

        $productsIds = array_chunk($productsIds, 1000);
        foreach ($productsIds as $productsIdsChunk) {
            $connection->update(
                $tableAmazonListingProduct,
                [
                    'is_general_id_owner' => \Ess\M2ePro\Model\Amazon\Listing\Product::IS_GENERAL_ID_OWNER_YES
                ],
                '`listing_product_id` IN ('.implode(',', $productsIdsChunk).')'
            );
        }
    }

    protected function runProcessorForParents($productsIds)
    {
        $connection = $this->resourceConnection->getConnection();
        $tableAmazonListingProduct = $this->activeRecordFactory
            ->getObject('Amazon_Listing_Product')->getResource()->getMainTable();

        $select = $connection->select();
        $select->from(['alp' => $tableAmazonListingProduct], ['listing_product_id'])
            ->where('listing_product_id IN (?)', $productsIds)
            ->where('is_variation_parent = ?', 1);

        $productsIds = $connection->fetchCol($select);

        foreach ($productsIds as $productId) {
            $listingProduct = $this->amazonFactory->getObjectLoaded('Listing\Product', $productId);
            $listingProduct->getChildObject()->getVariationManager()->getTypeModel()->getProcessor()->process();
        }
    }
}
