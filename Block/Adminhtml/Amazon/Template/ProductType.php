<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Template;

class ProductType extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractContainer
{
    /** @var \Ess\M2ePro\Helper\Module\Support */
    private $supportHelper;

    /**
     * @param \Ess\M2ePro\Block\Adminhtml\Magento\Context\Widget $context
     * @param \Ess\M2ePro\Helper\Module\Support $supportHelper
     * @param array $data
     */
    public function __construct(
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Widget $context,
        \Ess\M2ePro\Helper\Module\Support $supportHelper,
        array $data = []
    ) {
        $this->supportHelper = $supportHelper;
        parent::__construct($context, $data);
    }

    /**
     * @return void
     */
    public function _construct()
    {
        parent::_construct();

        $this->setId('amazonTemplateProductType');
        $this->_controller = 'adminhtml_amazon_template_productType';

        $this->buttonList->remove('back');
        $this->buttonList->remove('reset');
        $this->buttonList->remove('delete');
        $this->buttonList->remove('save');
        $this->buttonList->remove('edit');

        $this->buttonList->update('add', 'label', $this->__('Add Product Type'));
        $this->buttonList->update('add', 'onclick', '');
    }

    /**
     * @return \Ess\M2ePro\Block\Adminhtml\Amazon\Template\ProductType|\Magento\Backend\Block\Widget\Grid\Container
     */
    protected function _prepareLayout()
    {
        $content = $this->__(
            'The page displays Amazon Product Types that are currently used in your M2E Pro Listings.<br/><br/>

            Here you can add a new Product Type, edit or delete existing ones.
            Learn how to manage Amazon Product Types in
            <a href="%url%" target="_blank" class="external-link">this article</a>.',
            $this->supportHelper->getDocumentationArticleUrl('description-policies')
        );

        $this->appendHelpBlock(
            [
                'content' => $content,
            ]
        );

        $url = $this->getUrl('*/amazon_template_productType/edit');
        $this->addButton(
            'add',
            [
                'label' => $this->__('Add Product Type'),
                'onclick' => 'setLocation(\'' . $url . '\')',
                'class' => 'action-primary',
                'button_class' => '',
            ]
        );

        return parent::_prepareLayout();
    }
}
