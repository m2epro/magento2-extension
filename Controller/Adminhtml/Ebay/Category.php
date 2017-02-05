<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay;

abstract class Category extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Main
{
    //########################################

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Ess_M2ePro::ebay_listings_m2epro');
    }

    //########################################

    protected function getSpecificsFromPost($post)
    {
        $itemSpecifics = array();
        for ($i=0; true; $i++) {
            if (!isset($post['item_specifics_mode_'.$i])) {
                break;
            }
            if (!isset($post['custom_item_specifics_value_mode_'.$i])) {
                continue;
            }
            $ebayRecommendedTemp = array();
            if (isset($post['item_specifics_value_ebay_recommended_'.$i])) {
                $ebayRecommendedTemp = (array)$post['item_specifics_value_ebay_recommended_'.$i];
            }
            foreach ($ebayRecommendedTemp as $key=>$temp) {
                $ebayRecommendedTemp[$key] = base64_decode($temp);
            }

            $attributeValue = '';
            $customAttribute = '';

            if ($post['item_specifics_mode_'.$i] ==
                \Ess\M2ePro\Model\Ebay\Template\Category\Specific::MODE_ITEM_SPECIFICS) {

                $temp = \Ess\M2ePro\Model\Ebay\Template\Category\Specific::VALUE_MODE_CUSTOM_VALUE;
                if ((int)$post['item_specifics_value_mode_' . $i] == $temp) {
                    $attributeValue = (array)$post['item_specifics_value_custom_value_'.$i];
                    $customAttribute = '';
                    $ebayRecommendedTemp = '';
                }

                $temp = \Ess\M2ePro\Model\Ebay\Template\Category\Specific::VALUE_MODE_CUSTOM_ATTRIBUTE;
                if ((int)$post['item_specifics_value_mode_'.$i] == $temp) {
                    $customAttribute = $post['item_specifics_value_custom_attribute_'.$i];
                    $attributeValue = '';
                    $ebayRecommendedTemp = '';
                }

                $temp = \Ess\M2ePro\Model\Ebay\Template\Category\Specific::VALUE_MODE_EBAY_RECOMMENDED;
                if ((int)$post['item_specifics_value_mode_'.$i] == $temp) {
                    $customAttribute = '';
                    $attributeValue = '';
                }

                $temp = \Ess\M2ePro\Model\Ebay\Template\Category\Specific::VALUE_MODE_NONE;
                if ((int)$post['item_specifics_value_mode_'.$i] == $temp) {
                    $customAttribute = '';
                    $attributeValue = '';
                    $ebayRecommendedTemp = '';
                }

                $itemSpecifics[] = array(
                    'mode'                   => (int)$post['item_specifics_mode_'.$i],
                    'attribute_title'        => $post['item_specifics_attribute_title_'.$i],
                    'value_mode'             => (int)$post['item_specifics_value_mode_'.$i],
                    'value_ebay_recommended' => !empty($ebayRecommendedTemp)
                        ? $this->getHelper('Data')->jsonEncode($ebayRecommendedTemp) : '',
                    'value_custom_value'     => !empty($attributeValue)
                        ? $this->getHelper('Data')->jsonEncode($attributeValue)      : '',
                    'value_custom_attribute' => $customAttribute
                );
            }

            if ($post['item_specifics_mode_'.$i] ==
                \Ess\M2ePro\Model\Ebay\Template\Category\Specific::MODE_CUSTOM_ITEM_SPECIFICS) {

                $attributeTitle = '';
                $temp = \Ess\M2ePro\Model\Ebay\Template\Category\Specific::VALUE_MODE_CUSTOM_VALUE;
                if ((int)$post['custom_item_specifics_value_mode_' . $i] == $temp) {
                    $attributeTitle = $post['custom_item_specifics_label_custom_value_'.$i];
                    $attributeValue = (array)$post['item_specifics_value_custom_value_'.$i];
                    $customAttribute = '';
                }

                $temp = \Ess\M2ePro\Model\Ebay\Template\Category\Specific::VALUE_MODE_CUSTOM_ATTRIBUTE;
                if ((int)$post['custom_item_specifics_value_mode_'.$i] == $temp) {
                    $attributeTitle = '';
                    $attributeValue = '';
                    $customAttribute = $post['item_specifics_value_custom_attribute_'.$i];
                }

                $temp = \Ess\M2ePro\Model\Ebay\Template\Category\Specific::VALUE_MODE_CUSTOM_LABEL_ATTRIBUTE;
                if ((int)$post['custom_item_specifics_value_mode_'.$i] == $temp) {
                    $attributeTitle = $post['custom_item_specifics_label_custom_label_attribute_'.$i];
                    $attributeValue = '';
                    $customAttribute = $post['item_specifics_value_custom_attribute_'.$i];
                }

                $itemSpecifics[] = array(
                    'mode'                      => (int)$post['item_specifics_mode_' . $i],
                    'attribute_title'           => $attributeTitle,
                    'value_mode'                => (int)$post['custom_item_specifics_value_mode_' . $i],
                    'value_ebay_recommended'    => '',
                    'value_custom_value'        => !empty($attributeValue)
                        ? $this->getHelper('Data')->jsonEncode($attributeValue) : '',
                    'value_custom_attribute'    => $customAttribute
                );
            }
        }

        return $itemSpecifics;
    }

    //########################################
}