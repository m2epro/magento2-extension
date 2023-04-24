<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Any usage is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Template\Shipping;

class Update
{
    /** @var \Ess\M2ePro\Helper\Module\Database\Structure */
    private $moduleDatabaseStructure;
    /** @var \Ess\M2ePro\Helper\Factory */
    protected $helperFactory;
    /** @var \Ess\M2ePro\Model\Amazon\Connector\Dispatcher */
    private $dispatcher;
    /** @var \Magento\Framework\App\ResourceConnection */
    private $resourceConnection;

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Model\Amazon\Connector\Dispatcher $dispatcher,
        \Ess\M2ePro\Helper\Module\Database\Structure $moduleDatabaseStructure,
        \Ess\M2ePro\Helper\Factory $helperFactory
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->dispatcher = $dispatcher;
        $this->moduleDatabaseStructure = $moduleDatabaseStructure;
        $this->helperFactory = $helperFactory;
    }

    /**
     * @param \Ess\M2ePro\Model\Account $account
     *
     * @return void
     */
    public function process(\Ess\M2ePro\Model\Account $account): void
    {
        /** @var \Ess\M2ePro\Model\Amazon\Connector\Template\Get\EntityRequester $connectorObj */
        $connectorObj = $this->dispatcher->getConnector(
            'template',
            'get',
            'entityRequester',
            [],
            $account->getId()
        );

        $this->dispatcher->process($connectorObj);
        $data = $connectorObj->getResponseData();

        $connection = $this->resourceConnection->getConnection();

        $tableDictionaryTemplateShipping = $this->moduleDatabaseStructure
            ->getTableNameWithPrefix('m2epro_amazon_dictionary_template_shipping');

        $connection->delete($tableDictionaryTemplateShipping, ['account_id = ?' => $account->getId()]);

        if (empty($data['templates'])) {
            return;
        }

        foreach ($data['templates'] as $template) {
            $connection->insert($tableDictionaryTemplateShipping, $template);
        }
    }
}
