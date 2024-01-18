<?php

declare(strict_types=1);

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Account\Edit\Tabs;

class FbaInventory extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm
{
    /** @var \Magento\Inventory\Model\SourceRepository */
    private $sourceRepository;
    /** @var \Ess\M2ePro\Helper\Magento */
    private $magentoHelper;
    /** @var \Ess\M2ePro\Helper\Data\GlobalData */
    private $globalDataHelper;

    public function __construct(
        \Magento\Inventory\Model\SourceRepository $sourceRepository,
        \Ess\M2ePro\Helper\Magento $magentoHelper,
        \Ess\M2ePro\Helper\Data\GlobalData $globalDataHelper,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        array $data = []
    ) {
        $this->sourceRepository = $sourceRepository;
        $this->magentoHelper = $magentoHelper;
        $this->globalDataHelper = $globalDataHelper;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    protected function _prepareForm()
    {
        $formData = $this->getFormData();

        $form = $this->_formFactory->create();

        $form->addField(
            'fba_inventory_help',
            self::HELP_BLOCK,
            [
                'content' => __(
                    'Here you can enable the automatic synchronization of Amazon FBA Product QTY into a Magento
                    Inventory Source. Once the QTY value of an FBA product is reported by Amazon, it gets imported
                    into the predefined Inventory Source. You may choose to create a new Source if you wouldn`t want
                    the FBA Product stock information to have any effect on your Magento inventory.<br/><br/>
                    To use the FBA stock import, the Inventory Management module (formerly MSI) must be enabled for
                    your Adobe Commerce.'
                ),
            ]
        );

        $fieldset = $form->addFieldset(
            'fba_inventory',
            [
                'legend' => __('FBA to Magento Stock Synchronization'),
                'collapsable' => false,
            ]
        );

        if (!$this->magentoHelper->isMSISupportingVersion()) {
            $fieldset->addField(
                'fba_inventory_message',
                self::MESSAGES,
                [
                    'messages' => [
                        [
                            'type' => \Magento\Framework\Message\MessageInterface::TYPE_NOTICE,
                            'content' => __(
                                'To use the FBA stock import, please make sure the Inventory Management
                            module (formerly MSI) is enabled in your Magento'
                            ),
                        ],
                    ],
                ]
            );
        } else {
            $fieldset->addField(
                'fba_inventory_mode',
                self::SELECT,
                [
                    'name' => 'fba_inventory_mode',
                    'label' => __('Enabled'),
                    'values' => [
                        0 => __('No'),
                        1 => __('Yes'),
                    ],
                    'value' => $formData['fba_inventory_mode'],
                ]
            );

            $fieldset->addField(
                'fba_inventory_source',
                self::SELECT,
                [
                    'name' => 'fba_inventory_source',
                    'label' => __('Source'),
                    'values' => $this->getSourceOptions(),
                    'value' => $formData['fba_inventory_source'],
                    'container_id' => 'fba_inventory_source_tr',
                    'after_element_html' => __(
                        '<a target="_blank" href="%url">Manage Sources</a>',
                        ['url' => $this->_urlBuilder->getUrl('inventory/source/index/')]
                    ),
                    'tooltip' => __(
                        'You may use Manage Sources button to create new or manage existing Inventory Sources.<br>
                    <b>Note</b>: Remember to update this page to see a newly created Source available in the list'
                    ),
                ]
            );
        }

        $form->setValues($formData);

        $form->setUseContainer(false);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    // ----------------------------------------

    private function getFormData()
    {
        /** @var \Ess\M2ePro\Model\Account $account */
        $account = $this->globalDataHelper->getValue('edit_account');

        $formData = $account ? array_merge($account->toArray(), $account->getChildObject()->toArray()) : [];
        $defaults = $this->modelFactory->getObject('Amazon_Account_Builder')->getDefaultData();

        return array_merge($defaults, $formData);
    }

    private function getSourceOptions(): array
    {
        $sources = $this->sourceRepository->getList()->getItems();
        $sourceOptions = [];

        /** @var \Magento\Inventory\Model\SourceRepository $source */
        foreach ($sources as $source) {
            $sourceOptions[$source->getSourceCode()] = $source->getName();
        }

        return $sourceOptions;
    }
}
