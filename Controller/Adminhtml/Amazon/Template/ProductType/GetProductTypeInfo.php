<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Template\ProductType;

class GetProductTypeInfo extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Template\ProductType
{
    /** @var \Ess\M2ePro\Helper\Component\Amazon\ProductType */
    private $productTypeHelper;

    /**
     * @param \Ess\M2ePro\Helper\Component\Amazon\ProductType $productTypeHelper
     * @param \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory
     * @param \Ess\M2ePro\Controller\Adminhtml\Context $context
     */
    public function __construct(
        \Ess\M2ePro\Helper\Component\Amazon\ProductType $productTypeHelper,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($amazonFactory, $context);
        $this->productTypeHelper = $productTypeHelper;
    }

    /**
     * @return \Magento\Framework\Controller\Result\Raw|\Magento\Framework\Controller\ResultInterface|\Magento\Framework\View\Result\Page
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Exception
     */
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

        $productType = (string)$this->getRequest()->getParam('product_type');
        if (!$productType) {
            $this->setJsonContent([
                'result' => false,
                'message' => 'You should provide correct product_type.',
            ]);

            return $this->getResult();
        }

        $onlyRequiredAttributes = (bool)$this->getRequest()->getParam('only_required_attributes');
        $scheme = $this->productTypeHelper->getProductTypeScheme(
            $marketplaceId,
            $productType,
            $onlyRequiredAttributes
        );
        $onlyForAttributes = $onlyRequiredAttributes ? $scheme : [];
        $settings = $this->productTypeHelper->getProductTypeSettings($marketplaceId, $productType);
        $groups = $this->productTypeHelper->getProductTypeGroups(
            $marketplaceId,
            $productType,
            $onlyForAttributes
        );

        $isNewProductType = (bool)$this->getRequest()->getParam('is_new_product_type');
        $specificsDefaultSettings = $isNewProductType ? $this->productTypeHelper->getSpecificsDefaultSettings() : [];
        $timezoneShift = $this->productTypeHelper->getTimezoneShift();
        $mainImageSpecifics = $this->productTypeHelper->getMainImageSpecifics();
        $otherImagesSpecifics = $this->productTypeHelper->getOtherImagesSpecifics();
        $recommendedBrowseNodesLink = $this->productTypeHelper->getRecommendedBrowseNodesLink((int)$marketplaceId);

        $this->setJsonContent([
            'result' => true,
            'data' => [
                'scheme' => $scheme,
                'settings' => $settings,
                'groups' => $groups,
                'timezone_shift' => $timezoneShift,
                'specifics_default_settings' => $specificsDefaultSettings,
                'main_image_specifics' => $mainImageSpecifics,
                'other_images_specifics' => $otherImagesSpecifics,
                'recommended_browse_node_link' => $recommendedBrowseNodesLink,
            ],
        ]);

        return $this->getResult();
    }
}
