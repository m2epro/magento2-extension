<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Template\Description;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Template\Description;

class GetVariationThemeAttributes extends Description
{
    //########################################

    public function execute()
    {
        $model = $this->modelFactory->getObject('Amazon\Marketplace\Details');
        $model->setMarketplaceId($this->getRequest()->getParam('marketplace_id'));

        $variationThemes = $model->getVariationThemes($this->getRequest()->getParam('product_data_nick'));

        $attributes = array();
        foreach ($variationThemes as $themeName => $themeInfo) {
            foreach ($themeInfo['attributes'] as $attributeName) {

                if (isset($attributes[$attributeName]) && in_array($themeName, $attributes[$attributeName])) {
                    continue;
                }

                $attributes[$attributeName][] = $themeName;
            }
        }

        $this->setJsonContent($attributes);
        return $this->getResult();
    }

    //########################################
}