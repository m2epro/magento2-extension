<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Template\Description;

use Ess\M2ePro\Controller\Adminhtml\Walmart\Template;
use Ess\M2ePro\Helper\Component\Walmart;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Walmart\Template\Description\Save
 */
class Save extends Template
{
    //########################################

    public function execute()
    {
        $post = $this->getRequest()->getPost();

        if (!$post->count()) {
            $this->_forward('index');
            return;
        }

        $id = $this->getRequest()->getParam('id');

        // Base prepare
        // ---------------------------------------
        $data = [];
        // ---------------------------------------

        // tab: list
        // ---------------------------------------
        $keys = [
            'title',
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

            'count_per_pack_mode',
            'count_per_pack_custom_value',
            'count_per_pack_custom_attribute',

            'multipack_quantity_mode',
            'multipack_quantity_custom_value',
            'multipack_quantity_custom_attribute',

            'msrp_rrp_mode',
            'msrp_rrp_custom_attribute',

            'model_number_mode',
            'model_number_custom_value',
            'model_number_custom_attribute',

            'total_count_mode',
            'total_count_custom_value',
            'total_count_custom_attribute',

            'keywords_mode',
            'keywords_custom_value',
            'keywords_custom_attribute',

            'key_features_mode',

            'other_features_mode',

            'image_main_mode',
            'image_main_attribute',

            'image_variation_difference_mode',
            'image_variation_difference_attribute',

            'gallery_images_mode',
            'gallery_images_attribute',
            'gallery_images_limit',

            'attributes_mode',

            'description_mode',
            'description_template',
        ];
        foreach ($keys as $key) {
            if (isset($post[$key])) {
                $data[$key] = $post[$key];
            }
        }

        $helper = $this->getHelper('Data');

        $data['title'] = strip_tags($data['title']);
        $data['key_features']   = $helper->jsonEncode($post['key_features']);
        $data['other_features'] = $helper->jsonEncode($post['other_features']);
        $data['attributes']     = $helper->jsonEncode(
            $this->getComparedData($post, 'attributes_name', 'attributes_value')
        );

        // ---------------------------------------

        // Add or update model
        // ---------------------------------------
        $descriptionTemplate = $this->walmartFactory->getObject('Template\Description');

        $id && $descriptionTemplate->load($id);

        $oldData = [];
        if ($descriptionTemplate->getId()) {

            /** @var \Ess\M2ePro\Model\Walmart\Template\Description\SnapshotBuilder $snapshotBuilder */
            $snapshotBuilder = $this->modelFactory->getObject('Walmart_Template_Description_SnapshotBuilder');
            $snapshotBuilder->setModel($descriptionTemplate);

            $oldData = $snapshotBuilder->getSnapshot();
        }

        $descriptionTemplate->addData($data)->save();
        $descriptionTemplate->getChildObject()->addData(array_merge(
            [$descriptionTemplate->getResource()->getChildPrimary(Walmart::NICK) => $descriptionTemplate->getId()],
            $data
        ));

        $descriptionTemplate->save();

        // Is Need Synchronize
        // ---------------------------------------
        /** @var \Ess\M2ePro\Model\Walmart\Template\Description\SnapshotBuilder $snapshotBuilder */
        $snapshotBuilder = $this->modelFactory->getObject('Walmart_Template_Description_SnapshotBuilder');
        $snapshotBuilder->setModel($descriptionTemplate);
        $newData = $snapshotBuilder->getSnapshot();

        /** @var \Ess\M2ePro\Model\Walmart\Template\Description\Diff $diff */
        $diff = $this->modelFactory->getObject('Walmart_Template_Description_Diff');
        $diff->setNewSnapshot($newData);
        $diff->setOldSnapshot($oldData);

        /** @var \Ess\M2ePro\Model\Walmart\Template\Description\AffectedListingsProducts $affectedListingsProducts */
        $affectedListingsProducts = $this->modelFactory->getObject(
            'Walmart_Template_Description_AffectedListingsProducts'
        );
        $affectedListingsProducts->setModel($descriptionTemplate);

        /** @var \Ess\M2ePro\Model\Walmart\Template\Description\ChangeProcessor $changeProcessor */
        $changeProcessor = $this->modelFactory->getObject('Walmart_Template_Description_ChangeProcessor');
        $changeProcessor->process(
            $diff,
            $affectedListingsProducts->getObjectsData(['id', 'status'])
        );
        // ---------------------------------------

        if ($this->isAjax()) {
            $this->setJsonContent([
                'status' => true
            ]);
            return $this->getResult();
        }

        $id = $descriptionTemplate->getId();
        // ---------------------------------------

        $this->messageManager->addSuccess($this->__('Policy was successfully saved'));
        return $this->_redirect($this->getHelper('Data')->getBackUrl('*/walmart_template/index', [], [
            'edit' => [
                'id' => $id,
                'wizard' => $this->getRequest()->getParam('wizard'),
                'close_on_save' => $this->getRequest()->getParam('close_on_save')
            ],
        ]));
    }

    //########################################

    private function getComparedData($data, $keyName, $valueName)
    {
        $result = [];

        if (!isset($data[$keyName]) || !isset($data[$valueName])) {
            return $result;
        }

        $keyData = array_filter($data[$keyName]);
        $valueData = array_filter($data[$valueName]);

        if (count($keyData) !== count($valueData)) {
            return $result;
        }

        foreach ($keyData as $index => $value) {
            $result[] = ['name' => $value, 'value' => $valueData[$index]];
        }

        return $result;
    }

    //########################################
}
