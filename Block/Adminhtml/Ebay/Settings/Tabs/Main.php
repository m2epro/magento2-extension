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

        $viewEbayFeedbacksNotificationMode = (int)$configModel->getGroupValue(
            '/view/ebay/feedbacks/notification/','mode'
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
                    '<p>Choose \'Yes\' to prevent M2E Pro from adding a Product
                     if it has already been presented in the Listing</p>
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

        if ($this->getHelper('View\Ebay')->isFeedbacksShouldBeShown()) {
            $fieldset->addField('view_ebay_feedbacks_notification_mode',
                'select',
                [
                    'name' => 'view_ebay_feedbacks_notification_mode',
                    'label' => $this->__('Negative Feedback'),
                    'values' => [
                        0 => $this->__('No'),
                        1 => $this->__('Yes')
                    ],
                    'value' => $viewEbayFeedbacksNotificationMode,
                    'tooltip' => $this->__('Show a notification in Magento when you receive negative Feedback on eBay.')
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
            $this->getUrl('*/ebay_settings/save'),
            \Ess\M2ePro\Block\Adminhtml\Ebay\Settings\Tabs::TAB_ID_MAIN
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