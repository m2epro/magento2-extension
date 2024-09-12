<?php

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Template\ProductType\Edit;

class Tabs extends \Ess\M2ePro\Block\Adminhtml\Magento\Tabs\AbstractTabs
{
    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;
    private \Ess\M2ePro\Model\Amazon\Template\ProductType $productType;

    public function __construct(
        \Ess\M2ePro\Helper\Data $dataHelper,
        \Ess\M2ePro\Model\Amazon\Template\ProductType $productType,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Magento\Backend\Model\Auth\Session $authSession,
        array $data = []
    ) {
        $this->dataHelper = $dataHelper;
        $this->productType = $productType;
        parent::__construct($context, $jsonEncoder, $authSession, $data);
    }

    /**
     * @return void
     */
    public function _construct()
    {
        parent::_construct();

        $this->setId('amazonTemplateProductTypeEditTabs');
        $this->setDestElementId('tabs_edit_form_data');
    }

    protected function _prepareLayout(): Tabs
    {
        $this->addTab(
            'general',
            [
                'label' => __('General'),
                'title' => __('General'),
                'content' => $this->getLayout()
                    ->createBlock(
                        \Ess\M2ePro\Block\Adminhtml\Amazon\Template\ProductType\Edit\Tabs\General::class,
                        '',
                        ['productType' => $this->productType]
                    )
                    ->toHtml(),
            ]
        );

        $this->addTab(
            'template',
            [
                'label' => $this->__('%title%'),
                'title' => $this->__('%title%'),
                'is_hidden' => true,
                'content' => $this->getLayout()
                    ->createBlock(
                        \Ess\M2ePro\Block\Adminhtml\Amazon\Template\ProductType\Edit\Tabs\Template::class
                    )
                    ->toHtml(),
            ]
        );

        $this->setActiveTab($this->getRequest()->getParam('tab', 'general'));

        return parent::_prepareLayout();
    }

    public function _beforeToHtml()
    {
        $this->jsUrl->addUrls($this->dataHelper->getControllerActions('Amazon_Template_ProductType'));
        $this->jsUrl->addUrls([
            'formSubmit'    => $this->getUrl(
                '*/amazon_template_productType/save',
                ['id' => $this->productType->getId()]
            ),
            'deleteAction'  => $this->getUrl(
                '*/amazon_template_productType/delete',
                ['id' => $this->productType->getId()]
            ),
        ]);

        return parent::_beforeToHtml();
    }
}
