<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Settings\Tabs;

use \Ess\M2ePro\Helper\Component\Ebay as EbayHelper;

class Motors extends \Ess\M2ePro\Block\Adminhtml\Settings\Tabs\AbstractTab
{
    /** @var \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory */
    protected $attributeColFactory;

    /** @var \Magento\Framework\App\ResourceConnection */
    protected $resourceConnection;

    /** @var \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory */
    protected $ebayFactory;

    /** @var \Ess\M2ePro\Helper\Component\Ebay\Configuration */
    private $componentEbayConfiguration;

    /** @var \Ess\M2ePro\Helper\Component\Ebay\Motors */
    private $componentEbayMotors;

    /** @var \Ess\M2ePro\Helper\Magento\Attribute */
    private $magentoAttributeHelper;

    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Component\Ebay\Motors $componentEbayMotors,
        \Ess\M2ePro\Helper\Component\Ebay\Configuration $componentEbayConfiguration,
        \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory $attributeColFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Helper\Magento\Attribute $magentoAttributeHelper,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Ess\M2ePro\Helper\Data $dataHelper,
        array $data = []
    ) {
        $this->attributeColFactory        = $attributeColFactory;
        $this->ebayFactory                = $ebayFactory;
        $this->resourceConnection         = $resourceConnection;
        $this->componentEbayConfiguration = $componentEbayConfiguration;
        $this->componentEbayMotors        = $componentEbayMotors;
        $this->magentoAttributeHelper     = $magentoAttributeHelper;
        $this->dataHelper = $dataHelper;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    protected function _prepareForm()
    {
        /** @var \Ess\M2ePro\Helper\Component\Ebay\Motors $eBayMotorsHelper */
        $eBayMotorsHelper = $this->componentEbayMotors;

        /** @var \Ess\M2ePro\Helper\Component\Ebay\Configuration $configurationHelper */
        $configurationHelper = $this->componentEbayConfiguration;

        //----------------------------------------

        $attributes = $this->magentoAttributeHelper->filterByInputTypes(
            $this->magentoAttributeHelper->getAll(),
            ['textarea'],
            ['text']
        );

        $preparedAttributes = ['' => '-- ' . $this->__('Select Attribute') . ' --'];
        foreach ($attributes as $attribute) {
            $preparedAttributes[] = [
                'value' => $attribute['code'],
                'label' => $attribute['label']
            ];
        }

        //----------------------------------------

        $form = $this->_formFactory->create([
            'data' => [
                'method' => 'post',
                'action' => $this->getUrl('*/*/save')
            ]
        ]);

        $motorsMarketplace = $this->ebayFactory->getObjectLoaded('Marketplace', EbayHelper::MARKETPLACE_MOTORS);
        if ($motorsMarketplace->isStatusEnabled()) {
            $fieldset = $form->addFieldset(
                'motors_epids_motor',
                [
                    'legend'      => $this->__('Parts Compatibility [ePIDs Motors]'),
                    'collapsable' => false,
                    'tooltip'     => $this->__(
                        'In this Section, you can provide a Magento Attribute where ePID values for your Products
                         will be saved.
                         <br/>
                         Also you can Add/Update ePID Database manually by clicking <strong>Manage Option</strong>
                         in Database line.'
                    )
                ]
            );

            $fieldset->addField(
                'motors_epids_attribute',
                self::SELECT,
                [
                    'name'    => 'motors_epids_attribute',
                    'label'   => $this->__('Attribute'),
                    'values'  => $preparedAttributes,
                    'value'   => $configurationHelper->getMotorsEpidsAttribute(),
                    'class'   => 'M2ePro-custom-attribute-can-be-created',
                    'tooltip' => $this->__(
                        'Choose the Attribute that contains the Product Reference IDs (ePIDs) of compatible
                         vehicles for the parts.
                         In the M2E Pro Listing, use the <strong>Add Compatible Vehicles</strong> tool to find
                         necessary compatible Items.
                         <br/>
                         Only Textarea Attributes are shown.'
                    )
                ]
            )
                ->addCustomAttribute('allowed_attribute_types', 'textarea')
                ->addCustomAttribute('apply_to_all_attribute_sets', 'false');

            $motorsType = \Ess\M2ePro\Helper\Component\Ebay\Motors::TYPE_EPID_MOTOR;
            $popupTitle = $this->__('Manage Custom Compatibility [ePIDs Motor]');
            [$count, $customCount] = $eBayMotorsHelper->getDictionaryRecordCount(
                \Ess\M2ePro\Helper\Component\Ebay\Motors::TYPE_EPID_MOTOR
            );

            $fieldset->addField(
                'motors_epids_motor_database',
                self::CUSTOM_CONTAINER,
                [
                    'label' => $this->__('Database'),
                    'text' => <<<HTML
<span style="padding-right: 2px;">{$this->__('From eBay')}: </span>
<span style="font-weight: bold; display: inline-block; width: 40px;">{$count}</span>

<span style="padding-right: 2px; padding-left: 10px;">{$this->__('Custom Added')}: </span>
<span id="epids_motor_custom_count" style="font-weight: bold; padding-right: 2px;">{$customCount}</span>

<span>
    (<a href="javascript:void(0);"
        onclick="EbaySettingsMotorsObj.manageMotorsRecords('{$motorsType}','{$popupTitle}');">{$this->__('manage')}</a>)
</span>
HTML
                ]
            );
        }

        $ukMarketplace = $this->ebayFactory->getObjectLoaded('Marketplace', EbayHelper::MARKETPLACE_UK);
        if ($ukMarketplace->isStatusEnabled()) {
            $fieldset = $form->addFieldset(
                'motors_epids_uk',
                [
                    'legend'      => $this->__('Parts Compatibility [ePIDs UK]'),
                    'collapsable' => false,
                    'tooltip'     => $this->__(
                        'In this Section, you can provide a Magento Attribute where ePID values for UK marketplace
                        will be saved.
                        <br/>
                        Also you can Add/Update ePID Database manually by clicking <strong>Manage Option</strong>
                        in Database line.
                        <br/>
                        You have an ability to choose either ePID or kType values should be used on eBay UK.
                        Specify the appropriate <strong>Parts Compatibility Mode</strong> for your Listing
                        in M2E Pro Listings grid.'
                    )
                ]
            );

            $fieldset->addField(
                'uk_epids_attribute',
                self::SELECT,
                [
                    'name'    => 'uk_epids_attribute',
                    'label'   => $this->__('Attribute'),
                    'values'  => $preparedAttributes,
                    'value'   => $configurationHelper->getUkEpidsAttribute(),
                    'class'   => 'M2ePro-custom-attribute-can-be-created',
                    'tooltip' => $this->__(
                        'Choose the Attribute that contains the Product Reference IDs (ePIDs) of compatible vehicles
                         for the parts.
                         In the M2E Pro Listing, use the <strong>Add Compatible Vehicles</strong> tool to find
                         necessary compatible Items.
                         <br/>
                         Only Textarea Attributes are shown.'
                    )
                ]
            )
                ->addCustomAttribute('allowed_attribute_types', 'textarea')
                ->addCustomAttribute('apply_to_all_attribute_sets', 'false');

            $motorsType = \Ess\M2ePro\Helper\Component\Ebay\Motors::TYPE_EPID_UK;
            $popupTitle = $this->__('Manage Custom Compatibility [ePIDs UK]');
            [$count, $customCount] = $eBayMotorsHelper->getDictionaryRecordCount(
                \Ess\M2ePro\Helper\Component\Ebay\Motors::TYPE_EPID_UK
            );

            $fieldset->addField(
                'motors_epids_uk_database',
                self::CUSTOM_CONTAINER,
                [
                    'label' => $this->__('Database'),
                    'text' => <<<HTML
<span style="padding-right: 2px;">{$this->__('From eBay')}: </span>
<span style="font-weight: bold; display: inline-block; width: 40px;">{$count}</span>

<span style="padding-right: 2px; padding-left: 10px;">{$this->__('Custom Added')}: </span>
<span id="epids_uk_custom_count" style="font-weight: bold; padding-right: 2px;">{$customCount}</span>

<span>
    (<a href="javascript:void(0);"
        onclick="EbaySettingsMotorsObj.manageMotorsRecords('{$motorsType}','{$popupTitle}');">{$this->__('manage')}</a>)
</span>
HTML
                ]
            );
        }

        $deMarketplace = $this->ebayFactory->getObjectLoaded('Marketplace', EbayHelper::MARKETPLACE_DE);
        if ($deMarketplace->isStatusEnabled()) {
            $fieldset = $form->addFieldset(
                'motors_epids_de',
                [
                    'legend'      => $this->__('Parts Compatibility [ePIDs DE]'),
                    'collapsable' => false,
                    'tooltip'     => $this->__(
                        'In this Section, you can provide a Magento Attribute where ePID values for DE marketplace
                        will be saved.
                        <br/>
                        Also you can Add/Update ePID Database manually by clicking <strong>Manage Option</strong>
                        in Database line.
                        <br/>
                        You have an ability to choose either ePID or kType values should be used on eBay DE.
                        Specify the appropriate <strong>Parts Compatibility Mode</strong> for your Listing in M2E Pro
                        Listings grid.'
                    )
                ]
            );

            $fieldset->addField(
                'de_epids_attribute',
                self::SELECT,
                [
                    'name'    => 'de_epids_attribute',
                    'label'   => $this->__('Attribute'),
                    'values'  => $preparedAttributes,
                    'value'   => $configurationHelper->getDeEpidsAttribute(),
                    'class'   => 'M2ePro-custom-attribute-can-be-created',
                    'tooltip' => $this->__(
                        'Choose the Attribute that contains the Product Reference IDs (ePIDs) of compatible vehicles
                         for the parts.
                         In the M2E Pro Listing, use the <strong>Add Compatible Vehicles</strong> tool to find
                         necessary compatible Items.
                         <br/>
                         Only Textarea Attributes are shown.'
                    )
                ]
            )
                ->addCustomAttribute('allowed_attribute_types', 'textarea')
                ->addCustomAttribute('apply_to_all_attribute_sets', 'false');

            $motorsType = \Ess\M2ePro\Helper\Component\Ebay\Motors::TYPE_EPID_DE;
            $popupTitle = $this->__('Manage Custom Compatibility [ePIDs DE]');
            [$count, $customCount] = $eBayMotorsHelper->getDictionaryRecordCount(
                \Ess\M2ePro\Helper\Component\Ebay\Motors::TYPE_EPID_DE
            );

            $fieldset->addField(
                'motors_epids_de_database',
                self::CUSTOM_CONTAINER,
                [
                    'label' => $this->__('Database'),
                    'text' => <<<HTML
<span style="padding-right: 2px;">{$this->__('From eBay')}: </span>
<span style="font-weight: bold; display: inline-block; width: 40px;">{$count}</span>

<span style="padding-right: 2px; padding-left: 10px;">{$this->__('Custom Added')}: </span>
<span id="epids_de_custom_count" style="font-weight: bold; padding-right: 2px;">{$customCount}</span>

<span>
    (<a href="javascript:void(0);"
        onclick="EbaySettingsMotorsObj.manageMotorsRecords('{$motorsType}','{$popupTitle}');">{$this->__('manage')}</a>)
</span>
HTML
                ]
            );
        }

        $auMarketplace = $this->ebayFactory->getObjectLoaded('Marketplace', EbayHelper::MARKETPLACE_AU);
        if ($auMarketplace->isStatusEnabled()) {
            $fieldset = $form->addFieldset(
                'motors_epids_au',
                [
                    'legend'      => $this->__('Parts Compatibility [ePIDs AU]'),
                    'collapsable' => false,
                    'tooltip'     => $this->__(
                        'In this Section, you can provide a Magento Attribute where ePID values for AU marketplace
                        will be saved.
                        <br/>
                        Also you can Add/Update ePID Database manually by clicking <strong>Manage Option</strong>
                        in Database line.
                        <br/>
                        You have an ability to choose either ePID or kType values should be used on eBay AU.
                        Specify the appropriate <strong>Parts Compatibility Mode</strong> for your Listing in M2E Pro
                        Listings grid.'
                    )
                ]
            );

            $fieldset->addField(
                'au_epids_attribute',
                self::SELECT,
                [
                    'name'    => 'au_epids_attribute',
                    'label'   => $this->__('Attribute'),
                    'values'  => $preparedAttributes,
                    'value'   => $configurationHelper->getAuEpidsAttribute(),
                    'class'   => 'M2ePro-custom-attribute-can-be-created',
                    'tooltip' => $this->__(
                        'Choose the Attribute that contains the Product Reference IDs (ePIDs) of compatible vehicles
                         for the parts.
                         In the M2E Pro Listing, use the <strong>Add Compatible Vehicles</strong> tool to find
                         necessary compatible Items.
                         <br/>
                         Only Textarea Attributes are shown.'
                    )
                ]
            )
                ->addCustomAttribute('allowed_attribute_types', 'textarea')
                ->addCustomAttribute('apply_to_all_attribute_sets', 'false');

            $motorsType = \Ess\M2ePro\Helper\Component\Ebay\Motors::TYPE_EPID_AU;
            $popupTitle = $this->__('Manage Custom Compatibility [ePIDs AU]');
            [$count, $customCount] = $eBayMotorsHelper->getDictionaryRecordCount(
                \Ess\M2ePro\Helper\Component\Ebay\Motors::TYPE_EPID_AU
            );

            $fieldset->addField(
                'motors_epids_au_database',
                self::CUSTOM_CONTAINER,
                [
                    'label' => $this->__('Database'),
                    'text' => <<<HTML
<span style="padding-right: 2px;">{$this->__('From eBay')}: </span>
<span style="font-weight: bold; display: inline-block; width: 40px;">{$count}</span>

<span style="padding-right: 2px; padding-left: 10px;">{$this->__('Custom Added')}: </span>
<span id="epids_au_custom_count" style="font-weight: bold; padding-right: 2px;">{$customCount}</span>

<span>
    (<a href="javascript:void(0);"
        onclick="EbaySettingsMotorsObj.manageMotorsRecords('{$motorsType}','{$popupTitle}');">{$this->__('manage')}</a>)
</span>
HTML
                ]
            );
        }

        $itMarketplace = $this->ebayFactory->getObjectLoaded('Marketplace', EbayHelper::MARKETPLACE_IT);
        if ($itMarketplace->isStatusEnabled()) {
            $fieldset = $form->addFieldset(
                'motors_epids_it',
                [
                    'legend'      => $this->__('Parts Compatibility [ePIDs IT]'),
                    'collapsable' => false,
                    'tooltip'     => $this->__(
                        'In this Section, you can provide a Magento Attribute where ePID values for IT marketplace
                        will be saved.
                        <br/>
                        Also you can Add/Update ePID Database manually by clicking <strong>Manage Option</strong>
                        in Database line.
                        <br/>
                        You have an ability to choose either ePID or kType values should be used on eBay IT.
                        Specify the appropriate <strong>Parts Compatibility Mode</strong> for your Listing in M2E Pro
                        Listings grid.'
                    )
                ]
            );

            $fieldset->addField(
                'it_epids_attribute',
                self::SELECT,
                [
                    'name'    => 'it_epids_attribute',
                    'label'   => $this->__('Attribute'),
                    'values'  => $preparedAttributes,
                    'value'   => $configurationHelper->getItEpidsAttribute(),
                    'class'   => 'M2ePro-custom-attribute-can-be-created',
                    'tooltip' => $this->__(
                        'Choose the Attribute that contains the Product Reference IDs (ePIDs) of compatible vehicles
                         for the parts.
                         In the M2E Pro Listing, use the <strong>Add Compatible Vehicles</strong> tool to find
                         necessary compatible Items.
                         <br/>
                         Only Textarea Attributes are shown.'
                    )
                ]
            )
                     ->addCustomAttribute('allowed_attribute_types', 'textarea')
                     ->addCustomAttribute('apply_to_all_attribute_sets', 'false');

            $motorsType = \Ess\M2ePro\Helper\Component\Ebay\Motors::TYPE_EPID_IT;
            $popupTitle = $this->__('Manage Custom Compatibility [ePIDs IT]');
            [$count, $customCount] = $eBayMotorsHelper->getDictionaryRecordCount(
                \Ess\M2ePro\Helper\Component\Ebay\Motors::TYPE_EPID_IT
            );

            $fieldset->addField(
                'motors_epids_it_database',
                self::CUSTOM_CONTAINER,
                [
                    'label' => $this->__('Database'),
                    'text' => <<<HTML
<span style="padding-right: 2px;">{$this->__('From eBay')}: </span>
<span style="font-weight: bold; display: inline-block; width: 40px;">{$count}</span>

<span style="padding-right: 2px; padding-left: 10px;">{$this->__('Custom Added')}: </span>
<span id="epids_it_custom_count" style="font-weight: bold; padding-right: 2px;">{$customCount}</span>

<span>
    (<a href="javascript:void(0);"
        onclick="EbaySettingsMotorsObj.manageMotorsRecords('{$motorsType}','{$popupTitle}');">{$this->__('manage')}</a>)
</span>
HTML
                ]
            );
        }

        if ($eBayMotorsHelper->isKTypeMarketplacesEnabled()) {
            $fieldset = $form->addFieldset(
                'motors_ktypes',
                [
                    'legend'      => $this->__('Parts Compatibility [kTypes]'),
                    'collapsable' => false,
                    'tooltip'     => $this->__(
                        'In this Section, you can provide a Magento Attribute where kType values for your Products
                        will be saved.
                        <br/>
                        Also you can Add/Update kType Database manually by clicking <strong>Manage Option</strong>
                        in Database line.'
                    )
                ]
            );

            $fieldset->addField(
                'ktypes_attribute',
                self::SELECT,
                [
                    'name'    => 'ktypes_attribute',
                    'label'   => $this->__('Attribute'),
                    'values'  => $preparedAttributes,
                    'value'   => $configurationHelper->getKTypesAttribute(),
                    'class'   => 'M2ePro-custom-attribute-can-be-created',
                    'tooltip' => $this->__(
                        'Choose the Attribute that contains the kTypes of compatible vehicles for the parts.
                         In the M2E Pro Listing, use the <strong>Add Compatible Vehicles</strong> tool to find
                         necessary compatible Items.
                         <br/>
                         Only Text Area Attributes are shown. If you configure kTypes via Magento attributes, make sure
                         its input type (under attribute properties) is set to Text Area.'
                    )
                ]
            )
                ->addCustomAttribute('allowed_attribute_types', 'textarea')
                ->addCustomAttribute('apply_to_all_attribute_sets', 'false');

            $motorsType = \Ess\M2ePro\Helper\Component\Ebay\Motors::TYPE_KTYPE;
            $popupTitle = $this->__('Manage Custom Compatibility [kTypes]');
            [$count, $customCount] = $eBayMotorsHelper->getDictionaryRecordCount(
                \Ess\M2ePro\Helper\Component\Ebay\Motors::TYPE_KTYPE
            );

            $fieldset->addField(
                'motors_ktypes_database',
                self::CUSTOM_CONTAINER,
                [
                    'label' => $this->__('Database'),
                    'text' => <<<HTML
<span style="padding-right: 2px;">{$this->__('From eBay')}: </span>
<span style="font-weight: bold; display: inline-block; width: 40px;">{$count}</span>

<span style="padding-right: 2px; padding-left: 10px;">{$this->__('Custom Added')}: </span>
<span id="ktypes_custom_count" style="font-weight: bold; padding-right: 2px;">{$customCount}</span>

<span>
    (<a href="javascript:void(0);"
        onclick="EbaySettingsMotorsObj.manageMotorsRecords('{$motorsType}','{$popupTitle}');">{$this->__('manage')}</a>)
</span>
HTML
                ]
            );
        }

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    //########################################

    protected function _beforeToHtml()
    {
        $this->jsUrl->add(
            $this->getUrl('*/ebay_settings_motors/save'),
            \Ess\M2ePro\Block\Adminhtml\Ebay\Settings\Tabs::TAB_ID_MOTORS
        );

        $this->jsPhp->addConstants(
            $this->dataHelper->getClassConstants(\Ess\M2ePro\Helper\Component\Ebay\Motors::class)
        );

        $this->jsUrl->addUrls($this->dataHelper->getControllerActions('Ebay_Settings_Motors'));

        $this->js->add(<<<JS
    require([
        'M2ePro/Ebay/Settings/Motors'
    ], function(){
        window.EbaySettingsMotorsObj = new EbaySettingsMotors();
    });
JS
        );

        return parent::_beforeToHtml();
    }

    //########################################

    protected function getGlobalNotice()
    {
        return '';
    }

    //########################################
}
