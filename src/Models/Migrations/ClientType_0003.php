<?php

namespace Hubleto\App\External\WaiBlue\HlthcrClients\Models\Migrations;

use Hubleto\Framework\Migration;

// The number of a client's employees at risk on Hubleto's "customers" table (models and lookups review B7).
// A new install adds it later anyway (CustomerColumns::installMissingColumns), so it is added only when missing.
class ClientType_0003 extends Migration
{
  public function upgradeSchema(): void
  {
    $columns = $this->db->fetchAll(
      "SELECT COLUMN_NAME FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'customers' AND COLUMN_NAME = 'hlthcr_employee_count_at_risk'"
    );
    if (count($columns) > 0) return;

    $this->db->execute("ALTER TABLE `customers` ADD COLUMN `hlthcr_employee_count_at_risk` int(8) NULL DEFAULT NULL");
  }

  public function downgradeSchema(): void
  {
    $this->db->execute("ALTER TABLE `customers` DROP COLUMN `hlthcr_employee_count_at_risk`");
  }

  public function upgradeForeignKeys(): void
  {
  }

  public function downgradeForeignKeys(): void
  {
  }
}
