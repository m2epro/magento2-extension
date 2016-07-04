<?php

namespace Ess\M2ePro\Plugin\Model\Menu;

class Item
{
    protected $maintenanceHelper;
    private $menuTitlesUsing = [];

    protected $wizardHelper;
    protected $ebayView;
    protected $amazonView;
    protected $support;

    //########################################

    public function __construct(
        \Ess\M2ePro\Helper\Module\Wizard $wizardHelper,
        \Ess\M2ePro\Helper\Module\Maintenance\Setup $maintenanceHelper,
        \Ess\M2ePro\Helper\View\Ebay $ebayView,
        \Ess\M2ePro\Helper\View\Amazon $amazonView,
        \Ess\M2ePro\Helper\Module\Support $support
    )
    {
        $this->wizardHelper = $wizardHelper;
        $this->maintenanceHelper = $maintenanceHelper;
        $this->ebayView = $ebayView;
        $this->amazonView = $amazonView;
        $this->support = $support;
    }

    //########################################

    /**
     * @param \Magento\Backend\Model\Menu\Item $subject
     * @param \Closure $proceed
     * @return string
     */
    public function aroundGetUrl($subject, \Closure $proceed)
    {
        if ($this->maintenanceHelper->isEnabled()) {
            return $proceed();
        }

        $id = $subject->getId();

        if ($id == 'Ess_M2ePro::ebay_listings_other'
            && !$this->ebayView->is3rdPartyShouldBeShown()) {

            return '#';
        }

        if ($id == 'Ess_M2ePro::amazon_listings_other'
            && !$this->amazonView->is3rdPartyShouldBeShown()) {

            return '#';
        }

        return $proceed();
    }

    /**
     * @param \Magento\Backend\Model\Menu\Item $subject
     * @param \Closure $proceed
     * @return string
     */
    public function aroundGetClickCallback($subject, \Closure $proceed)
    {
        if ($this->maintenanceHelper->isEnabled()) {
            return $proceed();
        }

        $id = $subject->getId();
        $urls = $this->getUrls();

        if ($id == 'Ess_M2ePro::ebay_listings_other'
            && !$this->ebayView->is3rdPartyShouldBeShown()) {

            return 'return false;';
        }

        if ($id == 'Ess_M2ePro::amazon_listings_other'
            && !$this->amazonView->is3rdPartyShouldBeShown()) {

            return 'return false;';
        }

        if (isset($urls[$id])) {
            return $this->renderOnClickCallback($urls[$id]);
        }

        return $proceed();
    }

    /**
     * Gives able to display titles in menu slider which differ from titles in menu panel
     * @param \Magento\Backend\Model\Menu\Item $subject
     * @param string $result
     * @return string
     */
    public function afterGetTitle($subject, $result)
    {
        $ebayKey = 'Ess_M2ePro::ebay';
        $amazonKey = 'Ess_M2ePro::amazon';

        $isEbayWizardCompleted = $this->wizardHelper->isCompleted(
            \Ess\M2ePro\Helper\View\Ebay::WIZARD_INSTALLATION_NICK
        );

        if (
            $isEbayWizardCompleted
            && $subject->getId() == $ebayKey
            && !isset($this->menuTitlesUsing[$ebayKey])
        ) {
            $this->menuTitlesUsing[$ebayKey] = true;
            return 'eBay Integration (Beta)';
        }

        $isAmazonWizardCompleted = $this->wizardHelper->isCompleted(
            \Ess\M2ePro\Helper\View\Amazon::WIZARD_INSTALLATION_NICK
        );

        if (
            $isAmazonWizardCompleted
            && $subject->getId() == $amazonKey
            && !isset($this->menuTitlesUsing[$amazonKey])
        ) {
            $this->menuTitlesUsing[$amazonKey] = true;
            return 'Amazon Integration (Beta)';
        }

        return $result;
    }

    private function getUrls()
    {
        return [
            'Ess_M2ePro::ebay_help_center_documentation'   => $this->support->getDocumentationUrl(
                NULL, NULL, 'x/2AIkAQ'
            ),
            'Ess_M2ePro::ebay_help_center_ideas_workshop'  => $this->support->getIdeasBaseUrl(),
            'Ess_M2ePro::ebay_help_center_knowledge_base'  => $this->support->getKnowledgeBaseUrl(),
            'Ess_M2ePro::ebay_help_center_community_forum' => $this->support->getCommunityBaseUrl(),

            'Ess_M2ePro::amazon_help_center_documentation'   => $this->support->getDocumentationUrl(
                NULL, NULL, 'x/3AIkAQ'
            ),
            'Ess_M2ePro::amazon_help_center_ideas_workshop'  => $this->support->getIdeasBaseUrl(),
            'Ess_M2ePro::amazon_help_center_knowledge_base'  => $this->support->getKnowledgeBaseUrl(),
            'Ess_M2ePro::amazon_help_center_community_forum' => $this->support->getCommunityBaseUrl(),
        ];
    }

    private function renderOnClickCallback($url)
    {
        return "window.open('$url', '_blank')";
    }

    //########################################
}