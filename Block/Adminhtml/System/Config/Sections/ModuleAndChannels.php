<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\System\Config\Sections;

class ModuleAndChannels extends \Ess\M2ePro\Block\Adminhtml\System\Config\Sections
{
    /** @var \Ess\M2ePro\Helper\Module\Cron */
    private $cronHelper;
    /** @var \Ess\M2ePro\Helper\Module */
    private $moduleHelper;
    /** @var \Ess\M2ePro\Helper\Module\Support */
    private $moduleSupport;

    /**
     * @param \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Ess\M2ePro\Helper\Module\Cron $cronHelper
     * @param \Ess\M2ePro\Helper\Module $moduleHelper
     * @param array $data
     */
    public function __construct(
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Ess\M2ePro\Helper\Module\Cron $cronHelper,
        \Ess\M2ePro\Helper\Module $moduleHelper,
        \Ess\M2ePro\Helper\Module\Support $moduleSupport,
        array $data = []
    ) {
        $this->cronHelper = $cronHelper;
        $this->moduleHelper = $moduleHelper;
        $this->moduleSupport = $moduleSupport;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    protected function _prepareForm()
    {
        $form = $this->_formFactory->create();

        $form->addField(
            'module_and_channels_help',
            self::HELP_BLOCK,
            [
                'no_collapse' => true,
                'no_hide'     => true,
                'content'     => $this->__(
                    <<<HTML
<p>Here you can manage the Module and Automatic Synchronization running, enable Channels you want to sell on.
Read the <a href="%url%" target="_blank">article</a> for more details.</p>
HTML
                    ,
                    $this->moduleSupport->getDocumentationArticleUrl('x/KX50B')
                )
            ]
        );

        $fieldSet = $form->addFieldset(
            'configuration_settings_module',
            [
                'legend'      => $this->__('Module'),
                'collapsable' => false
            ]
        );

        $isCronEnabled = (int)$this->cronHelper->isModeEnabled();
        $isModuleEnabled = (int)!$this->moduleHelper->isDisabled();

        if ($isModuleEnabled) {
            $fieldSet->addField(
                'cron_mode_field',
                self::STATE_CONTROL_BUTTON,
                [
                    'name'    => 'groups[module_mode][fields][cron_mode_field][value]',
                    'label'   => $this->__('Automatic Synchronization'),
                    'content' => $isCronEnabled ? 'Disable' : 'Enable',
                    'value'   => $isCronEnabled,
                    'tooltip' => $this->__(
                        'Inventory and Order synchronization stops. The Module interface remains available.'
                    ),
                    'onclick' => 'toggleCronStatus()',
                ]
            );
        }

        $fieldSet->addField(
            'module_mode_field',
            self::STATE_CONTROL_BUTTON,
            [
                'name'    => 'groups[module_mode][fields][module_mode_field][value]',
                'label'   => $this->__('Module Interface and Automatic Synchronization'),
                'content' => $isModuleEnabled ? 'Disable' : 'Enable',
                'value'   => $isModuleEnabled,
                'tooltip' => $this->__(
                    'Inventory and Order synchronization stops. The Module interface becomes unavailable.'
                ),
                'onclick' => 'toggleM2EProModuleStatus()',
            ]
        );

        $fieldSet = $form->addFieldset(
            'configuration_settings_channels',
            [
                'legend'      => $this->__('Channels'),
                'collapsable' => false
            ]
        );

        $isEbayEnabled = $this->moduleHelper->getConfig()->getGroupValue(
            '/component/ebay/',
            'mode'
        );
        $fieldSet->addField(
            'ebay_mode_field',
            self::STATE_CONTROL_BUTTON,
            [
                'name'    => 'groups[channels][fields][ebay_mode_field][value]',
                'label'   => $this->__('eBay'),
                'content' => $isEbayEnabled ? 'Disable' : 'Enable',
                'value'   => $isEbayEnabled,
                'tooltip' => $this->__('eBay Channel Status.'),
                'onclick' => 'toggleEbayStatus()',
            ]
        );

        $isAmazonEnabled = $this->moduleHelper->getConfig()->getGroupValue(
            '/component/amazon/',
            'mode'
        );
        $fieldSet->addField(
            'amazon_mode_field',
            self::STATE_CONTROL_BUTTON,
            [
                'name'    => 'groups[channels][fields][amazon_mode_field][value]',
                'label'   => $this->__('Amazon'),
                'content' => $isAmazonEnabled ? 'Disable' : 'Enable',
                'value'   => $isAmazonEnabled,
                'tooltip' => $this->__('Amazon Channel Status.'),
                'onclick' => 'toggleAmazonStatus()',
            ]
        );

        $isWalmartEnabled = $this->moduleHelper->getConfig()->getGroupValue(
            '/component/walmart/',
            'mode'
        );
        $fieldSet->addField(
            'walmart_mode_field',
            self::STATE_CONTROL_BUTTON,
            [
                'name'    => 'groups[channels][fields][walmart_mode_field][value]',
                'label'   => $this->__('Walmart'),
                'content' => $isWalmartEnabled ? 'Disable' : 'Enable',
                'value'   => $isWalmartEnabled,
                'tooltip' => $this->__('Walmart Channel Status.'),
                'onclick' => 'toggleWalmartStatus()',
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
            ->createBlock(\Ess\M2ePro\Block\Adminhtml\System\Config\Popup\ModuleControlPopup::class);

        return $popup->toHtml() . parent::_toHtml();
    }

    protected function _prepareLayout()
    {
        parent::_prepareLayout();

        $this->js->add(
            <<<JS
toggleStatus = function (objectId) {
    var field = $(objectId);
    field.value = (field.value === '0') ? '1' : '0';
    $('save').click();
}
toggleCronStatus = function () {
    toggleStatus('cron_mode_field');
}
toggleEbayStatus = function () {
    toggleStatus('ebay_mode_field');
}
toggleAmazonStatus = function () {
    toggleStatus('amazon_mode_field');
}
toggleWalmartStatus = function () {
    toggleStatus('walmart_mode_field');
}
JS
        );
    }
}
