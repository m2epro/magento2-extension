<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Template\ProductType;

use Ess\M2ePro\Helper\Component\Amazon\ProductType as ProductTypeHelper;

class GetProductTypeInfo extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Template\ProductType
{
    private \Ess\M2ePro\Model\Amazon\Dictionary\ProductTypeService $dictionaryProductTypeService;
    private \Ess\M2ePro\Model\Amazon\Marketplace\Repository $amazonMarketplaceRepository;
    private \Ess\M2ePro\Model\Amazon\Template\ProductType\Repository $templateProductTypeRepository;
    private \Ess\M2ePro\Model\Amazon\ProductType\AttributeMapping\Suggester $productTypeAttributeMappingSuggester;

    public function __construct(
        \Ess\M2ePro\Model\Amazon\Template\ProductType\Repository $templateProductTypeRepository,
        \Ess\M2ePro\Model\Amazon\Marketplace\Repository $amazonMarketplaceRepository,
        \Ess\M2ePro\Model\Amazon\Dictionary\ProductTypeService $dictionaryProductTypeService,
        \Ess\M2ePro\Model\Amazon\ProductType\AttributeMapping\Suggester $productTypeAttributeMappingSuggester,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($amazonFactory, $context);
        $this->dictionaryProductTypeService = $dictionaryProductTypeService;
        $this->amazonMarketplaceRepository = $amazonMarketplaceRepository;
        $this->templateProductTypeRepository = $templateProductTypeRepository;
        $this->productTypeAttributeMappingSuggester = $productTypeAttributeMappingSuggester;
    }

    public function execute()
    {
        $marketplaceId = (int)$this->getRequest()->getParam('marketplace_id');
        if (!$marketplaceId) {
            $this->setJsonContent([
                'result' => false,
                'message' => 'You should provide correct marketplace_id.',
            ]);

            return $this->getResult();
        }

        $marketplace = $this->amazonMarketplaceRepository->get($marketplaceId);
        $productTypeNick = (string)$this->getRequest()->getParam('product_type');
        if (!$productTypeNick) {
            $this->setJsonContent([
                'result' => false,
                'message' => 'You should provide correct product_type.',
            ]);

            return $this->getResult();
        }

        $onlyRequiredAttributes = (bool)$this->getRequest()->getParam('only_required_attributes');

        $productType = $this->dictionaryProductTypeService->retrieve($productTypeNick, $marketplace);

        $template = $this->templateProductTypeRepository->findByDictionary($productType)[0] ?? null;

        $isNewProductType = (bool)$this->getRequest()->getParam('is_new_product_type');

        $this->setJsonContent([
            'result' => true,
            'data' => [
                'scheme' => $productType->getScheme(),
                'settings' => $template !== null ? $template->getSelfSetting() : [],
                'groups' => $productType->getAttributesGroups(),
                'timezone_shift' => ProductTypeHelper::getTimezoneShift(),
                'specifics_default_settings' => $isNewProductType
                    ? $this->productTypeAttributeMappingSuggester->getSuggestedAttributes() : [],
                'main_image_specifics' => ProductTypeHelper::getMainImageSpecifics(),
                'other_images_specifics' => ProductTypeHelper::getOtherImagesSpecifics(),
                'recommended_browse_node_link' => ProductTypeHelper::getRecommendedBrowseNodesLink($marketplaceId),
            ],
        ]);

        return $this->getResult();
    }
}
