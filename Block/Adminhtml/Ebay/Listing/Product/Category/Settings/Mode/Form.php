<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product\Category\Settings\Mode;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm;
use Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product\Category\Settings\Mode as CategoryTemplateBlock;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product\Category\Settings\Mode\Form
 */
class Form extends AbstractForm
{
    private $ebayListingFactory;

    public function __construct(
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Ess\M2ePro\Model\Ebay\ListingFactory $ebayListingFactory,
        array $data = []
    ) {
        $this->ebayListingFactory = $ebayListingFactory;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    protected function _prepareForm()
    {
        $form = $this->_formFactory->create(
            [
                'data' => [
                    'id' => 'categories_mode_form',
                    'method' => 'post',
                ],
            ]
        );
        $id = $this->getRequest()->getParam('id');
        $ebayListing = $this->ebayListingFactory->create()->load($id);
        $addProductMode = $ebayListing->getAddProductMode();

        $blockLabel = $addProductMode
            ? $this->__('To change the eBay category settings mode for this Listing, please click one of the available options below and save:')
            : $this->__('You need to choose eBay Categories for Products in order to list them on eBay.');

        $fieldset = $form->addFieldset('categories_mode', []);

        $fieldset->addField(
            'block-title',
            'label',
            [
                'value' => $blockLabel,
                'field_extra_attributes' =>
                    'id="categories_mode_block_title" style="font-weight: bold;font-size:18px;margin-bottom:0px"',
            ]
        );
        $this->css->add(
            <<<CSS
    #categories_mode_block_title .admin__field-control{
        width: 90%;
    }
CSS
        );

        $fieldset->addField(
            'block-notice',
            'label',
            [
                'value' => $this->__('Choose one of the Options below.'),
                'field_extra_attributes' => 'style="margin-bottom: 0;"',
            ]
        );

        $fieldset->addField(
            'mode1',
            'radios',
            [
                'name' => 'mode',
                'field_extra_attributes' => 'style="margin: 4px 0 0 0; font-weight: bold"',
                'values' => [
                    [
                        'value' => CategoryTemplateBlock::MODE_SAME,
                        'label' => 'All Products same Category',
                    ],
                ],
                'note' => '<div style="padding-top: 3px; padding-left: 26px; font-weight: normal">' .
                    $this->__('Products will be Listed using the same eBay Category.') . '</div>',
            ]
        );

        $fieldset->addField(
            'mode2',
            'radios',
            [
                'name' => 'mode',
                'field_extra_attributes' => 'style="margin: 4px 0 0 0; font-weight: bold"',
                'values' => [
                    [
                        'value' => CategoryTemplateBlock::MODE_CATEGORY,
                        'label' => 'Based on Magento Categories',
                    ],
                ],
                'note' => '<div style="padding-top: 3px; padding-left: 26px; font-weight: normal">' .
                    $this->__('Products will have eBay Categories set according to the Magento Categories.') . '</div>',
            ]
        );

        $fieldset->addField(
            'mode3',
            'radios',
            [
                'name' => 'mode',
                'field_extra_attributes' => 'style="margin: 4px 0 0 0; font-weight: bold"',
                'values' => [
                    [
                        'value' => CategoryTemplateBlock::MODE_PRODUCT,
                        'label' => 'Get suggested Categories',
                    ],
                ],
                'note' => '<div style="padding-top: 3px; padding-left: 26px; font-weight: normal">' .
                    $this->__(
                        'Get eBay to suggest Categories for your Products based on the Title and Magento Attribute set.'
                    ) . '</div>',
            ]
        );

        $fieldset->addField(
            'mode4',
            'radios',
            [
                'name' => 'mode',
                'field_extra_attributes' => 'style="margin: 4px 0 0 0; font-weight: bold"',
                'values' => [
                    [
                        'value' => CategoryTemplateBlock::MODE_MANUALLY,
                        'label' => 'Set Manually for each Product',
                    ],
                ],
                'note' => '<div style="padding-top: 3px; padding-left: 26px; font-weight: normal">' .
                    $this->__('Set eBay Categories for each Product (or a group of Products) manually.') . '</div>',
            ]
        );

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }
}
