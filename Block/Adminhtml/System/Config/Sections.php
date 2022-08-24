<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\System\Config;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm;

class Sections extends AbstractForm
{
    /** @var \Magento\Framework\View\Asset\Repository */
    protected $assetRepo;

    public const SECTION_ID_MODULE_AND_CHANNELS             = 'm2epro_module_and_channels';
    public const SECTION_ID_INTERFACE_AND_MAGENTO_INVENTORY = 'm2epro_interface_and_magento_inventory';
    public const SECTION_ID_LOGS_CLEARING                   = 'm2epro_logs_clearing';
    public const SECTION_ID_LICENSE                         = 'm2epro_extension_key';

    public const SELECT               = \Ess\M2ePro\Block\Adminhtml\System\Config\Form\Element\Select::class;
    public const TEXT                 = \Ess\M2ePro\Block\Adminhtml\System\Config\Form\Element\Text::class;
    public const LINK                 = \Ess\M2ePro\Block\Adminhtml\System\Config\Form\Element\Link::class;
    public const HELP_BLOCK           = \Ess\M2ePro\Block\Adminhtml\Magento\Form\Element\HelpBlock::class;
    public const STATE_CONTROL_BUTTON =
        \Ess\M2ePro\Block\Adminhtml\System\Config\Form\Element\StateControlButton::class;
    public const BUTTON               = \Ess\M2ePro\Block\Adminhtml\System\Config\Form\Element\Button::class;
    public const NOTE                 = \Magento\Framework\Data\Form\Element\Note::class;

    /**
     * @param \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param array $data
     */
    public function __construct(
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        array $data = []
    ) {
        $this->assetRepo = $context->getAssetRepository();
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _toHtml(): string
    {
        $generalBlock = $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\General::class);
        $asset = $this->assetRepo->createAsset("Ess_M2ePro::css/style.css");

        $this->js->addRequireJs(
            ['s' => 'M2ePro/Settings'],
            <<<JS
window.SettingsObj = new Settings();
JS
        );

        $html = <<<HTML
{$generalBlock->toHtml()}
<style>
@import url("{$asset->getUrl()}");
</style>
HTML;

        return $html . parent::_toHtml();
    }
}
