<?php

declare(strict_types=1);

namespace Powerline\PrescripcionModule\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Treatment Resource Model
 */
class Treatment extends AbstractDb
{
    /**
     * Table name
     */
    public const TABLE_NAME = 'pl_treatment';

    /**
     * Primary key field
     */
    public const ID_FIELD_NAME = 'id';

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init(self::TABLE_NAME, self::ID_FIELD_NAME);
    }
}
