<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Variation\Manage;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Main;

class SetGeneralIdOwner extends Main
{
    private const GENERAL_ID_OWNER_NOT_SET = -1;

    /** @var \Ess\M2ePro\Model\Listing\ProductFactory */
    private $listingProductFactory;
    /** @var \Ess\M2ePro\Helper\Data\Session */
    private $session;
    /** @var \Ess\M2ePro\Model\Amazon\Marketplace\DetailsFactory */
    private $amazonMarketplaceDetailsFactory;

    /**
     * @param \Ess\M2ePro\Helper\Data\Session $session
     * @param \Ess\M2ePro\Model\Listing\ProductFactory $listingProductFactory
     * @param \Ess\M2ePro\Model\Amazon\Marketplace\DetailsFactory $amazonMarketplaceDetailsFactory
     * @param \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory
     * @param \Ess\M2ePro\Controller\Adminhtml\Context $context
     */
    public function __construct(
        \Ess\M2ePro\Helper\Data\Session $session,
        \Ess\M2ePro\Model\Listing\ProductFactory $listingProductFactory,
        \Ess\M2ePro\Model\Amazon\Marketplace\DetailsFactory $amazonMarketplaceDetailsFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($amazonFactory, $context);
        $this->listingProductFactory = $listingProductFactory;
        $this->session = $session;
        $this->amazonMarketplaceDetailsFactory = $amazonMarketplaceDetailsFactory;
    }

    /**
     * @inheridoc
     */
    public function execute()
    {
        $listingProductId = (int)$this->getRequest()->getParam('product_id');
        $generalIdOwner = (int)$this->getRequest()->getParam('general_id_owner', self::GENERAL_ID_OWNER_NOT_SET);

        if (
            empty($listingProductId)
            || $generalIdOwner === self::GENERAL_ID_OWNER_NOT_SET
        ) {
            $this->setAjaxContent('You should provide correct parameters.', false);

            return $this->getResult();
        }

        if ($generalIdOwner !== \Ess\M2ePro\Model\Amazon\Listing\Product::IS_GENERAL_ID_OWNER_YES) {
            $this->setJsonContent($this->setGeneralIdOwner($listingProductId, $generalIdOwner));

            return $this->getResult();
        }

        $sku = $this->session->getValue('listing_product_setting_owner_sku_' . $listingProductId);

        if (empty($sku) && !$this->hasListingProductSku($listingProductId)) {
            $this->setJsonContent(['success' => false, 'empty_sku' => true]);

            return $this->getResult();
        }

        $data = $this->setGeneralIdOwner($listingProductId, $generalIdOwner);

        if (!$data['success']) {
            $mainBlock = $this
                ->getLayout()
                ->createBlock(\Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Product\Template\ProductType::class);

            $mainBlock->setMessages([
                [
                    'type' => 'warning',
                    'text' => $data['msg'],
                ],
            ]);
            $data['html'] = $mainBlock->toHtml();
        } else {
            $listingProduct = $this->loadListingProduct($listingProductId);
            $listingProduct->getChildObject()->setData('sku', $sku);
            $listingProduct->save();

            $this->session->removeValue('listing_product_setting_owner_sku_' . $listingProductId);
        }

        $this->setJsonContent($data);

        return $this->getResult();
    }

    private function hasListingProductSku($productId)
    {
        $listingProduct = $this->loadListingProduct($productId);
        $sku = $listingProduct->getSku();

        return !empty($sku);
    }

    /**
     * @param int $productId
     * @param int $generalIdOwner
     *
     * @return true[]
     * @throws \Ess\M2ePro\Model\Exception
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function setGeneralIdOwner(int $productId, int $generalIdOwner)
    {
        $data = ['success' => true];

        $listingProduct = $this->loadListingProduct($productId);

        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
        $amazonListingProduct = $listingProduct->getChildObject();

        if ($generalIdOwner === \Ess\M2ePro\Model\Amazon\Listing\Product::IS_GENERAL_ID_OWNER_YES) {
            if (!$amazonListingProduct->isExistsProductTypeTemplate()) {
                $data['success'] = false;
                $data['msg'] = $this->__(
                    'Product Type should be added in order for operation to be finished.'
                );

                return $data;
            }

            $detailsModel = $this->amazonMarketplaceDetailsFactory->create();
            $detailsModel->setMarketplaceId($listingProduct->getListing()->getMarketplaceId());
            $themes = $detailsModel->getVariationThemes(
                $amazonListingProduct->getProductTypeTemplate()->getNick()
            );

            if (empty($themes)) {
                $data['success'] = false;
                $data['msg'] = $this->__(
                    'The selected Product Type restricts adding variations.'
                );

                return $data;
            }

            $productAttributes = $amazonListingProduct->getVariationManager()
                                                      ->getTypeModel()
                                                      ->getProductAttributes();

            $isCountEqual = false;
            foreach ($themes as $theme) {
                if (count($theme['attributes']) == count($productAttributes)) {
                    $isCountEqual = true;
                    break;
                }
            }

            if (!$isCountEqual) {
                $data['success'] = false;
                $data['msg'] = $this->__('Number of attributes doesn’t match');

                return $data;
            }
        }

        $listingProduct->getChildObject()->setData('is_general_id_owner', $generalIdOwner)->save();
        $amazonListingProduct->getVariationManager()->getTypeModel()->getProcessor()->process();

        return $data;
    }

    /**
     * @param int $listingProductId
     *
     * @return \Ess\M2ePro\Model\Listing\Product
     */
    private function loadListingProduct(int $listingProductId): \Ess\M2ePro\Model\Listing\Product
    {
        $listingProduct = $this->listingProductFactory->create([
            'childMode' => \Ess\M2ePro\Helper\Component\Amazon::NICK,
        ]);
        $listingProduct->load($listingProductId);

        return $listingProduct;
    }
}
