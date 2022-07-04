<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Product\Variation\Manage\Tabs;

class Vocabulary extends \Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock
{
    protected $_template = 'amazon/listing/product/variation/manage/tabs/vocabulary.phtml';

    /** @var \Ess\M2ePro\Helper\Component\Amazon\Vocabulary */
    protected $vocabularyHelper;

    /** @var \Ess\M2ePro\Model\Listing\Product */
    protected $listingProduct = null;

    public function __construct(
        \Ess\M2ePro\Helper\Component\Amazon\Vocabulary $vocabularyHelper,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        array $data = []
    )
    {
        parent::__construct($context, $data);
        $this->vocabularyHelper = $vocabularyHelper;
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
     * @return \Ess\M2ePro\Model\Listing\Product
     */
    public function getListingProduct()
    {
        return $this->listingProduct;
    }

    //########################################

    public function prepareData()
    {
        $localVocabulary = [];
        $fixedAttributes = [];
        $matchedAttributes = $this->getListingProduct()->getChildObject()
            ->getVariationManager()->getTypeModel()->getMatchedAttributes();
        $magentoProductVariations = $this->getListingProduct()
            ->getMagentoProduct()
            ->getVariationInstance()
            ->getVariationsTypeStandard();

        $vocabularyData = $this->vocabularyHelper->getLocalData();

        if (empty($matchedAttributes)) {
            return [
                'local_vocabulary' => $localVocabulary,
                'fixed_attributes' => $fixedAttributes
            ];
        }

        foreach ($matchedAttributes as $magentoAttr => $channelAttr) {
            foreach ($vocabularyData as $attribute => $attributeData) {
                if (in_array($magentoAttr, $attributeData['names']) || $attribute == $channelAttr) {
                    if (!in_array($magentoAttr, $attributeData['names'])) {
                        $fixedAttributes[$magentoAttr][] = $attribute;
                    }

                    $localVocabulary[$magentoAttr][$attribute] = [];

                    if (!empty($attributeData['options'])) {
                        foreach ($magentoProductVariations['set'][$magentoAttr] as $magentoOption) {
                            foreach ($attributeData['options'] as $attributeOptions) {
                                if (in_array($magentoOption, $attributeOptions)) {
                                    $localVocabulary[$magentoAttr][$attribute][$magentoOption][] = $attributeOptions;
                                }
                            }
                        }
                    }

                    if (!empty($fixedAttributes[$magentoAttr]) &&
                        in_array($attribute, $fixedAttributes[$magentoAttr]) &&
                        empty($localVocabulary[$magentoAttr][$attribute])) {
                        unset($localVocabulary[$magentoAttr][$attribute]);
                        if (empty($localVocabulary[$magentoAttr])) {
                            unset($localVocabulary[$magentoAttr]);
                        }
                    }
                }
            }
        }

        return [
            'local_vocabulary' => $localVocabulary,
            'fixed_attributes' => $fixedAttributes
        ];
    }

    //########################################

    protected function _beforeToHtml()
    {
        $form = $this->getLayout()
         ->createBlock(\Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Product\Variation\Manage\Tabs\Vocabulary\Form::class);
        $this->setChild('variation_Vocabulary_form', $form);

        return parent::_beforeToHtml();
    }

    //########################################
}
