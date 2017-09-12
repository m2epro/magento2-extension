<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\HealthStatus;

use Ess\M2ePro\Model\HealthStatus\Task\IssueType;
use Ess\M2ePro\Model\HealthStatus\Task\InfoType;

class Tabs extends \Ess\M2ePro\Block\Adminhtml\Magento\Tabs\AbstractTabs
{
    const TAB_ID_DASHBOARD     = 'dashboard';
    const TAB_ID_NOTIFICATIONS = 'notifications';

    /** @var \Ess\M2ePro\Model\HealthStatus\Task\Result\Set */
    private $resultSet;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\HealthStatus\Task\Result\Set $resultSet,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Magento\Backend\Model\Auth\Session $authSession,
        array $data = []
    ){
        parent::__construct($context, $jsonEncoder, $authSession, $data);
        $this->resultSet = $resultSet;
    }

    //########################################

    public function _construct()
    {
        parent::_construct();

        $this->setId('healthStatus');
        $this->setDestElementId('healthStatus_tab_container');
    }

    //########################################

    protected function _prepareLayout()
    {
        $this->css->addFile('health_status.css');

        // ---------------------------------------
        $resultSet = clone $this->resultSet;
        $resultSet->fill($this->resultSet->getByType(InfoType::TYPE));

        /** @var \Ess\M2ePro\Block\Adminhtml\HealthStatus\Tabs\Dashboard $tabObj */
        $tabObj = $this->createBlock('HealthStatus\Tabs\Dashboard', '', [
            'resultSet' => $resultSet
        ]);

        $this->addTab(self::TAB_ID_DASHBOARD,  array(
            'label'   => $this->__('Dashboard'),
            'title'   => $this->__('Dashboard'),
            'content' => $tabObj->toHtml()
        ));
        // ---------------------------------------

        // -- Dynamic Tabs for Issues
        // ---------------------------------------
        $createdTabs = [];

        foreach ($this->resultSet->getByType(IssueType::TYPE) as $result) {

            if (in_array($result->getTabName(), $createdTabs)) {
                continue;
            }

            if ($result->isSuccess() && !$result->isTaskMustBeShowIfSuccess()) {
                continue;
            }

            $resultSet = clone $this->resultSet;
            $resultSet->fill($this->resultSet->getByTab(
                $this->resultSet->getTabKey($result)
            ));

            /** @var \Ess\M2ePro\Block\Adminhtml\HealthStatus\Tabs\IssueGroup $tabObj */
            $tabObj = $this->createBlock('HealthStatus\Tabs\IssueGroup', '', [
                'resultSet' => $resultSet
            ]);

            $tabClass = '';
            $resultSet->isCritical() && $tabClass = 'health-status-tab-critical';
            $resultSet->isWaring()   && $tabClass = 'health-status-tab-warning';
            $resultSet->isNotice()   && $tabClass = 'health-status-tab-notice';

            $this->addTab('issue_tab_' . $resultSet->getTabKey($result), array(
                'label'   => $this->__($result->getTabName()),
                'title'   => $this->__($result->getTabName()),
                'content' => $tabObj->toHtml(),
                'class'   => $tabClass
            ));

            $createdTabs[] = $result->getTabName();
        }
        // ---------------------------------------

        // ---------------------------------------
        /** @var \Ess\M2ePro\Block\Adminhtml\HealthStatus\Tabs\Notifications $tabObj */
        $tabObj = $this->createBlock('HealthStatus\Tabs\Notifications');

        $this->addTab(self::TAB_ID_NOTIFICATIONS, array(
            'label'   => $this->__('Notification Settings'),
            'title'   => $this->__('Notification Settings'),
            'content' => $tabObj->toHtml()
        ));
        // ---------------------------------------

        $this->setActiveTab($this->getRequest()->getParam('tab', self::TAB_ID_DASHBOARD));

        return parent::_prepareLayout();
    }

    //########################################

    public function getActiveTabById($id)
    {
        return isset($this->_tabs[$id]) ? $this->_tabs[$id] : NULL;
    }

    //########################################
}