<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon;

class Repricer extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractContainer
{
    /** @var \Ess\M2ePro\Helper\Module\Support */
    private $supportHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Module\Support $supportHelper,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Widget $context,
        array $data = []
    ) {
        $this->supportHelper = $supportHelper;

        parent::__construct($context, $data);
    }

    protected function _construct()
    {
        parent::_construct();

        // Set buttons actions
        // ---------------------------------------
        $this->removeButton('back');
        $this->removeButton('reset');
        $this->removeButton('delete');
        $this->removeButton('save');
        $this->removeButton('edit');
        $this->removeButton('add');
        // ---------------------------------------

        $this->_controller = 'adminhtml_amazon_repricer';
    }

    protected function _prepareLayout()
    {
        $this->appendHelpBlock([
            'content' => $this->__(
                <<<HTML
<p>On this page, you can:<ul>
<li>view which of your M2E Pro Amazon accounts are linked/not linked to
<a href="%url%" target="_blank">Repricer</a></li>
<li>check how many items from your Amazon account are currently managed via Repricer</li>
</ul></p>
<p>To connect your Amazon account to Repricer, click <strong>Connect</strong>.</p>
<p>Click on the Amazon account in the grid to view additional settings.</p>
HTML
                ,
                $this->supportHelper->getDocumentationArticleUrl('x/AAAZD')
            ),
        ]);

        return parent::_prepareLayout();
    }
}
