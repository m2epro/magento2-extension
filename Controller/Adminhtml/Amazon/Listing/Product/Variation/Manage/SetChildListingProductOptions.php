<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Variation\Manage;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Main;

class SetChildListingProductOptions extends Main
{
    /** @var \Ess\M2ePro\Helper\Component\Amazon\Vocabulary */
    protected $vocabularyHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Component\Amazon\Vocabulary $vocabularyHelper,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($amazonFactory, $context);
        $this->vocabularyHelper = $vocabularyHelper;
    }

    public function execute()
    {
        $listingProductId = $this->getRequest()->getParam('product_id');
        $productOptions   = $this->getRequest()->getParam('product_options');

        if (empty($listingProductId) || empty($productOptions['values']) || empty($productOptions['attr'])) {
            $this->setAjaxContent('You should provide correct parameters.', false);

            return $this->getResult();
        }

        /** @var \Ess\M2ePro\Model\Listing\Product $childListingProduct */
        $childListingProduct = $this->amazonFactory->getObjectLoaded('Listing\Product', $listingProductId);

        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonChildListingProduct */
        $amazonChildListingProduct = $childListingProduct->getChildObject();

        $childTypeModel = $amazonChildListingProduct->getVariationManager()->getTypeModel();

        $parentListingProduct = $childTypeModel->getParentListingProduct();

        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonParentListingProduct */
        $amazonParentListingProduct = $parentListingProduct->getChildObject();

        $magentoProduct = $parentListingProduct->getMagentoProduct();

        $magentoOptions = array_combine(
            $productOptions['attr'],
            $productOptions['values']
        );

        $magentoVariation = $magentoProduct->getVariationInstance()->getVariationTypeStandard($magentoOptions);

        $childTypeModel->setProductVariation($magentoVariation);

        $parentTypeModel = $amazonParentListingProduct->getVariationManager()->getTypeModel();
        $parentTypeModel->getProcessor()->process();

        $result = ['success' => true];

        if ($this->vocabularyHelper->isOptionAutoActionDisabled()) {
            $this->setJsonContent($result);

            return $this->getResult();
        }

        $matchedAttributes = $parentTypeModel->getMatchedAttributes();
        $channelOptions = $childTypeModel->getChannelOptions();

        $optionsForAddingToVocabulary = [];

        foreach ($matchedAttributes as $productAttribute => $channelAttribute) {
            $productOption = $magentoOptions[$productAttribute];
            $channelOption = $channelOptions[$channelAttribute];

            if ($productOption == $channelOption) {
                continue;
            }

            if ($this->vocabularyHelper->isOptionExistsInLocalStorage(
                    $productOption,
                    $channelOption,
                    $channelAttribute
                )
            ) {
                continue;
            }

            if ($this->vocabularyHelper->isOptionExistsInServerStorage(
                    $productOption,
                    $channelOption,
                    $channelAttribute
                )
            ) {
                continue;
            }

            $optionsForAddingToVocabulary[$channelAttribute] = [$productOption => $channelOption];
        }

        if ($this->vocabularyHelper->isOptionAutoActionNotSet()) {
            if (!empty($optionsForAddingToVocabulary)) {
                $result['vocabulary_attribute_options'] = $optionsForAddingToVocabulary;
            }

            $this->setAjaxContent(json_encode($result, JSON_FORCE_OBJECT), false);

            return $this->getResult();
        }

        foreach ($optionsForAddingToVocabulary as $channelAttribute => $options) {
            foreach ($options as $productOption => $channelOption) {
                $this->vocabularyHelper->addOption($productOption, $channelOption, $channelAttribute);
            }
        }

        $this->setJsonContent(['success' => true]);

        return $this->getResult();
    }
}
