<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Ebay\Template;

/**
 * Class \Ess\M2ePro\Model\ResourceModel\Ebay\Template\Synchronization
 */
class Synchronization extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Component\Child\AbstractModel
{
    protected $_isPkAutoIncrement = false;

    //########################################

    public function _construct()
    {
        $this->_init('m2epro_ebay_template_synchronization', 'template_synchronization_id');
        $this->_isPkAutoIncrement = false;
    }

    //########################################

    public function setSynchStatusNeed($newData, $oldData, $listingsProducts)
    {
        if (empty($listingsProducts)) {
            return;
        }

        $settings = [
            'sellingFormatTemplate' => 'revise_change_selling_format_template',
            'descriptionTemplate'   => 'revise_change_description_template',
            'categoryTemplate'      => 'revise_change_category_template',
            'otherCategoryTemplate' => 'revise_change_category_template',
            'paymentTemplate'       => 'revise_change_payment_template',
            'shippingTemplate'      => 'revise_change_shipping_template',
            'returnTemplate'        => 'revise_change_return_policy_template'
        ];

        $settings = $this->getEnabledReviseSettings($newData, $oldData, $settings);

        if (!$settings) {
            return;
        }

        $idsByReasonDictionary = [];
        foreach ($listingsProducts as $listingProduct) {
            if ($listingProduct['synch_status'] != \Ess\M2ePro\Model\Listing\Product::SYNCH_STATUS_SKIP) {
                continue;
            }

            $listingProductSynchReasons = array_unique(array_filter(explode(',', $listingProduct['synch_reasons'])));
            foreach ($listingProductSynchReasons as $reason) {
                $idsByReasonDictionary[$reason][] = $listingProduct['id'];
            }
        }

        $idsForUpdate = [];
        foreach ($settings as $reason => $setting) {
            if (!isset($idsByReasonDictionary[$reason])) {
                continue;
            }

            $idsForUpdate = array_merge($idsForUpdate, $idsByReasonDictionary[$reason]);
        }

        $this->getConnection()->update(
            $this->activeRecordFactory->getObject('Listing\Product')->getResource()->getMainTable(),
            ['synch_status' => \Ess\M2ePro\Model\Listing\Product::SYNCH_STATUS_NEED],
            ['id IN (?)' => array_unique($idsForUpdate)]
        );
    }

    // ---------------------------------------

    public function getEnabledReviseSettings($newData, $oldData, $settings)
    {
        foreach ($settings as $reason => $setting) {
            if (!isset($newData[$setting], $oldData[$setting])) {
                unset($settings[$reason]);
                continue;
            }

            // we need change from 0 to 1 only
            if ($oldData[$setting] || !$newData[$setting]) {
                unset($settings[$reason]);
                continue;
            }
        }

        return $settings;
    }

    //########################################
}
