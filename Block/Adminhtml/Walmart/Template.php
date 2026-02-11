<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Walmart;

use Ess\M2ePro\Block\Adminhtml\Walmart\Template\Grid;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Walmart\Template
 */
class Template extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractContainer
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        $this->setId('walmartTemplate');
        $this->_controller = 'adminhtml_walmart_template';

        $this->buttonList->remove('back');
        $this->buttonList->remove('reset');
        $this->buttonList->remove('delete');
        $this->buttonList->remove('save');
        $this->buttonList->remove('edit');

        $this->buttonList->update('add', 'label', __('Add Policy'));
        $this->buttonList->update('add', 'onclick', '');
    }

    //########################################

    protected function _prepareLayout()
    {
        $content = __(
            '
            <strong>Description Policy</strong> highlights the most essential Product details, e.g.
            Title, Brand, Images, etc.<br/><br/>

            <strong>Selling Policy</strong> contains conditions based on which you are going to sell your
            Item on the Channel, e.g. Item Price, Quantity, Shipping and Product Tax Code settings, etc.<br /><br />

            <strong>Synchronization Policy</strong> defines the Rules based on which your Walmart Items will
            be dynamically updated with Magento data.<br/><br/>'
        );

        $this->appendHelpBlock(
            [
                'content' => $content,
            ]
        );

        $addButtonProps = [
            'id' => 'add_policy',
            'label' => __('Add Policy'),
            'class' => 'add',
            'button_class' => '',
            'class_name' => \Ess\M2ePro\Block\Adminhtml\Magento\Button\DropDown::class,
            'options' => $this->_getAddTemplateButtonOptions(),
        ];
        $this->addButton('add', $addButtonProps);

        return parent::_prepareLayout();
    }

    //########################################

    protected function _getAddTemplateButtonOptions()
    {
        $data = [
            Grid::TEMPLATE_DESCRIPTION => [
                'label' => __('Description'),
                'id' => 'description',
                'onclick' => "setLocation('" . $this->getTemplateUrl(Grid::TEMPLATE_DESCRIPTION) . "')",
            ],
            Grid::TEMPLATE_SELLING_FORMAT => [
                'label' => __('Selling'),
                'id' => 'selling',
                'onclick' => "setLocation('" . $this->getTemplateUrl(Grid::TEMPLATE_SELLING_FORMAT) . "')",
            ],
            Grid::TEMPLATE_SYNCHRONIZATION => [
                'label' => __('Synchronization'),
                'id' => 'synchronization',
                'onclick' => "setLocation('" . $this->getTemplateUrl(Grid::TEMPLATE_SYNCHRONIZATION) . "')",
            ],
            Grid::TEMPLATE_REPRICER => [
                'label' => __('Repricer'),
                'id' => 'repricer',
                'onclick' => "setLocation('" . $this->getTemplateUrl(Grid::TEMPLATE_REPRICER) . "')",
            ],
        ];

        return $data;
    }

    protected function getTemplateUrl($type)
    {
        return $this->getUrl('*/walmart_template/new', ['type' => $type]);
    }

    //########################################
}
