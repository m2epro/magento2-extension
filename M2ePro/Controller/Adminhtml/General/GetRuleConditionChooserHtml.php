<?php
/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\General;

use Ess\M2ePro\Controller\Adminhtml\General;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\General\GetRuleConditionChooserHtml
 */
class GetRuleConditionChooserHtml extends General
{
    //########################################

    public function execute()
    {
        $request = $this->getRequest();

        switch ($request->getParam('attribute')) {
            case 'sku':
                $block = $this->createBlock(
                    'Magento_Product_Rule_Chooser_Sku',
                    'product_rule_chooser_sku',
                    [
                        'data' => [
                            'js_form_object' => $request->getParam('form'),
                            'store' => $request->getParam('store', 0)
                        ]
                    ]
                );
                break;

            case 'category_ids':
                $ids = $request->getParam('selected', []);
                if (is_array($ids)) {
                    foreach ($ids as $key => &$id) {
                        $id = (int) $id;
                        if ($id <= 0) {
                            unset($ids[$key]);
                        }
                    }

                    $ids = array_unique($ids);
                } else {
                    $ids = [];
                }

                $block = $this->getLayout()->createBlock(
                    \Magento\Catalog\Block\Adminhtml\Category\Checkboxes\Tree::class,
                    'promo_widget_chooser_category_ids',
                    [
                        'data' => [
                            'js_form_object' => $request->getParam('form')
                        ]
                    ]
                )->setCategoryIds($ids);
                break;

            default:
                $block = false;
                break;
        }

        if ($block) {
            $this->setAjaxContent($block->toHtml());
        } else {
            $this->setAjaxContent('', false);
        }

        return $this->getResult();
    }

    //########################################
}
