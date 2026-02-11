<?php

declare(strict_types=1);

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Template\Repricer\Edit;

class Form extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm
{
    private \Ess\M2ePro\Model\Walmart\Template\Repricer $repricerTemplate;
    private \Ess\M2ePro\Block\Adminhtml\Walmart\Template\Repricer\Edit\Form\RepricerFieldset $repricerFieldset;
    private \Ess\M2ePro\Block\Adminhtml\Walmart\Template\Repricer\Edit\Form\GeneralFieldset $generalFieldset;
    private \Ess\M2ePro\Model\Walmart\Template\Repricer\BuilderFactory $builderFactory;

    public function __construct(
        \Ess\M2ePro\Model\Walmart\Template\Repricer\BuilderFactory $builderFactory,
        \Ess\M2ePro\Block\Adminhtml\Walmart\Template\Repricer\Edit\Form\GeneralFieldset $generalFieldset,
        \Ess\M2ePro\Block\Adminhtml\Walmart\Template\Repricer\Edit\Form\RepricerFieldset $repricerFieldset,
        \Ess\M2ePro\Model\Walmart\Template\Repricer $repricerTemplate,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        array $data = []
    ) {
        $this->repricerTemplate = $repricerTemplate;
        $this->repricerFieldset = $repricerFieldset;
        $this->generalFieldset = $generalFieldset;
        $this->builderFactory = $builderFactory;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    public function _construct()
    {
        parent::_construct();

        $this->setId('walmartTemplateRepricerEditForm');
    }

    protected function _prepareForm()
    {
        $form = $this->_formFactory->create([
            'data' => [
                'id' => 'edit_form',
                'method' => 'post',
                'action' => $this->getUrl('*/*/save'),
                'enctype' => 'multipart/form-data',
            ],
        ]);
        $this->setForm($form);

        $formData = array_merge(
            $this->builderFactory->create()->getDefaultData(),
            $this->repricerTemplate->getData()
        );

        $this->generalFieldset->add($form, $formData);
        $this->repricerFieldset->add($form, $formData);

        // ---------------------------------------

        $form->setUseContainer(true);

        return parent::_prepareForm();
    }
}
