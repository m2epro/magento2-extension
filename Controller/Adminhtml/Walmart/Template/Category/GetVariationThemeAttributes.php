<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Template\Category;

use Ess\M2ePro\Controller\Adminhtml\Walmart\Template\Category;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Walmart\Template\Category\GetVariationThemeAttributes
 */
class GetVariationThemeAttributes extends Category
{
    //########################################

    public function execute()
    {
        $model = $this->modelFactory->getObject('Walmart_Marketplace_Details');
        $model->setMarketplaceId($this->getRequest()->getParam('marketplace_id'));

        $variationThemes = $model->getVariationAttributes($this->getRequest()->getParam('product_data_nick'));

        $attributes = [];
        foreach ($variationThemes as $themeName => $themeInfo) {
            $attributeName = $themeInfo;
            if (isset($attributes[$attributeName]) && in_array($themeName, $attributes[$attributeName])) {
                continue;
            }

            $attributes[$attributeName][] = $themeName;
        }

        $this->setJsonContent($attributes);
        return $this->getResult();
    }

    //########################################
}
