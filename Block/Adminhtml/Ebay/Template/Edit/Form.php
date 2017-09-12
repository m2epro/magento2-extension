<?php

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Template\Edit;

use Ess\M2ePro\Block\Adminhtml\Traits;
use Ess\M2ePro\Block\Adminhtml\Magento\Renderer;

class Form extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm
{
    private $stringUtils;
    private $templateManager;

    private $enabledMarketplaces = NULL;

    //########################################

    public function __construct(
        \Magento\Framework\Stdlib\StringUtils $stringUtils,
        \Ess\M2ePro\Model\Ebay\Template\Manager $templateManager,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        array $data = []
    )
    {
        $this->stringUtils = $stringUtils;
        $this->templateManager = $templateManager;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('ebayTemplateEditForm');
        // ---------------------------------------

        $this->css->addFile('ebay/template.css');
    }

    //########################################

    protected function _prepareForm()
    {
        $form = $this->_formFactory->create(['data' => [
            'id' => 'edit_form',
            'action' => 'javascript:void(0)',
            'method' => 'post',
            'enctype' => 'multipart/form-data'
        ]]);

        $fieldset = $form->addFieldset(
            'general_fieldset',
            ['legend' => __('General'), 'collapsable' => false]
        );

        $templateData = $this->getTemplateData();

        $fieldset->addField(
            'title',
            'text',
            [
                'name' => 'title',
                'label' => $this->__('Title'),
                'title' => $this->__('Title'),
                'value' => $templateData['title'],
                'class' => 'input-text validate-title-uniqueness',
                'required' => true
            ]
        );

        $templateNick = $this->getTemplateNick();
        if (
            $templateNick == \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_SHIPPING
            || $templateNick == \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_PAYMENT
            || $templateNick == \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_RETURN_POLICY
        ) {
            $fieldset->addField(
                'marketplace_id',
                'select',
                [
                    'name' => $this->getTemplateNick().'[marketplace_id]',
                    'label' => $this->__('Marketplace'),
                    'title' => $this->__('Marketplace'),
                    'values' => $this->getMarketplaceDataToOptions(),
                    'value' => $templateData['marketplace_id'],
                    'required' => true,
                    'disabled' => !empty($templateData['id'])
                ]
            );
        }

        $form->setUseContainer(true);
        $this->setForm($form);
        return $this;
    }

    public function getTemplateData()
    {
        $nick = $this->getTemplateNick();
        $templateData = $this->getHelper('Data\GlobalData')->getValue("ebay_template_{$nick}");
        return array_merge([
            'title' => '',
            'marketplace_id' => ''
        ],$templateData->getData());
    }

    //########################################

    public function getTemplateNick()
    {
        return $this->getParentBlock()->getTemplateNick();
    }

    public function getTemplateId()
    {
        $template = $this->getParentBlock()->getTemplateObject();

        return $template ? $template->getId() : NULL;
    }

    public function canDisplayMarketplace()
    {
        $manager = $this->templateManager->setTemplate($this->getTemplateNick());

        return $manager->isMarketplaceDependentTemplate();
    }

    public function getEnabledMarketplaces()
    {
        if (is_null($this->enabledMarketplaces)) {
            $collection = $this->activeRecordFactory->getObject('Marketplace')->getCollection();
            $collection->addFieldToFilter('component_mode', \Ess\M2ePro\Helper\Component\Ebay::NICK);
            $collection->addFieldToFilter('status', \Ess\M2ePro\Model\Marketplace::STATUS_ENABLE);
            $collection->setOrder('sorder', 'ASC');

            $this->enabledMarketplaces = $collection;
        }

        return $this->enabledMarketplaces->getItems();
    }

    public function getMarketplaceDataToOptions()
    {
        $optionsResult = [
            ['value' => '', 'label' => '']
        ];
        $helper = $this->getHelper('Data');
        foreach ($this->getEnabledMarketplaces() as $marketplace) {
            $optionsResult[] = [
                'value' => $marketplace->getId(),
                'label' => $helper->escapeHtml($marketplace->getTitle())
            ];
        }

        return $optionsResult;
    }

    public function getMarketplaceId()
    {
        $template = $this->getParentBlock()->getTemplateObject();

        if ($template) {
            return $template->getData('marketplace_id');
        }

        return NULL;
    }

    //########################################

    protected function _toHtml()
    {
        $nick = $this->getTemplateNick();
        $this->jsUrl->addUrls([
            'ebay_template/getTemplateHtml' => $this->getUrl('*/ebay_template/getTemplateHtml',
                [
                    'account_id' => NULL,
                    'id' => $this->getTemplateId(),
                    'nick' => $nick,
                    'mode' => \Ess\M2ePro\Model\Ebay\Template\Manager::MODE_TEMPLATE,
                    'data_force' => true
                ]
            ),
            'ebay_template/isTitleUnique' => $this->getUrl('*/ebay_template/isTitleUnique',
                [
                    'id' => $this->getTemplateId(),
                    'nick' => $nick
                ]
            ),
            'deleteAction' => $this->getUrl('*/ebay_template/delete',
                [
                    'id' => $this->getTemplateId(),
                    'nick' => $nick
                ]
            )
        ]);

        $this->jsTranslator->addTranslations([
            'Policy Title is not unique.' => $this->__('Policy Title is not unique.'),
            'Do not show any more' => $this->__('Do not show this message anymore'),
            'Save Policy' => $this->__('Save Policy'),
        ]);

        $this->js->addRequireJs([
            'form' => 'M2ePro/Ebay/Template/Edit/Form',
            'jquery' => 'jquery'
        ], <<<JS

        window.EbayTemplateEditObj = new EbayTemplateEdit();
        EbayTemplateEditObj.templateNick = '{$this->getTemplateNick()}';
        EbayTemplateEditObj.initObservers();
JS
        );

        return parent::_toHtml();
    }

    //########################################
}