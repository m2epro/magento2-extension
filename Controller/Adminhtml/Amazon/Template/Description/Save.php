<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Template\Description;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Template\Description;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Template\Description\Save
 */
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

        /** @var \Ess\M2ePro\Model\Template\Description $descriptionTemplate */
        $descriptionTemplate = $this->amazonFactory->getObject('Template\Description');
        $id && $descriptionTemplate->load($id);

        $oldData = [];
        if ($descriptionTemplate->getId()) {
            /** @var \Ess\M2ePro\Model\Amazon\Template\Description\SnapshotBuilder $snapshotBuilder */
            $snapshotBuilder = $this->modelFactory->getObject('Amazon_Template_Description_SnapshotBuilder');
            $snapshotBuilder->setModel($descriptionTemplate);

            $oldData = $snapshotBuilder->getSnapshot();
        }

        $this->modelFactory->getObject('Amazon_Template_Description_Builder')
            ->build($descriptionTemplate, $post['general']);

        $id = $descriptionTemplate->getId();

        // Saving definition info
        // ---------------------------------------
        /** @var $descriptionDefinition \Ess\M2ePro\Model\Amazon\Template\Description\Definition */
        $descriptionDefinition = $this->activeRecordFactory->getObjectLoaded(
            'Amazon_Template_Description_Definition',
            $id,
            null,
            false
        );
        if ($descriptionDefinition === null) {
            $descriptionDefinition = $this->activeRecordFactory->getObject('Amazon_Template_Description_Definition');
        }

        /** @var \Ess\M2ePro\Model\Amazon\Template\Description\Definition\Builder $descriptionDefinitionBuilder */
        $descriptionDefinitionBuilder = $this->modelFactory
            ->getObject('Amazon_Template_Description_Definition_Builder');
        $descriptionDefinitionBuilder->setTemplateDescriptionId($id);
        $descriptionDefinitionBuilder->build($descriptionDefinition, $post['definition']);

        /** @var \Ess\M2ePro\Model\Amazon\Template\Description $amazonDescriptionTemplate */
        $amazonDescriptionTemplate = $descriptionTemplate->getChildObject();
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

        /** @var \Ess\M2ePro\Model\Amazon\Template\Description\Specific\Builder $descriptionSpecificBuilder */
        $descriptionSpecificBuilder = $this->modelFactory->getObject('Amazon_Template_Description_Specific_Builder');

        foreach ($specifics as $xpath => $specificData) {
            if (!$this->validateSpecificData($specificData)) {
                continue;
            }

            $specificData['xpath'] = $xpath;

            $specificInstance = $this->activeRecordFactory->getObject('Amazon_Template_Description_Specific');
            $descriptionSpecificBuilder->setTemplateDescriptionId($id);
            $descriptionSpecificBuilder->build($specificInstance, $specificData);
        }

        // Is Need Synchronize
        // ---------------------------------------
        /** @var \Ess\M2ePro\Model\Amazon\Template\Description\SnapshotBuilder $snapshotBuilder */
        $snapshotBuilder = $this->modelFactory->getObject('Amazon_Template_Description_SnapshotBuilder');
        $snapshotBuilder->setModel($amazonDescriptionTemplate->getParentObject());
        $newData = $snapshotBuilder->getSnapshot();

        /** @var \Ess\M2ePro\Model\Amazon\Template\Description\Diff $diff */
        $diff = $this->modelFactory->getObject('Amazon_Template_Description_Diff');
        $diff->setNewSnapshot($newData);
        $diff->setOldSnapshot($oldData);

        /** @var \Ess\M2ePro\Model\Amazon\Template\Description\AffectedListingsProducts $affectedListingsProducts */
        $affectedListingsProducts = $this->modelFactory->getObject(
            'Amazon_Template_Description_AffectedListingsProducts'
        );
        $affectedListingsProducts->setModel($amazonDescriptionTemplate);

        /** @var \Ess\M2ePro\Model\Amazon\Template\Description\ChangeProcessor $changeProcessor */
        $changeProcessor = $this->modelFactory->getObject('Amazon_Template_Description_ChangeProcessor');
        $changeProcessor->process(
            $diff,
            $affectedListingsProducts->getObjectsData(['id', 'status'])
        );

        // Run Processor for Variation Relation Parents
        // ---------------------------------------
        if ($diff->isDetailsDifferent() || $diff->isImagesDifferent()) {
            $listingProductCollection = $this->amazonFactory->getObject('Listing\Product')->getCollection()
                ->addFieldToFilter('template_description_id', $id)
                ->addFieldToFilter(
                    'is_general_id_owner',
                    \Ess\M2ePro\Model\Amazon\Listing\Product::IS_GENERAL_ID_OWNER_YES
                )
                ->addFieldToFilter('general_id', ['null' => true])
                ->addFieldToFilter('is_variation_product', 1)
                ->addFieldToFilter('is_variation_parent', 1);

            $massProcessor = $this->modelFactory->getObject(
                'Amazon_Listing_Product_Variation_Manager_Type_Relation_ParentRelation_Processor_Mass'
            );
            $massProcessor->setListingsProducts($listingProductCollection->getItems());
            $massProcessor->setForceExecuting(false);

            $massProcessor->execute();
        }

        if ($this->isAjax()) {
            $this->setJsonContent([
                'status' => true
            ]);
            return $this->getResult();
        }

        $this->messageManager->addSuccess($this->__('Policy was saved'));
        return $this->_redirect($this->getHelper('Data')->getBackUrl(
            'list',
            [],
            ['edit' => [
                'id' => $id,
                'wizard' => $this->getRequest()->getParam('wizard'),
                'close_on_save' => $this->getRequest()->getParam('close_on_save')
            ]]
        ));
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
        $table = $this->getHelper('Module_Database_Structure')
            ->getTableNameWithPrefix('m2epro_amazon_dictionary_specific');

        $dictionarySpecifics = $this->resourceConnection->getConnection()->select()
            ->from($table, ['id', 'xpath'])
            ->where('product_data_nick = ?', $productData)
            ->where('marketplace_id = ?', $marketplaceId)
            ->query()->fetchAll();

        foreach ($dictionarySpecifics as $key => $specific) {
            $xpath = $specific['xpath'];
            unset($dictionarySpecifics[$key]);
            $dictionarySpecifics[$xpath] = $specific['id'];
        }

        $this->getHelper('Data\GlobalData')->setValue('dictionary_specifics', $dictionarySpecifics);

        $callback = function ($aXpath, $bXpath) use ($dictionarySpecifics) {

            $aXpathParts = explode('/', $aXpath);
            foreach ($aXpathParts as &$part) {
                $part = preg_replace('/\-\d+$/', '', $part);
            }
            unset($part);
            $aXpath = implode('/', $aXpathParts);

            $bXpathParts = explode('/', $bXpath);
            foreach ($bXpathParts as &$part) {
                $part = preg_replace('/\-\d+$/', '', $part);
            }
            unset($part);
            $bXpath = implode('/', $bXpathParts);

            $aIndex = $dictionarySpecifics[$aXpath];
            $bIndex = $dictionarySpecifics[$bXpath];

            return $aIndex > $bIndex ? 1 : -1;
        };

        uksort($specifics, $callback);
    }

    //########################################
}
