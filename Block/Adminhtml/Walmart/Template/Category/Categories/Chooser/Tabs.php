<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Template\Category\Categories\Chooser;

class Tabs extends \Ess\M2ePro\Block\Adminhtml\Magento\Tabs\AbstractHorizontalTabs
{

    const TAB_ID_RECENT = 'recent';
    const TAB_ID_BROWSE = 'browse';
    const TAB_ID_SEARCH = 'search';

    /** @var \Ess\M2ePro\Helper\Data\GlobalData */
    private $globalDataHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Data\GlobalData $globalDataHelper,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Magento\Backend\Model\Auth\Session $authSession,
        array $data = []
    ) {
        $this->globalDataHelper = $globalDataHelper;
        parent::__construct($context, $jsonEncoder, $authSession, $data);
    }

    public function _construct()
    {
        parent::_construct();

        $this->setId('walmartTemplateCategoryCategoriesChooserTabs');
        $this->setDestElementId('chooser_tabs_container');
    }

    protected function _prepareLayout()
    {
        !$this->isNeedToHideRecent() && $this->addTab(self::TAB_ID_RECENT, [
            'label'   => $this->__('Recently Used'),
            'title'   => $this->__('Recently Used'),
            'content' => $this->getLayout()
              ->createBlock(\Ess\M2ePro\Block\Adminhtml\Walmart\Template\Category\Categories\Chooser\Tabs\Recent::class)
              ->toHtml(),
            'active'  => true
        ]);

        $this->addTab(self::TAB_ID_BROWSE, [
            'label'   => $this->__('Browse'),
            'title'   => $this->__('Browse'),
            'content' => $this->getLayout()
              ->createBlock(\Ess\M2ePro\Block\Adminhtml\Walmart\Template\Category\Categories\Chooser\Tabs\Browse::class)
              ->toHtml(),
            'active'  => $this->isNeedToHideRecent() ? true : false
        ]);

        $this->addTab(self::TAB_ID_SEARCH, [
            'label'   => $this->__('Search'),
            'title'   => $this->__('Search'),
            'content' => $this->getLayout()
              ->createBlock(\Ess\M2ePro\Block\Adminhtml\Walmart\Template\Category\Categories\Chooser\Tabs\Search::class)
              ->toHtml()
        ]);

        return parent::_prepareLayout();
    }

    public function isNeedToHideRecent()
    {
        return $this->globalDataHelper->getValue('category_chooser_hide_recent', true);
    }
}
