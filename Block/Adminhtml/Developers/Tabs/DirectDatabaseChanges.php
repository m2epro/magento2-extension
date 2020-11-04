<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Developers\Tabs;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Developers\Tabs\DirectDatabaseChanges
 */
class DirectDatabaseChanges extends AbstractForm
{

    //########################################

    public function __construct(
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        array $data = []
    ) {
        parent::__construct($context, $registry, $formFactory, $data);
    }

    //########################################

    protected function _prepareForm()
    {
        $form = $this->_formFactory->create();

        $form->addField(
            'block_notice_configuration_advanced_settings',
            self::HELP_BLOCK,
            [
                'no_collapse' => true,
                'no_hide' => true,
                'content' => $this->__(
                    <<<HTML
<p>If you update Magento Product information over the Magento Core Models (e.g. direct SQL injections),
 use one of the options below to make M2E Pro detect these changes:</p>
 <ul>
<li>M2E Pro Models (Object or Structural Methods). Read <a target="_blank" href="%url1%"> the article</a> for more information.</li>
<li>M2E Pro plug-in for the Magmi Import tool. Learn the details <a target="_blank" href="%url2%">here</a>.</li>
<li>Track Direct Database Changes. Please note that this option is resource-consuming and may affect the 
performance of your Magento site and synchronization with Channels.</li>
</ul>
HTML
                    ,
                    $this->getHelper('Module\Support')->getDocumentationArticleUrl('x/oYFwAQ'),
                    $this->getHelper('Module\Support')->getDocumentationArticleUrl('x/moFwAQ')
                )
            ]
        );

        $fieldSet = $form->addFieldset(
            'direct_database_changes_field',
            ['legend' => false, 'collabsable' => false]
        );

        $inspectorMode = $this->getHelper('Module_Configuration')->isEnableListingProductInspectorMode();

        $button = $this->createBlock('Magento\Button', '', ['data' => [
            'id' => 'save_inspector_mode',
            'label' => $this->__('Save'),
            'onclick' => 'DevelopersObj.saveDirectDatabaseChanges()',
            'style' => 'display: none;',
            'class' => 'primary'
        ]]);

        $fieldSet->addField(
            'listing_product_inspector_mode',
            self::SELECT,
            [
                'name' => 'listing_product_inspector_mode',
                'label' => $this->__('Track Direct Database Changes'),
                'values' => [
                    ['value' => 0, 'label' => $this->__('No')],
                    ['value' => 1, 'label' => $this->__('Yes')]
                ],
                'value' => $inspectorMode,
                'after_element_html' => '&nbsp;&nbsp;&nbsp;'.$button->toHtml()
            ]
        );

        $this->setForm($form);
        return parent::_prepareForm();
    }

    //########################################

    protected function _beforeToHtml()
    {
        $this->jsUrl->add($this->getUrl('*/developers/save'), 'developers/save');

        $this->jsTranslator->add('Settings saved', $this->__('Settings saved'));

        $this->js->addRequireJs(['d' => 'M2ePro/Developers'], <<<JS

        window.DevelopersObj = new Developers();
JS
        );

        return parent::_beforeToHtml();
    }

    //########################################
}
