<?php

declare(strict_types=1);

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Template\Repricer;

use Ess\M2ePro\Controller\Adminhtml\Walmart\Template;

class Edit extends Template
{
    private \Ess\M2ePro\Model\Walmart\Template\Repricer\Repository $templateRepricerRepository;
    private \Ess\M2ePro\Model\Walmart\Template\RepricerFactory $templateRepricerFactory;

    public function __construct(
        \Ess\M2ePro\Model\Walmart\Template\Repricer\Repository $templateRepricerRepository,
        \Ess\M2ePro\Model\Walmart\Template\RepricerFactory $templateRepricerFactory,
        \Ess\M2ePro\Helper\Data $dataHelper,
        \Ess\M2ePro\Helper\Data\GlobalData $globalData,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($walmartFactory, $context);

        $this->templateRepricerRepository = $templateRepricerRepository;
        $this->templateRepricerFactory = $templateRepricerFactory;
    }

    public function execute()
    {
        $templateId = (int)$this->getRequest()->getParam('id');

        if ($templateId > 0) {
            $template = $this->templateRepricerRepository->find($templateId);
            if ($template === null) {
                $this->messageManager->addError(__('Policy does not exist'));

                return $this->_redirect('*/walmart_template/index');
            }
        } else {
            $template = $this->templateRepricerFactory->create();
        }

        if ($template->isObjectNew()) {
            $headerText = __("Add Repricer Policy");
        } else {
            $headerText = __('Edit Repricer Policy "%template_title"', [
                'template_title' => $template->getTitle()
            ]);
        }

        $this->getResultPage()->getConfig()->getTitle()->prepend($headerText);

        $this->setPageHelpLink('docs/walmart-repricer-policy/');

        $templateBlock = $this
            ->getLayout()
            ->createBlock(
                \Ess\M2ePro\Block\Adminhtml\Walmart\Template\Repricer\Edit::class,
                '',
                [
                    'repricerTemplate' => $template,
                ]
            );

        $this->addContent($templateBlock);

        return $this->getResultPage();
    }
}
