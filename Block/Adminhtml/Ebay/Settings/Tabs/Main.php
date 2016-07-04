<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Settings\Tabs;

class Main extends \Ess\M2ePro\Block\Adminhtml\Settings\Tabs\AbstractTab
{
    /** @var \Ess\M2ePro\Model\Config\Manager\Cache  */
    protected $cacheConfig;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\Config\Manager\Cache $cacheConfig,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        array $data = []
    )
    {
        $this->cacheConfig = $cacheConfig;

        parent::__construct($context, $registry, $formFactory, $data);
    }

    protected function _prepareForm()
    {
        $configModel = $this->getHelper('Module')->getConfig();

        $useLastSpecificsMode = (bool)(int)$configModel->getGroupValue(
            '/view/ebay/template/category/','use_last_specifics'
        );
        $checkTheSameProductAlreadyListedMode = (bool)(int)$configModel->getGroupValue(
            '/ebay/connector/listing/','check_the_same_product_already_listed'
        );

        $uploadImagesMode = (int)$configModel->getGroupValue(
            '/ebay/description/','upload_images_mode'
        );

        $form = $this->_formFactory->create([
            'data' => [
                'method' => 'post',
                'action' => $this->getUrl('*/*/save')
            ]
        ]);

        $fieldset = $form->addFieldset('selling',
            [
                'legend' => $this->__('Listing'),
                'collapsable' => false,
            ]
        );

        $fieldset->addField('use_last_specifics_mode',
            'select',
            [
                'name'        => 'use_last_specifics_mode',
                'label'       => $this->__('Item Specifics Step'),
                'values' => [
                    0 => $this->__('Show'),
                    1 => $this->__('Do Not Show')
                ],
                'value' => $useLastSpecificsMode,
                'tooltip' => $this->__(
                    'Choose <b>Do Not Show</b>
                    if you don\'t need to edit Item specifics details every time you add Products.<br/>'
                )
            ]
        );

        $fieldset = $form->addFieldset('additional',
            [
                'legend' => $this->__('Additional'),
                'collapsable' => false,
            ]
        );

        $multiCurrency = $this->getMultiCurrency();
        if (!empty($multiCurrency)) {
            foreach ($multiCurrency as $marketplace => $data) {
                $currencies = explode(',', $data['currency']);

                $preparedValues = [];
                $selectedValue = '';
                foreach ($currencies as $currency) {
                    if ($this->isCurrencyForCode($data['code'], $currency)) {
                        $selectedValue = $currency;
                    }
                    $preparedValues[$currency] = $currency;
                }

                $fieldset->addField('selling_currency' . $data['code'],
                    'select',
                    [
                        'name' => 'selling_currency' . $data['code'],
                        'label' => $this->__($marketplace) . ' ' . $this->__('Currency'),
                        'values' => $preparedValues,
                        'value' => $selectedValue,
                        'tooltip' => $this->__('Choose the Currency you want to sell for.')
                    ]
                );
            }
        }

        $fieldset->addField('check_the_same_product_already_listed_mode',
            'select',
            [
                'name'        => 'check_the_same_product_already_listed_mode',
                'label'       => $this->__('Prevent eBay Item Duplicates'),
                'values' => [
                    0 => $this->__('No'),
                    1 => $this->__('Yes'),
                ],
                'value' => $checkTheSameProductAlreadyListedMode,
                'tooltip' => $this->__(
                    '<p>Choose \'Yes\' to prevent M2E Pro from adding a Product if it 
                     <p>Essentially, this option is useful if you have Automatic Add/Remove Rules set up.
                     It will ensure that each Product is listed only once, when Products are added 
                     to the Listing automatically.</p><br/>
                     <p><strong>Note:</strong> Applies only to Products Listed automatically on live Marketplaces 
                     (i.e. not using a Sandbox Account).</p>'
                )
            ]
        );

        $fieldset->addField('upload_images_mode',
            'select',
            [
                'name'   => 'upload_images_mode',
                'label'  => $this->__('Upload Images to eBay'),
                'values' => [
                    \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Request\Description::UPLOAD_IMAGES_MODE_AUTO
                        => $this->__('Automatic'),
                    \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Request\Description::UPLOAD_IMAGES_MODE_SELF
                        => $this->__('Self-Hosted'),
                    \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Request\Description::UPLOAD_IMAGES_MODE_EPS
                        => $this->__('EPS-Hosted'),
                ],
                'value' => $uploadImagesMode,
                'tooltip' => $this->__('
                    Select the Mode which you would like to use for uploading Images on eBay:<br/><br/>
                    <strong>Automatic</strong> — if you try to upload more then 1 Image for an Item or
                    separate Variational Attribute the EPS-hosted mode will be used automatically.
                    Otherwise, the Self-hosted mode will be used automatically;<br/>
                    <strong>Self-Hosted</strong> — all the Images are provided as a direct Links to the
                    Images saved in your Magento;<br/>
                    <strong>EPS-Hosted</strong> — the Images are uploaded to eBay EPS service.
                ')
            ]
        );

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    protected function _prepareLayout()
    {
        // TODO NOT SUPPORTED FEATURES
        /*        $this->view_ebay_feedbacks_notification_mode = (bool)(int)$this->cacheConfig->getGroupValue(
                    '/view/ebay/feedbacks/notification/','mode'
                );

                $this->is_ebay_feedbacks_enabled = $this->getHelper('View\Ebay')->isFeedbacksShouldBeShown();*/

        // TODO NOT SUPPORTED FEATURES
        /** @var \Ess\M2ePro\Helper\Component\Ebay\Motors $motorsHelper */
        /*      $motorsHelper = $this->getHelper('Component\Ebay\Motors');

              $resource = Mage::getSingleton('core/resource');
              $epidsDictionaryTable = $resource->getTableName('m2epro_ebay_dictionary_motor_epid');
              $ktypeDictionaryTable = $resource->getTableName('m2epro_ebay_dictionary_motor_ktype');*/

        // ---------------------------------------
        /** @var \Ess\M2ePro\Model\ResourceModel\Marketplace\Collection $epidsMarketplaceCollection */
        /*$epidsMarketplaceCollection = Mage::getModel('M2ePro/Marketplace')->getCollection();
        $epidsMarketplaceCollection->addFieldToFilter(
            'id',
            array('in' => $motorsHelper->getEpidSupportedMarketplaces())
        );
        $epidsMarketplaceCollection->addFieldToFilter('status', Ess_M2ePro_Model_Marketplace::STATUS_ENABLE);
        $this->is_motors_epids_marketplace_enabled = (bool)$epidsMarketplaceCollection->getSize();

        $ebayDictionaryRecords = (int)$resource->getConnection('core_read')
            ->select()
            ->from($epidsDictionaryTable, array(new Zend_Db_Expr('COUNT(*)')))
            ->where('is_custom = 0')
            ->query()
            ->fetchColumn();

        $customDictionaryRecords = (int)$resource->getConnection('core_read')
            ->select()
            ->from($epidsDictionaryTable, array(new Zend_Db_Expr('COUNT(*)')))
            ->where('is_custom = 1')
            ->query()
            ->fetchColumn();

        $this->motors_epids_dictionary_ebay_count   = $ebayDictionaryRecords;
        $this->motors_epids_dictionary_custom_count = $customDictionaryRecords;*/
        // ---------------------------------------

        // ---------------------------------------
        /** @var \Ess\M2ePro\Model\ResourceModel\Marketplace\Collection $ktypeMarketplaceCollection */
        /*$ktypeMarketplaceCollection = Mage::getModel('M2ePro/Marketplace')->getCollection();
        $ktypeMarketplaceCollection->addFieldToFilter(
            'id',
            array('in' => $motorsHelper->getKtypeSupportedMarketplaces())
        );
        $ktypeMarketplaceCollection->addFieldToFilter('status', Ess_M2ePro_Model_Marketplace::STATUS_ENABLE);
        $this->is_motors_ktypes_marketplace_enabled = (bool)$ktypeMarketplaceCollection->getSize();

        $ebayDictionaryRecords = (int)$resource->getConnection('core_read')
            ->select()
            ->from($ktypeDictionaryTable, array(new Zend_Db_Expr('COUNT(*)')))
            ->where('is_custom = 0')
            ->query()
            ->fetchColumn();

        $customDictionaryRecords = (int)$resource->getConnection('core_read')
            ->select()
            ->from($ktypeDictionaryTable, array(new Zend_Db_Expr('COUNT(*)')))
            ->where('is_custom = 1')
            ->query()
            ->fetchColumn();

        $this->motors_ktypes_dictionary_ebay_count   = $ebayDictionaryRecords;
        $this->motors_ktypes_dictionary_custom_count = $customDictionaryRecords;*/
        // ---------------------------------------

        // ---------------------------------------
        /*$attributesForMotors = Mage::getResourceModel('catalog/product_attribute_collection')
            ->addVisibleFilter()
            ->addFieldToFilter('backend_type', array('eq' => 'text'))
            ->addFieldToFilter('frontend_input', array('eq' => 'textarea'))
            ->toArray();

        $this->attributes_for_motors = $attributesForMotors['items'];

        $this->motors_epids_attribute = $this->cacheConfig->getGroupValue('/ebay/motors/','epids_attribute');
        $this->motors_ktypes_attribute = $this->cacheConfig->getGroupValue('/ebay/motors/','ktypes_attribute');*/
        // ---------------------------------------

        return parent::_prepareLayout();
    }

    //########################################

    protected function _beforeToHtml()
    {
        $this->jsUrl->add(
            $this->getUrl('*/ebay_settings/save'),
            \Ess\M2ePro\Block\Adminhtml\Ebay\Settings\Tabs::TAB_ID_MAIN
        );

        return parent::_beforeToHtml();
    }

    //########################################

    protected function getMultiCurrency()
    {
        $multiCurrency = [];

        $collection = $this->activeRecordFactory->getObject('Marketplace')->getCollection();
        $collection->addFieldToFilter('component_mode', \Ess\M2ePro\Helper\Component\Ebay::NICK);
        $collection->addFieldToFilter('status', \Ess\M2ePro\Model\Marketplace::STATUS_ENABLE);

        foreach ($collection as $marketplace) {
            $tempCurrency = $marketplace->getChildObject()->getCurrencies();
            if (strpos($tempCurrency, ',') !== false) {
                $multiCurrency[$marketplace->getTitle()]['currency'] = $tempCurrency;
                $multiCurrency[$marketplace->getTitle()]['code'] = $marketplace->getCode();
                $multiCurrency[$marketplace->getTitle()]['default'] = substr($tempCurrency,
                    0,
                    strpos($tempCurrency, ','));
            }
        }

        return $multiCurrency;
    }

    protected function isCurrencyForCode($code, $currency)
    {
        return $currency == $this->cacheConfig
            ->getGroupValue('/ebay/selling/currency/', $code);
    }

    //########################################

    protected function getGlobalNotice()
    {
        return '';
    }

    //########################################
}