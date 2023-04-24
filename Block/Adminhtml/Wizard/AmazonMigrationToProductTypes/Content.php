<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Wizard\AmazonMigrationToProductTypes;

class Content extends \Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock
{
    /** @var \Ess\M2ePro\Helper\Module\Support */
    private $supportHelper;

    /**
     * @param \Ess\M2ePro\Helper\Module\Support $supportHelper
     * @param \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context
     * @param array $data
     */
    public function __construct(
        \Ess\M2ePro\Helper\Module\Support $supportHelper,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->supportHelper = $supportHelper;
    }

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
        return $this->supportHelper->getSupportUrl('/support/solutions/articles/9000225982');
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
