<?php

namespace Ess\M2ePro\Block\Adminhtml\Walmart\ProductType\Edit;

class Tabs extends \Ess\M2ePro\Block\Adminhtml\Magento\Tabs\AbstractTabs
{
    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;
    /** @var \Ess\M2ePro\Model\Walmart\ProductType $productType */
    private $productType;

    public function __construct(
        \Ess\M2ePro\Helper\Data $dataHelper,
        \Ess\M2ePro\Model\Walmart\ProductType $productType,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Magento\Backend\Model\Auth\Session $authSession,
        array $data = []
    ) {
        parent::__construct($context, $jsonEncoder, $authSession, $data);
        $this->dataHelper = $dataHelper;
        $this->productType = $productType;
    }

    public function _construct()
    {
        parent::_construct();

        $this->setId('walmartProductTypeEditTabs');
        $this->setDestElementId('tabs_edit_form_data');
    }

    protected function _prepareLayout(): Tabs
    {
        $this->addTab(
            'general',
            [
                'label' => $this->__('General'),
                'title' => $this->__('General'),
                'content' => $this->getLayout()
                    ->createBlock(
                        \Ess\M2ePro\Block\Adminhtml\Walmart\ProductType\Edit\Tabs\General::class,
                        '',
                        ['productType' => $this->productType]
                    )
                    ->toHtml(),
            ]
        );

        $this->addTab(
            'template',
            [
                'label' => '%title%',
                'title' => '%title%',
                'is_hidden' => true,
                'content' => $this->getLayout()
                    ->createBlock(
                        \Ess\M2ePro\Block\Adminhtml\Walmart\ProductType\Edit\Tabs\Template::class
                    )
                    ->toHtml(),
            ]
        );

        $this->setActiveTab($this->getRequest()->getParam('tab', 'general'));

        return parent::_prepareLayout();
    }

    public function _beforeToHtml()
    {
        $this->jsUrl->addUrls($this->dataHelper->getControllerActions('Walmart_ProductType'));
        $this->jsUrl->addUrls([
            'formSubmit'    => $this->getUrl(
                '*/walmart_productType/save',
                ['id' => $this->productType->getId()]
            ),
            'deleteAction'  => $this->getUrl(
                '*/walmart_productType/delete',
                ['id' => $this->productType->getId()]
            ),
        ]);

        return parent::_beforeToHtml();
    }
}
