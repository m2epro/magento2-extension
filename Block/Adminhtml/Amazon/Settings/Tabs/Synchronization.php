<?php

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Settings\Tabs;

use Ess\M2ePro\Block\Adminhtml\Amazon\Settings\Tabs;

class Synchronization extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm
{
    private \Ess\M2ePro\Helper\Data $dataHelper;
    private \Ess\M2ePro\Model\Config\ListingSynchronization $listingSynchronizationConfig;

    public function __construct(
        \Ess\M2ePro\Model\Config\ListingSynchronization $listingSynchronizationConfig,
        \Ess\M2ePro\Helper\Data $dataHelper,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        array $data = []
    ) {
        $this->listingSynchronizationConfig = $listingSynchronizationConfig;
        $this->dataHelper = $dataHelper;

        parent::__construct($context, $registry, $formFactory, $data);
    }

    protected function _prepareForm()
    {
        $form = $this->_formFactory->create(
            [
                'data' => [
                    'enctype' => 'multipart/form-data',
                    'method' => 'post',
                ],
            ]
        );

        $fieldset = $form->addFieldset(
            'amazon_synchronization_templates',
            [
                'legend' => __('M2E Pro Listings Synchronization'),
                'collapsable' => false,
            ]
        );

        $fieldset->addField(
            'instructions_mode',
            self::SELECT,
            [
                'name' => 'instructions_mode',
                'label' => __('Enabled'),
                'values' => [
                    0 => __('No'),
                    1 => __('Yes'),
                ],
                'value' => $this->listingSynchronizationConfig->getComponentMode(
                    \Ess\M2ePro\Helper\Component\Amazon::NICK
                ),
                'tooltip' => __(
                    '<p>This synchronization includes import of changes made on Amazon channel as well
                    as the ability to enable/disable the data synchronization managed by the
                    Synchronization Policy Rules.</p><br>
                    <p>However, it does not exclude the ability to manually manage Items in Listings using the
                    available List, Revise, Relist or Stop Action options.</p>'
                ),
            ]
        );

        $sectionUrl = $this
            ->_urlBuilder
            ->getUrl(
                'adminhtml/system_config/edit/section/'
                . \Ess\M2ePro\Block\Adminhtml\System\Config\Sections::SECTION_ID_INTERFACE_AND_MAGENTO_INVENTORY
            );

        $text = __(
            'You can enable the Product QTY and Price tracker <a target="_blank" href="%url">here</a>.',
            ['url' => $sectionUrl]
        );

        $fieldset->addField(
            'enhanced-inventory-tracker-message',
            self::MESSAGES,
            [
                'messages' => [
                    [
                        'type' => \Magento\Framework\Message\MessageInterface::TYPE_NOTICE,
                        'content' => $text,
                    ],
                ],
            ]
        );

        $form->setUseContainer(true);
        $this->setForm($form);

        parent::_prepareForm();
    }

    protected function _toHtml()
    {
        $js = "require([
                'M2ePro/Synchronization'
            ], function() {

            SynchronizationObj = new Synchronization();";

        $js .= '})';

        $this->js->addOnReadyJs($js);

        $this->jsTranslator->addTranslations(
            [
                'Synchronization Settings have been saved.' => 'Synchronization Settings have been saved.',
            ]
        );

        $this->jsUrl->addUrls([
            Tabs::TAB_ID_SYNCHRONIZATION => $this->getUrl('*/amazon_synchronization/save'),
            'synch_formSubmit' => $this->getUrl('*/amazon_synchronization/save'),
            'logViewUrl' => $this->getUrl('*/amazon_synchronization_log/index', [
                'back' => $this->dataHelper
                    ->makeBackUrlParam('*/amazon_synchronization/index'),
            ]),
        ]);

        return parent::_toHtml();
    }

    protected function getGlobalNotice()
    {
        return '';
    }
}
