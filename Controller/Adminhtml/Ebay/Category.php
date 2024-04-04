<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay;

abstract class Category extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Main
{
    /** @var \Ess\M2ePro\Block\Adminhtml\Magento\Product\Rule\ViewState\Manager */
    private $ruleViewStateManager;
    /** @var \Ess\M2ePro\Model\Ebay\Magento\Product\RuleFactory */
    private $ebayProductRuleFactory;
    /** @var \Ess\M2ePro\Helper\Data\GlobalData */
    private $globalDataHelper;
    /** @var \Ess\M2ePro\Helper\Data\Session */
    private $sessionHelper;
    /** @var \Ess\M2ePro\Block\Adminhtml\Magento\Product\Rule\ViewStateFactory */
    private $viewStateFactory;

    public function __construct(
        \Ess\M2ePro\Block\Adminhtml\Magento\Product\Rule\ViewState\Manager $ruleViewStateManager,
        \Ess\M2ePro\Block\Adminhtml\Magento\Product\Rule\ViewStateFactory $viewStateFactory,
        \Ess\M2ePro\Model\Ebay\Magento\Product\RuleFactory $ebayProductRuleFactory,
        \Ess\M2ePro\Helper\Data\GlobalData $globalDataHelper,
        \Ess\M2ePro\Helper\Data\Session $sessionHelper,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($ebayFactory, $context);

        $this->ruleViewStateManager = $ruleViewStateManager;
        $this->ebayProductRuleFactory = $ebayProductRuleFactory;
        $this->globalDataHelper = $globalDataHelper;
        $this->sessionHelper = $sessionHelper;
        $this->viewStateFactory = $viewStateFactory;
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Ess_M2ePro::ebay_listings_m2epro');
    }

    protected function getSpecificsFromPost($post)
    {
        $itemSpecifics = [];
        for ($i = 0; true; $i++) {
            if (!isset($post['item_specifics_mode_' . $i])) {
                break;
            }
            if (!isset($post['custom_item_specifics_value_mode_' . $i])) {
                continue;
            }
            $ebayRecommendedTemp = [];
            if (isset($post['item_specifics_value_ebay_recommended_' . $i])) {
                $ebayRecommendedTemp = (array)$post['item_specifics_value_ebay_recommended_' . $i];
            }
            foreach ($ebayRecommendedTemp as $key => $temp) {
                $ebayRecommendedTemp[$key] = base64_decode($temp);
            }

            $attributeValue = '';
            $customAttribute = '';

            if (
                $post['item_specifics_mode_' . $i] ==
                \Ess\M2ePro\Model\Ebay\Template\Category\Specific::MODE_ITEM_SPECIFICS
            ) {
                $temp = \Ess\M2ePro\Model\Ebay\Template\Category\Specific::VALUE_MODE_CUSTOM_VALUE;
                if ((int)$post['item_specifics_value_mode_' . $i] == $temp) {
                    $attributeValue = (array)$post['item_specifics_value_custom_value_' . $i];
                    $customAttribute = '';
                    $ebayRecommendedTemp = '';
                }

                $temp = \Ess\M2ePro\Model\Ebay\Template\Category\Specific::VALUE_MODE_CUSTOM_ATTRIBUTE;
                if ((int)$post['item_specifics_value_mode_' . $i] == $temp) {
                    $customAttribute = $post['item_specifics_value_custom_attribute_' . $i];
                    $attributeValue = '';
                    $ebayRecommendedTemp = '';
                }

                $temp = \Ess\M2ePro\Model\Ebay\Template\Category\Specific::VALUE_MODE_EBAY_RECOMMENDED;
                if ((int)$post['item_specifics_value_mode_' . $i] == $temp) {
                    $customAttribute = '';
                    $attributeValue = '';
                }

                $temp = \Ess\M2ePro\Model\Ebay\Template\Category\Specific::VALUE_MODE_NONE;
                if ((int)$post['item_specifics_value_mode_' . $i] == $temp) {
                    $customAttribute = '';
                    $attributeValue = '';
                    $ebayRecommendedTemp = '';
                }

                $itemSpecifics[] = [
                    'mode' => (int)$post['item_specifics_mode_' . $i],
                    'attribute_title' => $post['item_specifics_attribute_title_' . $i],
                    'value_mode' => (int)$post['item_specifics_value_mode_' . $i],
                    'value_ebay_recommended' => !empty($ebayRecommendedTemp)
                        ? \Ess\M2ePro\Helper\Json::encode($ebayRecommendedTemp) : '',
                    'value_custom_value' => !empty($attributeValue)
                        ? \Ess\M2ePro\Helper\Json::encode($attributeValue) : '',
                    'value_custom_attribute' => $customAttribute,
                ];
            }

            if (
                $post['item_specifics_mode_' . $i] ==
                \Ess\M2ePro\Model\Ebay\Template\Category\Specific::MODE_CUSTOM_ITEM_SPECIFICS
            ) {
                $attributeTitle = '';
                $temp = \Ess\M2ePro\Model\Ebay\Template\Category\Specific::VALUE_MODE_CUSTOM_VALUE;
                if ((int)$post['custom_item_specifics_value_mode_' . $i] == $temp) {
                    $attributeTitle = $post['custom_item_specifics_label_custom_value_' . $i];
                    $attributeValue = (array)$post['item_specifics_value_custom_value_' . $i];
                    $customAttribute = '';
                }

                $temp = \Ess\M2ePro\Model\Ebay\Template\Category\Specific::VALUE_MODE_CUSTOM_ATTRIBUTE;
                if ((int)$post['custom_item_specifics_value_mode_' . $i] == $temp) {
                    $attributeTitle = '';
                    $attributeValue = '';
                    $customAttribute = $post['item_specifics_value_custom_attribute_' . $i];
                }

                $temp = \Ess\M2ePro\Model\Ebay\Template\Category\Specific::VALUE_MODE_CUSTOM_LABEL_ATTRIBUTE;
                if ((int)$post['custom_item_specifics_value_mode_' . $i] == $temp) {
                    $attributeTitle = $post['custom_item_specifics_label_custom_label_attribute_' . $i];
                    $attributeValue = '';
                    $customAttribute = $post['item_specifics_value_custom_attribute_' . $i];
                }

                $itemSpecifics[] = [
                    'mode' => (int)$post['item_specifics_mode_' . $i],
                    'attribute_title' => $attributeTitle,
                    'value_mode' => (int)$post['custom_item_specifics_value_mode_' . $i],
                    'value_ebay_recommended' => '',
                    'value_custom_value' => !empty($attributeValue)
                        ? \Ess\M2ePro\Helper\Json::encode($attributeValue) : '',
                    'value_custom_attribute' => $customAttribute,
                ];
            }
        }

        return $itemSpecifics;
    }

    protected function setRuleModel(): void
    {
        $prefix = 'ebay_rule_category';
        $prefix .= $this->getRequest()->getParam('active_tab', '');
        $prefix .= $this->getRequest()->getParam('template_id', '');

        $getRuleBySessionData = function () use ($prefix) {
            return $this->createRuleBySessionData($prefix);
        };

        $ruleModel = $this->ruleViewStateManager->getRuleWithViewState(
            $this->viewStateFactory->create($prefix),
            \Ess\M2ePro\Model\Ebay\Magento\Product\Rule::NICK,
            $getRuleBySessionData
        );

        $this->globalDataHelper->setValue('rule_model', $ruleModel);
    }

    private function createRuleBySessionData(string $prefix): \Ess\M2ePro\Model\Ebay\Magento\Product\Rule
    {
        $this->globalDataHelper->setValue('rule_prefix', $prefix);

        $ruleModel = $this->ebayProductRuleFactory->create($prefix);

        $ruleParam = $this->getRequest()->getPost('rule');
        if (!empty($ruleParam)) {
            $this->sessionHelper->setValue(
                $prefix,
                $ruleModel->getSerializedFromPost($this->getRequest()->getPostValue())
            );
        } elseif ($ruleParam !== null) {
            $this->sessionHelper->setValue($prefix, []);
        }

        $sessionRuleData = $this->sessionHelper->getValue($prefix);
        if (!empty($sessionRuleData)) {
            $ruleModel->loadFromSerialized($sessionRuleData);
        }

        return $ruleModel;
    }
}
