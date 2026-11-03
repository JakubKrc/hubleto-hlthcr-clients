<?php

namespace Hubleto\App\External\WaiBlue\HlthcrClients\Models\Migrations;

use Hubleto\Framework\Migration;

// Contract dates on Hubleto's "customers" table, which both legacy apps keep.
// A new install adds them later anyway (CustomerColumns::installMissingColumns), so they are added only when missing.
class ClientType_0004 extends Migration
{
  const CONTRACT_DATE_COLUMNS = ['hlthcr_contract_valid_from', 'hlthcr_contract_valid_to'];

  public function upgradeSchema(): void
  {
    foreach (self::CONTRACT_DATE_COLUMNS as $columnName) {
      $columns = $this->db->fetchAll(
        "SELECT COLUMN_NAME FROM information_schema.COLUMNS
          WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'customers' AND COLUMN_NAME = '{$columnName}'"
      );
      if (count($columns) > 0) continue;

      $this->db->execute("ALTER TABLE `customers` ADD COLUMN `{$columnName}` date NULL DEFAULT NULL");
    }
  }

  public function downgradeSchema(): void
  {
    foreach (self::CONTRACT_DATE_COLUMNS as $columnName) {
      $this->db->execute("ALTER TABLE `customers` DROP COLUMN `{$columnName}`");
    }
  }

  public function upgradeForeignKeys(): void
  {
  }

  public function downgradeForeignKeys(): void
  {
  }
}
