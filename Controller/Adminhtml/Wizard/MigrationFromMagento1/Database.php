<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Wizard\MigrationFromMagento1;

use Ess\M2ePro\Model\Wizard\MigrationFromMagento1;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Wizard\MigrationFromMagento1\Database
 */
class Database extends \Magento\Backend\App\Action
{
    /** @var \Ess\M2ePro\Helper\Factory */
    protected $helperFactory;

    /** @var \Magento\Framework\View\Result\PageFactory $resultPageFactory  */
    protected $resultPageFactory;

    /** @var \Magento\Framework\App\ResourceConnection */
    protected $resourceConnection;

    //########################################

    public function __construct(
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\App\ResourceConnection $resourceConnection
    ) {
        $this->helperFactory      = $helperFactory;
        $this->resultPageFactory  = $resultPageFactory;
        $this->resourceConnection = $resourceConnection;
        parent::__construct($context);
    }

    //########################################

    protected function _isAllowed()
    {
        return $this->_auth->isLoggedIn();
    }

    //########################################

    public function execute()
    {
        /** @var \Ess\M2ePro\Model\Wizard\MigrationFromMagento1 $wizard */
        $wizard = $this->helperFactory->getObject('Module_Wizard')->getWizard(MigrationFromMagento1::NICK);

        if (!$wizard->isStarted()) {
            return $this->_redirect('*/wizard_migrationFromMagento1/disableModule');
        }

        if ($wizard->getCurrentStatus() === MigrationFromMagento1::STATUS_UNEXPECTEDLY_COPIED) {
            $configTableName = $wizard->getM1TablesPrefix() . 'm2epro_config';

            if ($this->resourceConnection->getConnection()->isTableExists($configTableName)) {
                $status = (int)$this->resourceConnection->getConnection()
                    ->select()
                    ->from($configTableName, 'value')
                    ->where(
                        new \Zend_Db_Expr(
                            "`group` = '/migrationtomagento2/source/' AND `key` = 'is_prepared_for_migration'"
                        )
                    )
                    ->query()
                    ->fetchColumn();

                if ($status === 1) {
                    $wizard->setCurrentStatus(MigrationFromMagento1::STATUS_PREPARED);
                    return $this->_redirect($this->getUrl('m2epro/wizard_migrationFromMagento1/database'));
                }
            }
        }

        $result = $this->resultPageFactory->create();

        $result->getConfig()->addPageAsset("Ess_M2ePro::css/style.css");
        $result->getConfig()->addPageAsset("Ess_M2ePro::css/wizard.css");

        $result->getConfig()->getTitle()->set(__(
            'M2E Pro Module Migration from Magento v1.x'
        ));

        /** @var \Ess\M2ePro\Block\Adminhtml\Wizard\MigrationFromMagento1\Installation\Database $block */
        $block = $result->getLayout()->createBlock(
            \Ess\M2ePro\Block\Adminhtml\Wizard\MigrationFromMagento1\Installation\Database::class
        );
        $block->setData('nick', \Ess\M2ePro\Model\Wizard\MigrationFromMagento1::NICK);

        $this->_addContent($block);

        $generalBlock =  $result->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\General::class);
        $result->getLayout()->setChild('js', $generalBlock->getNameInLayout(), '');

        return $result;
    }

    //########################################
}
