<?php

declare(strict_types=1);

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Template\Repricer;

use Ess\M2ePro\Controller\Adminhtml\Walmart\Template;

class Save extends Template
{
    private \Ess\M2ePro\Helper\Data $dataHelper;
    private \Ess\M2ePro\Model\Walmart\Template\Repricer\SnapshotBuilderFactory $snapshotBuilderFactory;
    private \Ess\M2ePro\Model\Walmart\Template\Repricer\BuilderFactory $builderFactory;
    private \Ess\M2ePro\Model\Walmart\Template\Repricer\DiffFactory $diffFactory;
    private \Ess\M2ePro\Model\Walmart\Template\Repricer\Repository $templateRepricerRepository;
    private \Ess\M2ePro\Model\Walmart\Template\RepricerFactory $templateRepricerFactory;
    private \Ess\M2ePro\Model\Walmart\Template\Repricer\AffectedListingsProductsFactory $affectedListingsProductsFactory;
    private \Ess\M2ePro\Model\Walmart\Template\Repricer\ChangeProcessorFactory $changeProcessorFactory;

    public function __construct(
        \Ess\M2ePro\Helper\Data $dataHelper,
        \Ess\M2ePro\Model\Walmart\Template\Repricer\ChangeProcessorFactory $changeProcessorFactory,
        \Ess\M2ePro\Model\Walmart\Template\Repricer\Repository $templateRepricerRepository,
        \Ess\M2ePro\Model\Walmart\Template\RepricerFactory $templateRepricerFactory,
        \Ess\M2ePro\Model\Walmart\Template\Repricer\BuilderFactory $builderFactory,
        \Ess\M2ePro\Model\Walmart\Template\Repricer\SnapshotBuilderFactory $snapshotBuilderFactory,
        \Ess\M2ePro\Model\Walmart\Template\Repricer\AffectedListingsProductsFactory $affectedListingsProductsFactory,
        \Ess\M2ePro\Model\Walmart\Template\Repricer\DiffFactory $diffFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($walmartFactory, $context);

        $this->dataHelper = $dataHelper;
        $this->snapshotBuilderFactory = $snapshotBuilderFactory;
        $this->builderFactory = $builderFactory;
        $this->diffFactory = $diffFactory;
        $this->templateRepricerRepository = $templateRepricerRepository;
        $this->templateRepricerFactory = $templateRepricerFactory;
        $this->affectedListingsProductsFactory = $affectedListingsProductsFactory;
        $this->changeProcessorFactory = $changeProcessorFactory;
    }

    public function execute()
    {
        $post = $this->getRequest()->getPost();

        if (!$post->count()) {
            $this->_forward('index');

            return;
        }

        $templateId = (int)$this->getRequest()->getParam('id');
        if ($templateId > 0) {
            $repricerTemplate = $this->templateRepricerRepository->get($templateId);
        } else {
            $repricerTemplate = $this->templateRepricerFactory->create();
        }

        $oldData = [];
        if (!$repricerTemplate->isObjectNew()) {
            $snapshotBuilder = $this->snapshotBuilderFactory->create();
            $snapshotBuilder->setModel($repricerTemplate);

            $oldData = $snapshotBuilder->getSnapshot();
        }

        $this->builderFactory->create()->build($repricerTemplate, $post->toArray());

        $snapshotBuilder = $this->snapshotBuilderFactory->create();
        $snapshotBuilder->setModel($repricerTemplate);
        $newData = $snapshotBuilder->getSnapshot();

        $diff = $this->diffFactory->create();
        $diff->setNewSnapshot($newData);
        $diff->setOldSnapshot($oldData);

        $affectedListingsProducts = $this->affectedListingsProductsFactory->create();
        $affectedListingsProducts->setModel($repricerTemplate);

        $changeProcessor = $this->changeProcessorFactory->create();
        $changeProcessor->process(
            $diff,
            $affectedListingsProducts->getObjectsData(['id', 'status'])
        );

        // ---------------------------------------

        if ($this->isAjax()) {
            $this->setJsonContent([
                'status' => true,
            ]);

            return $this->getResult();
        }

        // ---------------------------------------

        $this->messageManager->addSuccess(__('Policy was saved'));

        return $this->_redirect(
            $this->dataHelper->getBackUrl(
                '*/walmart_template/index',
                [],
                [
                    'edit' => [
                        'id' => $repricerTemplate->getId(),
                        'wizard' => $this->getRequest()->getParam('wizard'),
                        'close_on_save' => $this->getRequest()->getParam('close_on_save'),
                    ],
                ]
            )
        );
    }
}
