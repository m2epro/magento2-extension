<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Template\Category\Chooser\Specific;

use \Ess\M2ePro\Model\Ebay\Template\Category\Specific as Specific;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Ebay\Template\Category\Chooser\Specific\Edit
 */
class Edit extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractContainer
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        $this->setId('ebayTemplateCategoryChooserSpecificEdit');

        $this->_controller = 'adminhtml_ebay_template_category_chooser_specific';
        $this->_mode = 'edit';

        $this->removeButton('back');
        $this->removeButton('reset');
        $this->removeButton('delete');
        $this->removeButton('add');
        $this->removeButton('save');
        $this->removeButton('edit');
    }

    //########################################

    public function prepareFormData()
    {
        $templateSpecifics = [];
        $dictionarySpecifics = $this->getDictionarySpecifics();

        $selectedSpecs =  $this->getHelper('Data')->jsonDecode($this->getData('selected_specifics'));

        if ($this->getData('template_id')) {
            /** @var \Ess\M2ePro\Model\Ebay\Template\Category $template */
            $template = $this->activeRecordFactory->getObjectLoaded(
                'Ebay_Template_Category',
                $this->getData('template_id')
            );
            $templateSpecifics = $template->getSpecifics();
        } elseif (!empty($selectedSpecs)) {
            $builder = $this->modelFactory->getObject('Ebay_Template_Category_Builder');
            foreach ($selectedSpecs as $selectedSp) {
                $templateSpecifics[] = $builder->serializeSpecific($selectedSp);
            }
        } else {
            /** @var \Ess\M2ePro\Model\Ebay\Template\Category $template */
            $template = $this->activeRecordFactory->getObject('Ebay_Template_Category');
            $template->loadByCategoryValue(
                $this->getData('category_value'),
                $this->getData('category_mode'),
                $this->getData('marketplace_id'),
                0
            );

            $template->getId() && $templateSpecifics = $template->getSpecifics();
        }

        foreach ($dictionarySpecifics as &$dictionarySpecific) {
            foreach ($templateSpecifics as $templateSpecific) {
                if ($dictionarySpecific['title'] == $templateSpecific['attribute_title']) {
                    $dictionarySpecific['template_specific'] = $templateSpecific;
                    continue;
                }
            }
        }

        unset($dictionarySpecific);

        $templateCustomSpecifics = [];
        foreach ($templateSpecifics as $templateSpecific) {
            if ($templateSpecific['mode'] == Specific::MODE_CUSTOM_ITEM_SPECIFICS) {
                $templateCustomSpecifics[] = $templateSpecific;
            }
        }

        $this->getChildBlock('form')->setData(
            'form_data',
            [
                'dictionary_specifics'      => $dictionarySpecifics,
                'template_custom_specifics' => $templateCustomSpecifics
            ]
        );
    }

    protected function getDictionarySpecifics()
    {
        if ($this->getData('category_mode') == \Ess\M2ePro\Model\Ebay\Template\Category::CATEGORY_MODE_ATTRIBUTE) {
            return [];
        }

        $specifics = $this->getHelper('Component_Ebay_Category_Ebay')->getSpecifics(
            $this->getData('category_value'),
            $this->getData('marketplace_id')
        );

        return $specifics === null ? [] : $specifics;
    }

    //########################################

    protected function _toHtml()
    {
        $infoBlock = $this->createBlock(
            'Ebay_Template_Category_Chooser_Specific_Info',
            '',
            [
                'data' => [
                    'category_mode'  => $this->getData('category_mode'),
                    'category_value' => $this->getData('category_value'),
                    'marketplace_id' => $this->getData('marketplace_id')
                ]
            ]
        );

        $this->jsTranslator->addTranslations(
            [
                'Item Specifics cannot have the same Labels.' => $this->__(
                    'Item Specifics cannot have the same Labels.'
                ),
            ]
        );
        $this->jsPhp->addConstants(
            $this->getHelper('Data')->getClassConstants(\Ess\M2ePro\Model\Ebay\Template\Category\Specific::class)
        );

        $this->js->add(<<<JS

    require([
        'M2ePro/Ebay/Template/Category/Specifics'
    ], function(){

        window.EbayTemplateCategorySpecificsObj = new EbayTemplateCategorySpecifics();
    });
JS
        );

        $parentHtml = parent::_toHtml();

        return <<<HTML
<div id="chooser_container_specific">

    <div style="margin-top: 15px;">
        {$infoBlock->_toHtml()}
    </div>
    
    <div id="ebay-category-chooser-specific" overflow: auto;">
        {$parentHtml}
    </div>
    
</div>
HTML;
    }

    //########################################
}
