<?php

declare(strict_types=1);

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Wizard\Category\Chooser\Specific;

use Ess\M2ePro\Model\MarketplaceFactory;
use Ess\M2ePro\Model\ResourceModel\Marketplace as MarketplaceResource;
use Ess\M2ePro\Helper\Component\Ebay\Category\Ebay;
use Ess\M2ePro\Helper\Magento\Attribute as MagentoAttributeHelper;
use Magento\Framework\Math\Random;
use Ess\M2ePro\Block\Adminhtml\Magento\Context\Template;

class Info extends \Ess\M2ePro\Block\Adminhtml\Widget\Info
{
    private Ebay $componentEbayCategoryEbay;

    private MagentoAttributeHelper $magentoAttributeHelper;

    private MarketplaceFactory $marketplaceModelFactory;

    private MarketplaceResource $marketplaceResource;

    public function __construct(
        Ebay $componentEbayCategoryEbay,
        MagentoAttributeHelper $magentoAttributeHelper,
        MarketplaceResource $marketplaceResource,
        MarketplaceFactory $marketplaceModelFactory,
        Random $random,
        Template $context,
        array $data = []
    ) {
        parent::__construct($random, $context, $data);

        $this->componentEbayCategoryEbay = $componentEbayCategoryEbay;
        $this->magentoAttributeHelper = $magentoAttributeHelper;
        $this->marketplaceModelFactory = $marketplaceModelFactory;
        $this->marketplaceResource = $marketplaceResource;
    }

    protected function _prepareLayout()
    {
        if ($this->getData('category_mode') == \Ess\M2ePro\Model\Ebay\Template\Category::CATEGORY_MODE_ATTRIBUTE) {
            $category = $this->__('Magento Attribute') . ' > ' .
                $this->magentoAttributeHelper->getAttributeLabel($this->getData('category_value'));
        } else {
            $category = $this->getEbayCategoryPath(
                $this->getData('category_value'),
                $this->getData('marketplace_id')
            );
            $category .= ' (' . $this->getData('category_value') . ')';
        }

        $this->setInfo(
            [
                [
                    'label' => $this->__('Category'),
                    'value' => $category,
                ],
            ]
        );

        return parent::_prepareLayout();
    }

    private function getEbayCategoryPath($value, $marketplaceId, $includeTitle = true)
    {
        $marketplaceModel = $this->marketplaceModelFactory->create();

        $this->marketplaceResource->load($marketplaceModel, $marketplaceId);

        if ($marketplaceModel->getId()) {
            $category = $marketplaceModel->getChildObject()
                                         ->getCategory((int)$value);
        }

        if (!$category) {
            return '';
        }

        $category['path'] = str_replace(' > ', '>', $category['path']);

        return $category['path'] . ($includeTitle ? '>' . $category['title'] : '');
    }
}
