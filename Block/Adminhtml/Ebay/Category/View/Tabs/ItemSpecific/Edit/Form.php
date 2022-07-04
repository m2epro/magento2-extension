<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Category\View\Tabs\ItemSpecific\Edit;

use Ess\M2ePro\Block\Adminhtml\Ebay\Template\Category\Chooser\Specific\Form\Renderer\Custom;
use Ess\M2ePro\Model\Ebay\Template\Category\Specific;
use \Ess\M2ePro\Block\Adminhtml\Ebay\Template\Category\Chooser\Specific\Form\Renderer\Dictionary;

class Form extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm
{
    /** @var \Ess\M2ePro\Helper\Component\Ebay\Category\Ebay */
    private $componentEbayCategoryEbay;

    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Component\Ebay\Category\Ebay $componentEbayCategoryEbay,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Ess\M2ePro\Helper\Data $dataHelper,
        array $data = []
    ) {
        $this->componentEbayCategoryEbay = $componentEbayCategoryEbay;
        $this->dataHelper = $dataHelper;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    protected function _prepareForm()
    {
        $form = $this->_formFactory->create(
            [
                'data' => [
                    'id'    => 'edit_form',
                    'action' => $this->getUrl('*/*/saveTemplateCategorySpecifics'),
                    'method' => 'post',
                    'enctype' => 'multipart/form-data'
                ]
            ]
        );

        $templateId = $this->getRequest()->getParam('template_id');

        $form->addField(
            'template_id',
            'hidden',
            [
                'name' => 'template_id',
                'value' => $templateId
            ]
        );

        /** @var \Ess\M2ePro\Model\Ebay\Template\Category $template */
        $template = $this->activeRecordFactory->getObjectLoaded(
            'Ebay_Template_Category',
            $templateId
        );

        $templateSpecifics = $template->getSpecifics();
        $dictionarySpecifics = (array)$this->componentEbayCategoryEbay->getSpecifics(
            $template->getCategoryId(),
            $template->getMarketplaceId()
        );

        foreach ($dictionarySpecifics as &$dictionarySpecific) {
            foreach ($templateSpecifics as $templateSpecific) {
                if ($dictionarySpecific['title'] == $templateSpecific['attribute_title']) {
                    $dictionarySpecific['template_specific'] = $templateSpecific;
                    continue;
                }
            }
        }

        unset($dictionarySpecific);

        if (!empty($dictionarySpecifics)) {
            $fieldset = $form->addFieldset(
                'dictionary',
                [
                    'legend' => $this->__('eBay Specifics'),
                    'collapsable' => false
                ]
            );

            /** @var Dictionary $renderer
             */
            $renderer = $this->getLayout()->createBlock(Dictionary::class);
            $fieldset->addField(
                'dictionary_specifics',
                \Ess\M2ePro\Block\Adminhtml\Ebay\Template\Category\Chooser\Specific\Form\Element\Dictionary::class,
                [
                    'specifics' => $dictionarySpecifics,
                ]
            )->setRenderer($renderer);
        }

        $templateCustomSpecifics = [];
        foreach ($templateSpecifics as $templateSpecific) {
            if ($templateSpecific['mode'] == Specific::MODE_CUSTOM_ITEM_SPECIFICS) {
                $templateCustomSpecifics[] = $templateSpecific;
            }
        }

        $fieldset = $form->addFieldset(
            'custom',
            [
                'legend' => $this->__('Additional Specifics'),
                'collapsable' => false
            ]
        );

        /** @var \Ess\M2ePro\Block\Adminhtml\Ebay\Template\Category\Chooser\Specific\Form\Renderer\Custom $renderer */
        $renderer = $this->getLayout()->createBlock(Custom::class);
        $fieldset->addField(
            'custom_specifics',
            \Ess\M2ePro\Block\Adminhtml\Ebay\Template\Category\Chooser\Specific\Form\Element\Custom::class,
            [
                'specifics' => $templateCustomSpecifics,
            ]
        )->setRenderer($renderer);

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    //########################################

    protected function _prepareLayout()
    {
        $this->jsTranslator->add(
            'Item Specifics cannot have the same Labels.',
            'Item Specifics cannot have the same Labels.'
        );

        $this->jsPhp->addConstants(
            $this->dataHelper->getClassConstants(Specific::class)
        );

        $this->js->addRequireJs([
            'etcs' => 'M2ePro/Ebay/Template/Category/Specifics'
        ], <<<JS
        window.EbayTemplateCategorySpecificsObj = new EbayTemplateCategorySpecifics();
JS
        );

        return parent::_prepareLayout();
    }

    //########################################
}
