<?php

declare(strict_types=1);

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Account\Edit\Tabs;

class FbaInventory extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm
{
    public const FORM_KEY_FBA_INVENTORY_MODE = 'fba_inventory_mode';
    public const FORM_KEY_FBA_INVENTORY_SOURCE_NAME = 'fba_inventory_source_name';

    /** @var \Ess\M2ePro\Model\Amazon\Account\Builder */
    private $amazonAccountBuilder;
    /** @var \Ess\M2ePro\Helper\Magento */
    private $magentoHelper;
    /** @var \Ess\M2ePro\Helper\Data\GlobalData */
    private $globalDataHelper;
    /** @var \Magento\InventoryApi\Api\SourceRepositoryInterface|null */
    private $sourceRepository;

    public function __construct(
        \Ess\M2ePro\Model\Amazon\Account\Builder $amazonAccountBuilder,
        \Ess\M2ePro\Helper\Magento $magentoHelper,
        \Ess\M2ePro\Helper\Data\GlobalData $globalDataHelper,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        array $data = []
    ) {
        $this->amazonAccountBuilder = $amazonAccountBuilder;
        $this->magentoHelper = $magentoHelper;
        $this->globalDataHelper = $globalDataHelper;
        $this->sourceRepository = null;

        if ($this->magentoHelper->isMSISupportingVersion()) {
            $this->sourceRepository = $objectManager->get(
                \Magento\InventoryApi\Api\SourceRepositoryInterface::class
            );
        }

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
                self::FORM_KEY_FBA_INVENTORY_MODE,
                self::SELECT,
                [
                    'name' => self::FORM_KEY_FBA_INVENTORY_MODE,
                    'label' => __('Enabled'),
                    'values' => [
                        0 => __('No'),
                        1 => __('Yes'),
                    ],
                    'value' => $formData[self::FORM_KEY_FBA_INVENTORY_MODE],
                ]
            );

            $fieldset->addField(
                self::FORM_KEY_FBA_INVENTORY_SOURCE_NAME,
                self::SELECT,
                [
                    'name' => self::FORM_KEY_FBA_INVENTORY_SOURCE_NAME,
                    'label' => __('Source'),
                    'values' => $this->getSourceOptions(),
                    'value' => $formData[self::FORM_KEY_FBA_INVENTORY_SOURCE_NAME],
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

    private function getFormData(): array
    {
        /** @var \Ess\M2ePro\Model\Account $account */
        $account = $this->globalDataHelper->getValue('edit_account');

        $accountFormData = $account ? array_merge($account->toArray(), $account->getChildObject()->toArray()) : [];
        $defaultAccountData = $this->amazonAccountBuilder->getDefaultData();

        return array_merge($defaultAccountData, $accountFormData);
    }

    private function getSourceOptions(): array
    {
        if ($this->sourceRepository === null) {
            return [];
        }

        $sources = $this->sourceRepository->getList()->getItems();
        $sourceOptions = [];

        /** @var \Magento\Inventory\Model\Source $source */
        foreach ($sources as $source) {
            $sourceOptions[$source->getSourceCode()] = $source->getName();
        }

        return $sourceOptions;
    }
}
