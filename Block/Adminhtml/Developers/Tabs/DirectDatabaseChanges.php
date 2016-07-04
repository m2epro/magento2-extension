<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Developers\Tabs;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm;

class DirectDatabaseChanges extends AbstractForm
{
    protected $synchronizationConfig;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\Config\Manager\Synchronization $synchronizationConfig,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        array $data = []
    )
    {
        $this->synchronizationConfig = $synchronizationConfig;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    //########################################

    protected function _prepareForm()
    {
        $form = $this->_formFactory->create();

        $urlComponents = $this->getHelper('Component')->getEnabledComponents();
        $componentForUrl = count($urlComponents) == 1
            ? array_shift($urlComponents) : \Ess\M2ePro\Helper\Component\Ebay::NICK;

        $form->addField('block_notice_configuration_advanced_settings',
            self::HELP_BLOCK,
            [
                'no_collapse' => true,
                'no_hide' => true,
                'content' => $this->__(
                    <<<HTML
                    <p>M2E Pro is developed to work based on standard Magento functionality. 
                    One of the main aspects of its work is a dynamic event catching of the Product Data
                    changes - Price, Quantity, Images, Attributes, etc.</p><br>
                    <p>However, if Product Data is changed via Magmi Import Tool,
                    M2E Pro will not catch all of the changes. It is related to the fact that Magmi Import Tool
                    (along with many other similar tools) makes changes directly in Magento Database
                    without any Core Magento Functions involved. Inability to catch the events of Product Data
                    change leads to inability to deliver these changes to the channels (eBay, Amazon, etc.).</p><br>
                    <p>If you are using Magmi Import Tool to update the Product Data,
                    which implements changes directly into the Magento Database, please, use a
                    <a href="%url1%" target="_blank">Magmi Plugin</a> However, if you are a developer and 
                    change the Product Data directly in the Database, you can use a predefined 
                    <a href="%url2%" target="_blank">M2E Pro Models</a></p><br>
                    <p>Only in case you cannot use these features, we would recommend you to enable <br> an 
                    additional option - <strong>Track Direct Database Changes</strong>.</p><br>
                    <p><strong>Warning:</strong> Track Вirect Database changes feature is resource-intensive and may 
                    affect Performance of your Magento Site and Synchronization with Channels. Choose 'Yes' only if you
                    cannot use other predefined M2E Pro Models and you are absolutely confident that you need to
                    use this functionality.</p>
HTML
                    ,
                    $this->getHelper('Module\Support')->getDocumentationUrl(NULL, NULL, 'x/yIQVAQ'),
                    $this->getHelper('Module\Support')->getDocumentationUrl(NULL, NULL, 'x/xYQVAQ')
                )
            ]
        );

        $fieldSet = $form->addFieldset(
            'direct_database_changes_field', ['legend' => false, 'collabsable' => false]
        );

        $inspectorMode = (int)$this->synchronizationConfig->getGroupValue(
            '/global/magento_products/inspector/','mode'
        );

        $button = $this->createBlock('Magento\Button', '', ['data' => [
            'id' => 'save_inspector_mode',
            'label' => $this->__('Save'),
            'onclick' => 'DevelopersObj.saveDirectDatabaseChanges()',
            'style' => 'display: none;',
            'class' => 'primary'
        ]]);

        $fieldSet->addField('inspector_mode',
            self::SELECT,
            [
                'name' => 'inspector_mode',
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

        $this->jsTranslator->add('Settings successfully saved', $this->__('Settings successfully saved'));

        $this->js->addRequireJs(['d' => 'M2ePro/Developers'], <<<JS

        window.DevelopersObj = new Developers();
JS
);

        return parent::_beforeToHtml();
    }

    //########################################
}