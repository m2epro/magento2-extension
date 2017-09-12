<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Template\Description\Edit\Tabs;

class Specifics extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm
{
    protected $_template = 'amazon/template/description/tabs/specifics.phtml';

    public $formData = [];

    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('amazonTemplateDescriptionEditTabsSpecifics');
        // ---------------------------------------

        $this->formData = $this->getFormData();
    }

    //########################################

    public function getFormData()
    {
        $formData = [];
        $template = $this->getHelper('Data\GlobalData')->getValue('tmp_template');

        if ($template->getId()) {
            $formData = $template->getChildObject()->getSpecifics();
        }

        return $formData;
    }

    //########################################

    protected function _beforeToHtml()
    {
        $helpBlock = $this->createBlock('HelpBlock')->setData([
            'content' => $this->__(
                'On this Tab you can specify Product Specifics for more detailed Description of its properties.
                List of available Specifics determines for the Category that you have chosen on General Tab. <br/><br/>
                To add new Specifics you can use “Add Specifics” Button at the top of a Tab or click “Plus”
                icon at the top right corner of a group to add Specifics to one particular group. Use search
                and filters to find Specifics you need. Specifics have nested structure so the same
                Specific can be used in different groups. <br/><br/>

                There is a list of required Specifics that should be specified. Recommended Specifics
                by Amazon are marked with a “Desired” label. Such Specifics are not mandatory,
                though they are recommended to be specified. <br/><br/>

                You can delete added Specifics by clicking a cross icon on the right.
                Some Specifics have a duplication Option (there is a copy icon on the right),
                i.e. you can specify several values for one Specific at the same time. <br/><br/>

                You can choose between 3 modes to specify the Specifics values:

                <ul class="list">
                    <li>Custom Value – you should set value manually;</li>
                    <li>Custom Attribute - selecting of Magento Attribute,
                    that will be a source for a Specific value;</li>
                    <li>Recommended Value (optional) - selecting of value from the list of predefined values.</li>
                </ul>
                <br/>
                More detailed information about ability to work with this Page you can find
                <a href="%url%" target="_blank" class="external-link">here</a>.',
                $this->getHelper('Module\Support')->getDocumentationArticleUrl('x/YAYtAQ')
            )
        ]);

        $this->setChild('help_block', $helpBlock);

        $this->css->add(<<<CSS
        a.specific-clone-button {
            display: inline-block;
            width: 20px;
            height: 16px;
            background: no-repeat center;
            background-image: url("{$this->getViewFileUrl('Ess_M2ePro::images/duplicate.png')}");
        }

CSS
);
        $this->jsTranslator->addTranslations([
            'Add Specifics'        => $this->__('Add Specifics'),
            'Remove this specific' => $this->__('Remove this specific'),

            'Total digits (not more):' => $this->__('Total digits (not more):'),
            'Type: Numeric.' => $this->__('Type: Numeric.'),
            'Min:'           => $this->__('Min:'),
            'Max:'           => $this->__('Max:'),

            'Can take any value.' => $this->__('Can take any value.'),
            'Two uppercase letters or "unknown".' => $this->__('Two uppercase letters or "unknown".'),
            'The value is incorrect.' => $this->__('The value is incorrect.'),
            'Type: String.'   => $this->__('Type: String.'),
            'Min length:'     => $this->__('Min length:'),
            'Max length:'     => $this->__('Max length:'),

            'Type: Date time. Format: YYYY-MM-DD hh:mm:ss' => $this->__('Type: Date time. Format: YYYY-MM-DD hh:mm:ss'),
            'Type: Numeric floating point.'                => $this->__('Type: Numeric floating point.'),
            'Decimal places (not more):'                   => $this->__('Decimal places (not more):'),

            'Recommended Values' => $this->__('Recommended Values'),
            'Allowed Values'     => $this->__('Allowed Values'),
            'Custom Attribute'   => $this->__('Custom Attribute'),
            'Custom Value'       => $this->__('Custom Value'),
            'None'               => $this->__('None'),

            'Definition:'    => $this->__('Definition:'),
            'Tips:'          => $this->__('Tips:'),
            'Examples:'      => $this->__('Examples:'),
            'Desired'        => $this->__('Desired'),

            'Duplicate specific' => $this->__('Duplicate specific'),
            'Delete specific'    => $this->__('Delete specific'),
            'Add Specific into current container' => $this->__('Add Specific into current container'),

            'Value of this Specific can be automatically overwritten by M2E Pro.' => $this->__(
                'In case this Description Policy will be used to create New Amazon Child Products,
                value of this Specific can be automatically overwritten by M2E Pro.
                Below there is a list of Variation Themes for which the value of this
                Specific will be overwritten if you are using one of them in your Listing.'
            ),
            'Amazon Parentage Specific will be overridden notice.' =>
                'The Value of this Specific can be necessary due to technical reasons,
                so there is no ability to Edit the Attribute parentage and also it has no semantic load.
                In case this Description Policy uses for creation of new Amazon Parent-Child Product,
                this Value will be overwritten and the Value you selected will not be/cannot be used.'
        ]);

        $formData = $this->getHelper('Data')->jsonEncode($this->formData);
        $this->js->add("
            wait(
                function() { return typeof AmazonTemplateDescriptionCategorySpecificObj != 'undefined'; },
                function() { AmazonTemplateDescriptionCategorySpecificObj.setFormDataSpecifics({$formData}); },
                50
            );

        ");

        return parent::_beforeToHtml();
    }

    //########################################
}