<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\System\Config\Sections;

class Migration extends \Ess\M2ePro\Block\Adminhtml\System\Config\Sections
{
    /** @var \Ess\M2ePro\Helper\Module\Support */
    private $moduleSupport;

    public function __construct(
        \Ess\M2ePro\Helper\Module\Support $moduleSupport,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        array $data = []
    ) {
        parent::__construct($context, $registry, $formFactory, $data);
        $this->moduleSupport = $moduleSupport;
    }

    protected function _prepareForm()
    {
        $form = $this->_formFactory->create();

        $form->addField(
            'migration_helper_block',
            self::HELP_BLOCK,
            [
                'no_collapse' => true,
                'no_hide' => true,
                'content' => $this->__(
                    'Here you can start M2E Pro data migration from Magento v1.x.
                    Read the <a href="%url%" target="_blank">Migration Guide</a>
                    for more details.',
                    $this->moduleSupport->getDocumentationArticleUrl('x/Ov0kB')
                ),
            ]
        );

        $fieldSet = $form->addFieldset(
            'migration_from_magento1',
            [
                'legend' => $this->__('Migration'),
                'collapsable' => false,
            ]
        );

        $fieldSet->addField(
            'migration_button',
            self::BUTTON,
            [
                'label' => 'Migration from Magento v1.x',
                'content' => $this->__('Proceed'),
                'onclick' => 'startMigrationFromMagento1Wizard()',
                'tooltip' => $this->__(
                    'Inventory and Order synchronization stops. The Module interface becomes unavailable.<br>
                            <b>Note</b>: Once you confirm the migration running, it cannot be stopped.'
                ),
            ]
        );

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _toHtml(): string
    {
        $popup = $this->getLayout()
                      ->createBlock(\Ess\M2ePro\Block\Adminhtml\System\Config\Popup\MigrationPopup::class);

        return $popup->toHtml() . parent::_toHtml();
    }
}
