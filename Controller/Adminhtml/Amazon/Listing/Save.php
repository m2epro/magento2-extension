<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Save
 */
class Save extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing
{
    protected $dateTime;

    //########################################

    public function __construct(
        \Magento\Framework\Stdlib\DateTime $dateTime,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        $this->dateTime = $dateTime;
        parent::__construct($amazonFactory, $context);
    }

    //########################################

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Ess_M2ePro::amazon_listings_m2epro');
    }

    //########################################

    public function execute()
    {
        if (!$post = $this->getRequest()->getPost()) {
            $this->_redirect('*/amazon_listing/index');
        }

        $id = $this->getRequest()->getParam('id');
        $listing = $this->amazonFactory->getObjectLoaded('Listing', $id, null, false);

        if ($listing === null && $id) {
            $this->getMessageManager()->addError($this->__('Listing does not exist.'));
            return $this->_redirect('*/amazon_listing/index');
        }

        $oldData = array_merge($listing->getDataSnapshot(), $listing->getChildObject()->getDataSnapshot());

        // Base prepare
        // ---------------------------------------
        $data = [];
        // ---------------------------------------

        // tab: settings
        // ---------------------------------------
        $keys = [
            'template_selling_format_id',
            'template_synchronization_id',
        ];
        foreach ($keys as $key) {
            if (isset($post[$key])) {
                $data[$key] = $post[$key];
            }
        }
        // ---------------------------------------

        $listing->addData($data);
        $listing->getChildObject()->addData($data);
        $listing->save();

        $templateData = [];

        // tab: channel settings
        // ---------------------------------------
        $keys = [
            'account_id',
            'marketplace_id',

            'sku_mode',
            'sku_custom_attribute',
            'sku_modification_mode',
            'sku_modification_custom_value',
            'generate_sku_mode',

            'general_id_mode',
            'general_id_custom_attribute',
            'worldwide_id_mode',
            'worldwide_id_custom_attribute',

            'search_by_magento_title_mode',

            'condition_mode',
            'condition_value',
            'condition_custom_attribute',

            'condition_note_mode',
            'condition_note_value',

            'image_main_mode',
            'image_main_attribute',

            'gallery_images_mode',
            'gallery_images_limit',
            'gallery_images_attribute',

            'gift_wrap_mode',
            'gift_wrap_attribute',

            'gift_message_mode',
            'gift_message_attribute',

            'handling_time_mode',
            'handling_time_value',
            'handling_time_custom_attribute',

            'restock_date_mode',
            'restock_date_value',
            'restock_date_custom_attribute'
        ];
        foreach ($keys as $key) {
            if (isset($post[$key])) {
                $templateData[$key] = $post[$key];
            }
        }

        if ($templateData['restock_date_value'] === '') {
            $templateData['restock_date_value'] = $this->getHelper('Data')->getCurrentGmtDate();
        } else {
            $timestamp = $this->getHelper('Data')->parseTimestampFromLocalizedFormat(
                $templateData['restock_date_value'],
                \IntlDateFormatter::SHORT,
                \IntlDateFormatter::SHORT
            );
            $templateData['restock_date_value'] = $this->getHelper('Data')->getDate($timestamp);
        }
        // ---------------------------------------

        $listing->addData($templateData);
        $listing->getChildObject()->addData($templateData);
        $listing->save();

        $newData = array_merge($listing->getDataSnapshot(), $listing->getChildObject()->getDataSnapshot());

        $listing->getChildObject()->setSynchStatusNeed($newData, $oldData);

        $this->getMessageManager()->addSuccess($this->__('The Listing was successfully saved.'));

        return $this->_redirect($this->getHelper('Data')->getBackUrl('list', [], ['edit'=>['id'=>$id]]));
    }

    //########################################
}
