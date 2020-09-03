<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Settings\Tabs;

use \Ess\M2ePro\Helper\Component\Ebay\Configuration as ConfigurationHelper;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Ebay\Settings\Tabs\Main
 */
class Main extends \Ess\M2ePro\Block\Adminhtml\Settings\Tabs\AbstractTab
{
    //########################################

    protected function _prepareForm()
    {
        $form = $this->_formFactory->create([
            'data' => [
                'method' => 'post',
                'action' => $this->getUrl('*/*/save')
            ]
        ]);

        $fieldset = $form->addFieldset(
            'images',
            [
                'legend' => $this->__('Images Uploading'),
                'collapsable' => false,
            ]
        );

        $fieldset->addField(
            'upload_images_mode',
            'select',
            [
                'name'   => 'upload_images_mode',
                'label'  => $this->__('Main Image/Gallery Hosting Mode'),
                'values' => [
                    ConfigurationHelper::UPLOAD_IMAGES_MODE_AUTO => $this->__('Automatic'),
                    ConfigurationHelper::UPLOAD_IMAGES_MODE_SELF => $this->__('Self-Hosted'),
                    ConfigurationHelper::UPLOAD_IMAGES_MODE_EPS => $this->__('EPS-Hosted')
                ],
                'value' => $this->getHelper('Component_Ebay_Configuration')->getUploadImagesMode(),
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

        $fieldset = $form->addFieldset(
            'additional',
            [
                'legend' => $this->__('Additional'),
                'collapsable' => false,
            ]
        );

        $fieldset->addField(
            'prevent_item_duplicates_mode',
            'select',
            [
                'name'        => 'prevent_item_duplicates_mode',
                'label'       => $this->__('Prevent eBay Item Duplicates'),
                'values' => [
                    0 => $this->__('No'),
                    1 => $this->__('Yes'),
                ],
                'value' => $this->getHelper('Component_Ebay_Configuration')->getPreventItemDuplicatesMode(),
                'tooltip' => $this->__(
                    'M2E Pro will not list Magento Product on the Channel if it is already listed
                    within the same eBay Account and Marketplace.'
                )
            ]
        );

        if ($this->getHelper('View_Ebay')->isFeedbacksShouldBeShown()) {
            $fieldset->addField(
                'feedback_notification_mode',
                'select',
                [
                    'name' => 'feedback_notification_mode',
                    'label' => $this->__('Negative Feedback'),
                    'values' => [
                        0 => $this->__('No'),
                        1 => $this->__('Yes')
                    ],
                    'value' => $this->getHelper('Component_Ebay_Configuration')->getFeedbackNotificationMode(),
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
