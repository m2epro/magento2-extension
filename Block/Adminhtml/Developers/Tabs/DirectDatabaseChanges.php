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
    protected $synchronizationConfig;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\Config\Manager\Synchronization $synchronizationConfig,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        array $data = []
    ) {
        $this->synchronizationConfig = $synchronizationConfig;
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
<p>M2E Pro is developed to work based on a standard Magento functionality.
One of the main aspects of its work is a dynamic event catching: the Product Price,
Quantity, Image, Attribute, etc. changes.</p><br>
<p>
If you update Magento Product information not via Magento backend and Standard Magento Model functionality
(e.g. direct SQL injections or Custom Code that does not support a Magento Core Models),
you can use the predefined M2E Pro Models to notify Extension about the product changes.
The details can be found <a href="%url1%" target="_blank" class="external-link">here</a>.
</p><br>

<p>
If you use Magmi Import tool to update Magento Product information, it is required to set up a
predefined M2E Pro plug-in for Magmi Import tool. It will notify Extension about the changes made to product data.
Please read more <a href="%url2%" target="_blank" class="external-link">here</a>.
</p><br>

<p>
It is strongly recommended that you use one of the options above to decrease the impact on M2E Pro performance.
</p><br>
<p>
Alternatively, you can enable Track Direct Database Changes to detect the product changes.
</p><br>
<p>
<strong>Important note:</strong> The tracking of direct Database changes is resource-consuming and may
affect the performance of your Magento site and synchronization with Channels. Set Yes only in case of
extreme necessity when using of predefined M2E Pro Models is impossible for some reasons.
</p>
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

        $inspectorMode = (int)$this->synchronizationConfig->getGroupValue(
            '/global/magento_products/inspector/',
            'mode'
        );

        $button = $this->createBlock('Magento\Button', '', ['data' => [
            'id' => 'save_inspector_mode',
            'label' => $this->__('Save'),
            'onclick' => 'DevelopersObj.saveDirectDatabaseChanges()',
            'style' => 'display: none;',
            'class' => 'primary'
        ]]);

        $fieldSet->addField(
            'inspector_mode',
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
