<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Edit;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Ebay\Account\Edit\Form
 */
class Form extends AbstractForm
{
    /** @var \Ess\M2ePro\Model\Listing */
    protected $listing;

    /** @var \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory */
    protected $ebayFactory;
    /** @var \Ess\M2ePro\Model\ResourceModel\Listing\CollectionFactory */
    protected $listingCollectionFactory;
    protected $titles = [];

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Ess\M2ePro\Model\ResourceModel\Listing\CollectionFactory $listingCollectionFactory,
        array $data = []
    ) {
        $this->listingCollectionFactory = $listingCollectionFactory;
        $this->ebayFactory = $ebayFactory;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    protected function _prepareForm()
    {
        $form = $this->_formFactory->create(
            [
                'data' => [
                    'id' => 'edit_form',
                    'method' => 'post',
                    'action' => $this->getUrl('*/ebay_listing_edit/saveTitle', ['id' => $this->getRequest()->getParam('id'), 'titles' => $this->titles]),
                    'enctype' => 'multipart/form-data',
                ],
            ]
        );
        $this->getTitles();

        $titleFieldset = $form->addFieldset(
            'title',
            [
                'legend' => __('Title'),
                'collapsable' => false,
                'class' => 'fieldset-wide',
            ]
        );
        $titleFieldset->addField(
            'title_field',
            'text',
            [
                'name' => 'title',
                'label' => __('Title'),
                'title' => __('Title'),
                'required' => true,
                'class' => 'validate-no-html-tags',
                'value' => $this->getListing()->getData('title'),
            ]
        );
        $titleFieldset->addField(
            'titles',
            'hidden',
            [
                'name' => 'titles',
                'value' => implode(',', $this->titles),
            ]
        );

        $form->setUseContainer(true);
        $this->setForm($form);
        return parent::_prepareForm();
    }

    protected function getListing()
    {
        if ($this->listing === null && $this->getRequest()->getParam('id')) {
            $this->listing = $this->ebayFactory->getCachedObjectLoaded('Listing', $this->getRequest()->getParam('id'));
        }

        return $this->listing;
    }

    protected function getTitles()
    {
        $collection = $this->listingCollectionFactory->create();
        $data = $collection->getData();
        foreach ($data as $row) {
            $this->titles[] = $row['title'];
        }
    }
}
