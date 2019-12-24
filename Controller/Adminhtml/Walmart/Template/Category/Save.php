<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Template\Category;

use Ess\M2ePro\Controller\Adminhtml\Walmart\Template\Category;
use Ess\M2ePro\Model\Walmart\Template\Category\Specific;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Walmart\Template\Category\Save
 */
class Save extends Category
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

            'category_path',
            'product_data_nick',
            'browsenode_id'
        ];

        $dataForAdd = [];
        foreach ($keys as $key) {
            if ($key === 'encoded_data') {
                continue;
            }

            if (isset($post[$key])) {
                $dataForAdd[$key] = $post[$key];
            }
        }

        $dataForAdd['title'] = strip_tags($dataForAdd['title']);

        /** @var \Ess\M2ePro\Model\Walmart\Template\Category $categoryTemplate */
        $categoryTemplate = $this->activeRecordFactory->getObject('Walmart_Template_Category');
        $id && $categoryTemplate->load($id);

        $oldData = [];
        if ($categoryTemplate->getId()) {
            $oldData = $categoryTemplate->getDataSnapshot();
        }

        $categoryTemplate->addData($dataForAdd)->save();
        $id = $categoryTemplate->getId();

        // ---------------------------------------

        // Saving specifics info
        // ---------------------------------------
        foreach ($categoryTemplate->getSpecifics(true) as $specific) {
            $specific->delete();
        }

        $specifics = !empty($post['encoded_data']) ? $post['encoded_data'] : '';
        $specifics = (array)$this->getHelper('Data')->jsonDecode($specifics);

        $this->sortSpecifics($specifics, $post['product_data_nick'], $post['marketplace_id']);

        foreach ($specifics as $xpath => $specificData) {
            if (!$this->validateSpecificData($specificData)) {
                continue;
            }

            $specificInstance = $this->activeRecordFactory->getObject('Walmart_Template_Category_Specific');

            $type       = isset($specificData['type']) ? $specificData['type'] : '';
            $isRequired = isset($specificData['is_required']) ? $specificData['is_required'] : 0;
            $attributes = isset($specificData['attributes'])
                ? $this->getHelper('Data')->jsonEncode($specificData['attributes']) : '[]';

            $customValue      = $specificData['mode'] == Specific::DICTIONARY_MODE_CUSTOM_VALUE
                ? $specificData['custom_value'] : '';

            $customAttribute  = $specificData['mode'] == Specific::DICTIONARY_MODE_CUSTOM_ATTRIBUTE
                ? $specificData['custom_attribute'] : '';

            $specificInstance->addData([
                'template_category_id' => $id,
                'xpath'                => $xpath,
                'mode'                 => $specificData['mode'],
                'is_required'          => $isRequired,
                'custom_value'         => $customValue,
                'custom_attribute'     => $customAttribute,
                'type'                 => $type,
                'attributes'           => $attributes
            ]);
            $specificInstance->save();
        }
        // ---------------------------------------

        // Is Need Synchronize
        // ---------------------------------------
        $newData = $categoryTemplate->getDataSnapshot();
        $categoryTemplate->setSynchStatusNeed($newData, $oldData);
        // ---------------------------------------

        if ($this->isAjax()) {
            $this->setJsonContent([
                'status' => true
            ]);
            return $this->getResult();
        }

        $this->messageManager->addSuccess($this->__('Policy was successfully saved'));
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
            ->getTableNameWithPrefix('m2epro_walmart_dictionary_specific');

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
