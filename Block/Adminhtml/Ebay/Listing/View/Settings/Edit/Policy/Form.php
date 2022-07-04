<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\View\Settings\Edit\Policy;

class Form extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm
{
    /** @var \Ess\M2ePro\Helper\Module\Support */
    private $supportHelper;

    public function __construct(
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Ess\M2ePro\Helper\Module\Support $supportHelper,
        array $data = []
    ) {
        $this->supportHelper = $supportHelper;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    protected function _prepareLayout()
    {
        $form = $this->_formFactory->create(['data' => [
            'id' => 'edit_form',
            'action'  => $this->getUrl('*/ebay_template/save'),
            'method' => 'post',
            'enctype' => 'multipart/form-data'
        ]]);

        $templateNick = $this->getRequest()->getParam('templateNick');

        $switcherBlock = $this->getLayout()->createBlock(
            \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Template\Switcher::class,
            '',
            ['data' => [
                'template_nick' => $templateNick,
                'policy_localization' => $this->getData('policy_localization'),
                'custom_header_text' => $this->__('Source Mode')
            ]]
        );

        $form->addField(
            'template_wrapper',
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
            \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_RETURN_POLICY => 'x/dgAVB',
            \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_SHIPPING => 'x/YgAVB',
            \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_DESCRIPTION => 'x/ff8UB ',
            \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_SELLING_FORMAT =>
                'x/e-8UB',
            \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_SYNCHRONIZATION => 'x/Y-8UB',
        ];

        if (!isset($articles[$templateNick])) {
            return '';
        }

        $helpLinkBlock = $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\PageHelpLink::class)->setData([
            'page_help_link' => $this->supportHelper->getDocumentationArticleUrl(
                $articles[$templateNick]
            )
        ]);

        return '<div id="popup_template_help_link">'
        . $helpLinkBlock->toHtml()
        . '</div>' ;
    }
}
