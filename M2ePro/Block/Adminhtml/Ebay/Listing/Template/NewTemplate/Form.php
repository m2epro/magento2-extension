<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Template\NewTemplate;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Template\NewTemplate\Form
 */
class Form extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm
{
    protected function _prepareForm()
    {
        if ($this->getData('nick') == '') {
            throw new \Ess\M2ePro\Model\Exception\Logic('You should set template "nick"');
        }

        $form = $this->_formFactory->create(
            ['data' => [
                'id' => 'new_template_form_' . $this->getData('nick'),
                'action' => 'javascript:void(0)',
                'method' => 'post'
            ]]
        );

        $form->addField(
            'new_template_form_help_block',
            self::HELP_BLOCK,
            [
                'content' => $this->__(
                    '<p>Saving Policy under a distinctive title will let you easily and quickly search for
                    it in case you need to use it in a different M2E Pro Listing in the future.</p><br>
                    <p>More detailed information you can find
                    <a href="%url%" target="_blank" class="external-link">here</a>.</p>',
                    $this->getHelper('Module\Support')->getDocumentationArticleUrl('x/8wItAQ')
                )
            ]
        );

        $fieldset = $form->addFieldset(
            'new_template_fieldset',
            []
        );

        $fieldset->addField(
            'template_title_' . $this->getData('nick'),
            'text',
            [
                'name'        => $this->getData('nick').'[template_title]',
                'class'       => 'M2ePro-validate-ebay-template-title',
                'label'       => $this->__('Title'),
                'placeholder' => $this->__('Please specify Policy Title'),
                'required'    => true,
            ]
        );

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }
}
