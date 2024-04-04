<?php

declare(strict_types=1);

namespace Ess\M2ePro\Block\Adminhtml\Listing\Edit;

class EditStoreView extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractContainer
{
    /** @var \Ess\M2ePro\Model\Listing */
    private $listing;

    public function __construct(
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Widget $context,
        \Ess\M2ePro\Model\Listing $listing,
        array $data = []
    ) {
        $this->listing = $listing;
        parent::__construct($context, $data);
    }

    public function _construct()
    {
        parent::_construct();

        $this->removeButton('back');
        $this->removeButton('reset');
        $this->removeButton('delete');
        $this->removeButton('add');
        $this->removeButton('save');
        $this->removeButton('edit');
    }

    protected function _prepareLayout()
    {
        $this->addChild(
            'form',
            \Ess\M2ePro\Block\Adminhtml\Listing\Edit\StoreView\Form::class,
            ['listing' => $this->listing]
        );

        return parent::_prepareLayout();
    }
}
