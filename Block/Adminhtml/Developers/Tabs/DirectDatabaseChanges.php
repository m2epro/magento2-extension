<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
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
<p>
M2E Pro is developed to work based on a standard Magento functionality. One of the main aspects of its work is a 
dynamic event catching: the Product Price, Quantity, Image, Attribute, etc. changes.
</p><br>
<p>
If you update Magento Product Information not via Magento backend and Standard Magento Model functionality 
(e.g. direct SQL injections or Custom Code that does not support a Magento Core Models), 
you can use the predefined M2E Pro Models to notify Extension about the Product changes.<br>
More detailed information can be found <a href="%url1%" target="_blank" class="external-link">here</a>.
</p><br>
<p>
It is <strong>highly recommended</strong> to use the option above to decrease the impact on M2E Pro performance.
</p><br>
<p>
Alternatively, you can enable <strong>Track Direct Database Changes</strong> to detect the Product changes.
</p><br>
<p>
<strong>Important note:</strong> the tracking of direct Database changes is resource-consuming and may affect the 
performance of your Magento Site and Synchronization with Channels. 
Set 'Yes' only in case of extreme necessity when the use of predefined M2E Pro Models is impossible for some reasons.
</p>
HTML
                    ,
                    $this->getHelper('Module\Support')->getDocumentationArticleUrl('x/xYQVAQ')
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