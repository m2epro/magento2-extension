<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Template\Description;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Template\Description;
use Ess\M2ePro\Model\Amazon\Template\Description\Specific;
use Ess\M2ePro\Helper\Component\Amazon;

class Save extends Description
{
    //########################################

    public function execute()
    {
        $post = $this->getRequest()->getPostValue();

        if (empty($post)) {
            $this->_forward('index');
            return;
        }

        $id = $this->getRequest()->getParam('id');

        // Saving general data
        // ---------------------------------------
        $keys = [
            'title',
            'marketplace_id',
            'is_new_asin_accepted',

            'category_path',
            'product_data_nick',
            'browsenode_id',

            'registered_parameter',

            'worldwide_id_mode',
            'worldwide_id_custom_attribute'
        ];

        $dataForAdd = [];
        foreach ($keys as $key) {
            isset($post['general'][$key]) && $dataForAdd[$key] = $post['general'][$key];
        }

        $dataForAdd['title'] = strip_tags($dataForAdd['title']);

        /** @var \Ess\M2ePro\Model\Template\Description $descriptionTemplate */
        $descriptionTemplate = $this->amazonFactory->getObject('Template\Description');

        $id && $descriptionTemplate->load($id);

        $oldData = [];
        if ($descriptionTemplate->getId()) {
            $oldData = array_merge(
                $descriptionTemplate->getDataSnapshot(),
                $descriptionTemplate->getChildObject()->getDataSnapshot()
            );
        }

        $descriptionTemplate->addData($dataForAdd)->save();

        /** @var \Ess\M2ePro\Model\Amazon\Template\Description $amazonDescriptionTemplate */
        $amazonDescriptionTemplate = $descriptionTemplate->getChildObject();
        $amazonDescriptionTemplate->addData(array_merge(
            [$descriptionTemplate->getResource()->getChildPrimary(Amazon::NICK) => $descriptionTemplate->getId()],
            $dataForAdd
        ))->save();
        // ---------------------------------------

        $id = $descriptionTemplate->getId();

        // Saving definition info
        // ---------------------------------------
        $keys = [

            'title_mode',
            'title_template',

            'brand_mode',
            'brand_custom_value',
            'brand_custom_attribute',

            'manufacturer_mode',
            'manufacturer_custom_value',
            'manufacturer_custom_attribute',

            'manufacturer_part_number_mode',
            'manufacturer_part_number_custom_value',
            'manufacturer_part_number_custom_attribute',

            'item_package_quantity_mode',
            'item_package_quantity_custom_value',
            'item_package_quantity_custom_attribute',

            'number_of_items_mode',
            'number_of_items_custom_value',
            'number_of_items_custom_attribute',

            'item_dimensions_volume_mode',
            'item_dimensions_volume_length_custom_value',
            'item_dimensions_volume_width_custom_value',
            'item_dimensions_volume_height_custom_value',
            'item_dimensions_volume_length_custom_attribute',
            'item_dimensions_volume_width_custom_attribute',
            'item_dimensions_volume_height_custom_attribute',
            'item_dimensions_volume_unit_of_measure_mode',
            'item_dimensions_volume_unit_of_measure_custom_value',
            'item_dimensions_volume_unit_of_measure_custom_attribute',

            'item_dimensions_weight_mode',
            'item_dimensions_weight_custom_value',
            'item_dimensions_weight_custom_attribute',
            'item_dimensions_weight_unit_of_measure_mode',
            'item_dimensions_weight_unit_of_measure_custom_value',
            'item_dimensions_weight_unit_of_measure_custom_attribute',

            'package_dimensions_volume_mode',
            'package_dimensions_volume_length_custom_value',
            'package_dimensions_volume_width_custom_value',
            'package_dimensions_volume_height_custom_value',
            'package_dimensions_volume_length_custom_attribute',
            'package_dimensions_volume_width_custom_attribute',
            'package_dimensions_volume_height_custom_attribute',
            'package_dimensions_volume_unit_of_measure_mode',
            'package_dimensions_volume_unit_of_measure_custom_value',
            'package_dimensions_volume_unit_of_measure_custom_attribute',

            'package_weight_mode',
            'package_weight_custom_value',
            'package_weight_custom_attribute',
            'package_weight_unit_of_measure_mode',
            'package_weight_unit_of_measure_custom_value',
            'package_weight_unit_of_measure_custom_attribute',

            'shipping_weight_mode',
            'shipping_weight_custom_value',
            'shipping_weight_custom_attribute',
            'shipping_weight_unit_of_measure_mode',
            'shipping_weight_unit_of_measure_custom_value',
            'shipping_weight_unit_of_measure_custom_attribute',

            'target_audience_mode',
            'target_audience',

            'search_terms_mode',
            'search_terms',

            'image_main_mode',
            'image_main_attribute',

            'image_variation_difference_mode',
            'image_variation_difference_attribute',

            'gallery_images_mode',
            'gallery_images_attribute',
            'gallery_images_limit',

            'bullet_points_mode',
            'bullet_points',

            'description_mode',
            'description_template',
        ];

        $dataForAdd = [];
        foreach ($keys as $key) {
            isset($post['definition'][$key]) && $dataForAdd[$key] = $post['definition'][$key];
        }

        $dataForAdd['template_description_id'] = $id;

        $dataForAdd['target_audience'] = $this->getHelper('Data')->jsonEncode(
            array_filter($dataForAdd['target_audience'])
        );
        $dataForAdd['search_terms']    = $this->getHelper('Data')->jsonEncode(
            array_filter($dataForAdd['search_terms'])
        );
        $dataForAdd['bullet_points']   = $this->getHelper('Data')->jsonEncode(
            array_filter($dataForAdd['bullet_points'])
        );

        /* @var $descriptionDefinition \Ess\M2ePro\Model\Amazon\Template\Description\Definition */
        $descriptionDefinition = $this->activeRecordFactory->getObjectLoaded(
            'Amazon\Template\Description\Definition', $id, NULL, false
        );

        if (is_null($descriptionDefinition)) {
            $descriptionDefinition = $this->activeRecordFactory->getObject('Amazon\Template\Description\Definition');
        }

        $descriptionDefinition->addData($dataForAdd)->save();
        $amazonDescriptionTemplate->setDefinitionTemplate($descriptionDefinition);
        // ---------------------------------------

        // Saving specifics info
        // ---------------------------------------
        foreach ($amazonDescriptionTemplate->getSpecifics(true) as $specific) {
            $specific->delete();
        }

        $specifics = !empty($post['specifics']['encoded_data']) ? $post['specifics']['encoded_data'] : '';
        $specifics = (array)$this->getHelper('Data')->jsonDecode($specifics);

        $this->sortSpecifics($specifics, $post['general']['product_data_nick'], $post['general']['marketplace_id']);

        foreach ($specifics as $xpath => $specificData) {

            if (!$this->validateSpecificData($specificData)) {
                continue;
            }

            $specificInstance = $this->activeRecordFactory->getObject('Amazon\Template\Description\Specific');

            $type       = isset($specificData['type']) ? $specificData['type'] : '';
            $isRequired = isset($specificData['is_required']) ? $specificData['is_required'] : 0;
            $attributes = isset($specificData['attributes'])
                ? $this->getHelper('Data')->jsonEncode($specificData['attributes']) : '[]';

            $recommendedValue = $specificData['mode'] == Specific::DICTIONARY_MODE_RECOMMENDED_VALUE
                ? $specificData['recommended_value'] : '';

            $customValue      = $specificData['mode'] == Specific::DICTIONARY_MODE_CUSTOM_VALUE
                ? $specificData['custom_value'] : '';

            $customAttribute  = $specificData['mode'] == Specific::DICTIONARY_MODE_CUSTOM_ATTRIBUTE
                ? $specificData['custom_attribute'] : '';

            $specificInstance->addData([
                'template_description_id' => $id,
                'xpath'                   => $xpath,
                'mode'                    => $specificData['mode'],
                'is_required'             => $isRequired,
                'recommended_value'       => $recommendedValue,
                'custom_value'            => $customValue,
                'custom_attribute'        => $customAttribute,
                'type'                    => $type,
                'attributes'              => $attributes
            ]);
            $specificInstance->save();
        }
        // ---------------------------------------

        // Is Need Synchronize
        // ---------------------------------------
        $newData = array_merge(
            $descriptionTemplate->getDataSnapshot(),
            $amazonDescriptionTemplate->getDataSnapshot()
        );
        $amazonDescriptionTemplate->setSynchStatusNeed($newData, $oldData);
        // ---------------------------------------

        // Run Processor for Variation Relation Parents
        // ---------------------------------------
        if ($amazonDescriptionTemplate->getResource()->isDifferent($newData, $oldData)) {

            $listingProductCollection = $this->amazonFactory->getObject('Listing\Product')->getCollection()
                ->addFieldToFilter('template_description_id', $id)
                ->addFieldToFilter(
                    'is_general_id_owner', \Ess\M2ePro\Model\Amazon\Listing\Product::IS_GENERAL_ID_OWNER_YES
                )
                ->addFieldToFilter('general_id', array('null' => true))
                ->addFieldToFilter('is_variation_product', 1)
                ->addFieldToFilter('is_variation_parent', 1);

            $massProcessor = $this->modelFactory->getObject(
                'Amazon\Listing\Product\Variation\Manager\Type\Relation\ParentRelation\Processor\Mass'
            );
            $massProcessor->setListingsProducts($listingProductCollection->getItems());
            $massProcessor->setForceExecuting(false);

            $massProcessor->execute();
        }
        // ---------------------------------------

        if ($this->isAjax()) {
            $this->setJsonContent([
                'status' => true
            ]);
            return $this->getResult();
        }

        $this->messageManager->addSuccess($this->__('Policy was successfully saved'));
        return $this->_redirect($this->getHelper('Data')->getBackUrl(
            'list', [], ['edit' => [
                'id' => $id,
                'wizard' => $this->getRequest()->getParam('wizard'),
                'close_on_save' => $this->getRequest()->getParam('close_on_save')
            ]])
        );
    }

    // ---------------------------------------

    private function validateSpecificData($specificData)
    {
        if (empty($specificData['mode'])) {
            return false;
        }

        if (empty($specificData['recommended_value']) &&
            !in_array($specificData['mode'], ['none','custom_value','custom_attribute'])) {
            return false;
        }
        if (empty($specificData['custom_value']) &&
            !in_array($specificData['mode'], ['none','recommended_value','custom_attribute'])) {
            return false;
        }
        if (empty($specificData['custom_attribute']) &&
            !in_array($specificData['mode'], ['none','recommended_value','custom_value'])) {
            return false;
        }

        return true;
    }

    private function sortSpecifics(&$specifics, $productData, $marketplaceId)
    {
        $table = $this->resourceConnection->getTableName('m2epro_amazon_dictionary_specific');

        $dictionarySpecifics = $this->resourceConnection->getConnection()->select()
            ->from($table,['id', 'xpath'])
            ->where('product_data_nick = ?', $productData)
            ->where('marketplace_id = ?', $marketplaceId)
            ->query()->fetchAll();

        foreach ($dictionarySpecifics as $key => $specific) {
            $xpath = $specific['xpath'];
            unset($dictionarySpecifics[$key]);
            $dictionarySpecifics[$xpath] = $specific['id'];
        }

        $this->getHelper('Data\GlobalData')->setValue('dictionary_specifics', $dictionarySpecifics);

        $callback = function ($aXpath, $bXpath) use ($dictionarySpecifics)
        {

            $aXpathParts = explode('/',$aXpath);
            foreach ($aXpathParts as &$part) {
                $part = preg_replace('/\-\d+$/','',$part);
            }
            unset($part);
            $aXpath = implode('/',$aXpathParts);

            $bXpathParts = explode('/',$bXpath);
            foreach ($bXpathParts as &$part) {
                $part = preg_replace('/\-\d+$/','',$part);
            }
            unset($part);
            $bXpath = implode('/',$bXpathParts);

            $aIndex = $dictionarySpecifics[$aXpath];
            $bIndex = $dictionarySpecifics[$bXpath];

            return $aIndex > $bIndex ? 1 : -1;
        };

        uksort($specifics, $callback);
    }

    //########################################
}