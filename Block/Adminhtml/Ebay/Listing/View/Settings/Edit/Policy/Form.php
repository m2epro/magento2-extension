<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\View\Settings\Edit\Policy;

class Form extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm
{

    //########################################

    protected function _prepareLayout()
    {
        $form = $this->_formFactory->create(['data' => [
            'id' => 'edit_form',
            'action'  => $this->getUrl('*/ebay_template/save'),
            'method' => 'post',
            'enctype' => 'multipart/form-data'
        ]]);

        $templateNick = $this->getRequest()->getParam('templateNick');

        $switcherBlock = $this->createBlock(
            'Ebay\Listing\Template\Switcher','',
            ['data' => [
                'template_nick' => $templateNick,
                'policy_localization' => $this->getData('policy_localization'),
                'custom_header_text' => $this->__('Source Mode')
            ]]
        );

        $form->addField('template_wrapper',
            self::CUSTOM_CONTAINER,
            [
                'text' => $switcherBlock->toHtml()
            ]
        );

        $form->setUseContainer(true);
        $this->setForm($form);
        parent::_prepareLayout();
    }

    protected function _toHtml()
    {
        $templateNick = $this->getRequest()->getParam('templateNick');

        return $this->getHelpLinkHtmlForTemplate($templateNick)
               . parent::_toHtml();
    }

    protected function getHelpLinkHtmlForTemplate($templateNick)
    {
        $articles = [
            \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_RETURN_POLICY => 'x/TgMtAQ',
            \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_PAYMENT => 'x/LwMtAQ',
            \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_SHIPPING => 'x/OgMtAQ',
            \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_DESCRIPTION => 'x/VQItAQ ',
            \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_SELLING_FORMAT =>
                'x/UwItAQ',
            \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_SYNCHRONIZATION => 'x/OwItAQ',
        ];

        if (!isset($articles[$templateNick])) {
            return '';
        }

        $helpLinkBlock = $this->createBlock('PageHelpLink')->setData([
            'page_help_link' => $this->getHelper('Module\Support')->getDocumentationArticleUrl(
                $articles[$templateNick]
            )
        ]);

        return '<div id="popup_template_help_link">'
        . $helpLinkBlock->toHtml()
        . '</div>' ;
    }

    //########################################
}