<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Amazon\Template;

class Synchronization extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Component\Child\AbstractModel
{
    protected $_isPkAutoIncrement = false;

    //########################################

    public function _construct()
    {
        $this->_init('m2epro_amazon_template_synchronization', 'template_synchronization_id');
        $this->_isPkAutoIncrement = false;
    }

    //########################################

    public function setSynchStatusNeed($newData, $oldData, $listingsProducts)
    {
        if (empty($listingsProducts)) {
            return;
        }

        $settings = array(
            'listing'                => 'revise_change_listing',
            'sellingFormatTemplate'  => 'revise_change_selling_format_template',
            'descriptionTemplate'    => 'revise_change_description_template',
            'shippingTemplate'       => 'revise_change_shipping_template',
            'productTaxCodeTemplate' => 'revise_change_product_tax_code_template',
        );

        $settings = $this->getEnabledReviseSettings($newData, $oldData, $settings);

        if (!$settings) {
            return;
        }

        $idsByReasonDictionary = array();
        foreach ($listingsProducts as $listingProduct) {

            if ($listingProduct['synch_status'] != \Ess\M2ePro\Model\Listing\Product::SYNCH_STATUS_SKIP) {
                continue;
            }

            $listingProductSynchReasons = array_unique(array_filter(explode(',',$listingProduct['synch_reasons'])));
            foreach ($listingProductSynchReasons as $reason) {
                $idsByReasonDictionary[$reason][] = $listingProduct['id'];
            }
        }

        $idsForUpdate = array();
        foreach ($settings as $reason => $setting) {

            if (!isset($idsByReasonDictionary[$reason])) {
                continue;
            }

            $idsForUpdate = array_merge($idsForUpdate, $idsByReasonDictionary[$reason]);
        }

        $lpTable = $this->activeRecordFactory->getObject('Listing\Product')->getResource()->getMainTable();

        $this->getConnection()->update(
            $lpTable,
            array('synch_status' => \Ess\M2ePro\Model\Listing\Product::SYNCH_STATUS_NEED),
            array('id IN (?)' => array_unique($idsForUpdate))
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