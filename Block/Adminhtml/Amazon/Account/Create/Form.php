<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Account\Create;

class Form extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm
{
    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;
    /** @var \Ess\M2ePro\Helper\Component\Amazon */
    private $amazonHelper;

    /**
     * @param \Ess\M2ePro\Helper\Data $dataHelper
     * @param \Ess\M2ePro\Helper\Component\Amazon $amazonHelper
     * @param \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param array $data
     */
    public function __construct(
        \Ess\M2ePro\Helper\Data $dataHelper,
        \Ess\M2ePro\Helper\Component\Amazon $amazonHelper,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        array $data = []
    ) {
        parent::__construct($context, $registry, $formFactory, $data);
        $this->dataHelper = $dataHelper;
        $this->amazonHelper = $amazonHelper;
    }

    protected function _prepareForm()
    {
        $marketplaces = $this->getMarketplacesList();

        $form = $this->_formFactory->create(
            [
                'data' => [
                    'id'    => 'edit_form',
                ]
            ]
        );

        $fieldset = $form->addFieldset(
            'account_general_info',
            [
                'legend' => ''
            ]
        );

        $fieldset->addField(
            'title',
            'text',
            [
                'name' => 'title',
                'class' => 'M2ePro-account-title',
                'label' => __('Title'),
                'required' => true,
                'style' => 'width: 50%',
                'tooltip' => __('Title or Identifier of Amazon Account for your internal use.'),
            ]
        );

        $fieldset->addField(
            'marketplace_id',
            'select',
            [
                'label' => __('Marketplace'),
                'name' => 'marketplace_id',
                'required' => true,
                'values' => $marketplaces,
            ]
        );

        $this->jsUrl->addUrls([
            'formSubmit'   => $this->getUrl('*/amazon_account/create'),
        ]);
        $this->jsPhp->addConstants($this->dataHelper->getClassConstants(\Ess\M2ePro\Helper\Component\Amazon::class));
        $this->js->add(
            <<<JS
    require([
        'M2ePro/Amazon/Account/Create',
    ], function(){
        window.AmazonAccountCreateObj = new AmazonAccountCreate();
    });
JS
        );
        $this->jsTranslator->addTranslations([
            'The specified Title is already used for other Account. Account Title must be unique.' => __(
                'The specified Title is already used for other Account. Account Title must be unique.'
            ),
        ]);

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    /**
     * @return array[]
     */
    private function getMarketplacesList(): array
    {
        $collection = $this->amazonHelper->getMarketplacesAvailableForApiCreation();

        $marketplaces = [''];
        foreach ($collection->getItems() as $item) {
            $parentData = $item->getData();
            $marketplaces[$parentData['id']] = $parentData['title'];
        }

        return $marketplaces;
    }
}
