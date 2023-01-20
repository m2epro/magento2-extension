<?php

namespace Ess\M2ePro\Block\Adminhtml\ControlPanel\Tabs\ChangeTracker;

class SettingsForm extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm
{
    /**
     * @var \Ess\M2ePro\Helper\Module\ChangeTracker
     */
    private $changeTrackerHelper;
    /**
     * @var \Ess\M2ePro\Helper\Module\Configuration
     */
    private $moduleConfiguration;

    public function __construct(
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Ess\M2ePro\Helper\Module\ChangeTracker $changeTrackerHelper,
        \Ess\M2ePro\Helper\Module\Configuration $moduleConfiguration,
        array $data = []
    ) {
        parent::__construct($context, $registry, $formFactory, $data);
        $this->changeTrackerHelper = $changeTrackerHelper;
        $this->moduleConfiguration = $moduleConfiguration;
    }

    protected function _prepareForm()
    {
        $form = $this->_formFactory->create([
            'data' => ['id' => 'change_tracker_config_form'],
        ]);
        $form->setUseContainer(true);
        $fieldset = $form->addFieldset('base', []);

        $fieldset->addField(
            'change_tracker_status',
            \Magento\Framework\Data\Form\Element\Select::class,
            [
                'label' => 'Enabled Smart Tracker',
                'name' => 'change_tracker_status',
                'values' => [
                    ['value' => 0, 'label' => $this->__('No')],
                    ['value' => 1, 'label' => $this->__('Yes')],
                ],
                'value' => $this->changeTrackerHelper->getStatus(),
            ]
        );

        $fieldset->addField(
            'track_direct_status',
            \Magento\Framework\Data\Form\Element\Select::class,
            [
                'label' => $this->__('Track Direct Status'),
                'name' => 'track_direct_status',
                'values' => [
                    ['value' => 0, 'label' => $this->__('No')],
                    ['value' => 1, 'label' => $this->__('Yes')],
                ],
                'value' => (int)$this->moduleConfiguration->isEnableListingProductInspectorMode(),
            ]
        );

        $fieldset->addField(
            'timeout',
            \Magento\Framework\Data\Form\Element\Text::class,
            [
                'name' => 'timeout',
                'label' => $this->__('Run interval, seconds'),
                'value' => $this->changeTrackerHelper->getInterval(),
            ]
        );

        $fieldset->addField(
            'log_level',
            \Magento\Framework\Data\Form\Element\Select::class,
            [
                'name' => 'log_level',
                'label' => $this->__('Log Level'),
                'values' => [
                    100 => 'Debug',
                    200 => 'Info',
                    300 => 'Notice',
                ],
                'value' => $this->changeTrackerHelper->getLogLevel(),
            ]
        );

        $fieldset->addField(
            'save_btn',
            \Magento\Framework\Data\Form\Element\Button::class,
            [
                'value' => 'Save',
                'class' => 'save_btn',
                'onclick' => 'ChangeTracker.saveConfig()',
            ]
        );

        $this->setForm($form);

        return parent::_prepareForm();
    }

    protected function _beforeToHtml()
    {
        $this->registerJs();
        $this->registerCss();

        return parent::_beforeToHtml();
    }

    private function registerJs(): void
    {
        $this->jsUrl->addUrls([
            'changeTracker/changeStatus' => $this->getUrl('m2epro/controlPanel_changeTracker/changeStatus'),
        ]);

        $js = <<<JS
require([
    'jquery',
    'M2ePro/Plugin/Messages'
], function($, message) {
    window.ChangeTracker = Class.create({
        saveConfig: function () {
            var form = document.getElementById('change_tracker_config_form');
            new Ajax.Request(M2ePro.url.get('changeTracker/changeStatus'), {
                method: 'post',
                asynchronous: true,
                parameters: Object.fromEntries(new FormData(form)),
                onSuccess: function(transport) {
                    message.clearAll();
                    let response = JSON.parse(transport.responseText);
                    if (response.error === true) {
                        message.addError(response.message);
                    } else {
                        message.addSuccess(response.message);
                    }

                    setTimeout(function () {
                        message.clearAll()
                    }, 3000)
                }
            });
        }
    });

    window.ChangeTracker = new ChangeTracker();
});
JS;

        $this->js->addRequireJs([], $js);
    }

    /**
     * @return void
     */
    private function registerCss(): void
    {
        $css = <<<CSS
.save_btn {
    background-color: #eb5202;
    border: 1px solid #eb5202;
    color: #ffffff;
    text-shadow: 1px 1px 0 rgb(0 0 0 / 25%);
    font-size: 16px;
    font-weight: 600;
    letter-spacing: .4px;
    padding: 11px 16px;
}
.save_btn:hover {
    background-color: #ba4000;
}
CSS;

        $this->css->add($css);
    }
}
