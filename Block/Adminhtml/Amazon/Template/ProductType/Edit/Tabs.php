<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Template\ProductType\Edit;

class Tabs extends \Ess\M2ePro\Block\Adminhtml\Magento\Tabs\AbstractTabs
{
    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;
    /** @var \Ess\M2ePro\Model\Amazon\Template\ProductType $productType */
    private $productType;

    /**
     * @param \Ess\M2ePro\Helper\Data $dataHelper
     * @param \Ess\M2ePro\Model\Amazon\Template\ProductType $productType
     * @param \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context
     * @param \Magento\Framework\Json\EncoderInterface $jsonEncoder
     * @param \Magento\Backend\Model\Auth\Session $authSession
     * @param array $data
     */
    public function __construct(
        \Ess\M2ePro\Helper\Data $dataHelper,
        \Ess\M2ePro\Model\Amazon\Template\ProductType $productType,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Magento\Backend\Model\Auth\Session $authSession,
        array $data = []
    ) {
        parent::__construct($context, $jsonEncoder, $authSession, $data);
        $this->dataHelper = $dataHelper;
        $this->productType = $productType;
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

    /**
     * @return \Ess\M2ePro\Block\Adminhtml\Amazon\Template\ProductType\Edit\Tabs
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Exception
     */
    protected function _prepareLayout(): Tabs
    {
        $this->addTab(
            'general',
            [
                'label' => $this->__('General'),
                'title' => $this->__('General'),
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

    /**
     * @return \Ess\M2ePro\Block\Adminhtml\Amazon\Template\ProductType\Edit\Tabs|\Magento\Backend\Block\Widget\Tabs
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function _beforeToHtml()
    {
        $this->jsUrl->addUrls($this->dataHelper->getControllerActions('Amazon_Template_ProductType'));
        $this->jsUrl->addUrls([
            'formSubmit'    => $this->getUrl(
                '*/amazon_template_productType/save',
                ['_current' => true]
            ),
            'deleteAction'  => $this->getUrl(
                '*/amazon_template_productType/delete',
                ['_current' => true]
            ),
        ]);

        return parent::_beforeToHtml();
    }
}
