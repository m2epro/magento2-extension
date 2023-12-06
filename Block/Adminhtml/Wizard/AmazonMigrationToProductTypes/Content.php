<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Wizard\AmazonMigrationToProductTypes;

class Content extends \Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock
{
    public function _construct()
    {
        parent::_construct();

        $this->setId('amazonMigrationToProductTypesContent');
        $this->setTemplate('wizard/amazonMigrationToProductTypes/content.phtml');
    }

    /**
     * @return string
     */
    public function getSupportArticleUrl(): string
    {
        return 'https://help.m2epro.com/support/solutions/articles/9000225982';
    }

    protected function _toHtml()
    {
        $this->jsUrl->addUrls([
            'wizard_amazonMigrationToProductTypes/proceed' => $this->getUrl('*/*/accept'),
        ]);

        $this->jsTranslator->addTranslations([
            'An error during of marketplace synchronization.' =>
                $this->__('An error during of marketplace synchronization.'),
        ]);

        $this->js->add(
            <<<JS
    require([
        'M2ePro/Wizard/AmazonMigrationToProductTypes',
    ], function(){
        window.WizardAmazonMigrationToProductTypesObj = new WizardAmazonMigrationToProductTypes();
    });
JS
        );

        return parent::_toHtml();
    }
}
