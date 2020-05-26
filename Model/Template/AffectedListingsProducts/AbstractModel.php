<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Template\AffectedListingsProducts;

/**
 * Class \Ess\M2ePro\Model\Template\AffectedListingsProducts\AbstractModel
 */
abstract class AbstractModel extends \Ess\M2ePro\Model\AbstractModel
{
    protected $activeRecordFactory;

    /** @var \Ess\M2ePro\Model\ActiveRecord\AbstractModel */
    protected $model = null;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $data = []
    ) {
        $this->activeRecordFactory = $activeRecordFactory;
        parent::__construct($helperFactory, $modelFactory, $data);
    }

    //########################################

    public function setModel(\Ess\M2ePro\Model\ActiveRecord\AbstractModel $model)
    {
        $this->model = $model;
        return $this;
    }

    //########################################

    abstract public function getObjects(array $filters = []);

    abstract public function getObjectsData($columns = '*', array $filters = []);

    abstract public function getIds(array $filters = []);

    //########################################
}
