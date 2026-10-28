<?php

namespace Hubleto\App\External\WaiBlue\HlthcrClients\Services;

use Hubleto\App\Community\Customers\Models\Customer;

/**
 * Extra columns on the community "customers" table: the client data both legacy apps keep.
 * Uses Hubleto custom columns: the DB columns are added on install, the definitions are set on every request.
 */
class CustomerColumns extends \Hubleto\Erp\Core
{
  // Used for new clients when the settings have no other value (both apps audit every two years by default).
  const DEFAULT_AUDIT_INTERVAL_MONTHS = 24;

  public string $translationContext = 'hubleto-app-external-waiblue-hlthcrclients-loader';
  public string $translationContextInner = 'Services\\CustomerColumns';

  /**
   * Column name => SQL type used when adding the column to the "customers" table.
   */
  public function getSqlTypes(): array
  {
    return [
      'hlthcr_client_type' => 'int(8) NULL DEFAULT NULL',
      'hlthcr_employee_count' => 'int(8) NULL DEFAULT NULL',
      'hlthcr_audit_interval_months' => 'int(8) NULL DEFAULT NULL',
    ];
  }

  /**
   * Column name => Hubleto column definition (format of Column::fromArray()).
   * The client types come from their code list; a lookup column would need a relation inside the Customer model.
   */
  public function getColumnDefinitions(): array
  {
    /** @var \Hubleto\App\External\WaiBlue\HlthcrClients\Models\ClientType */
    $mClientType = $this->getModel(\Hubleto\App\External\WaiBlue\HlthcrClients\Models\ClientType::class);

    return [
      'hlthcr_client_type' => [
        'class' => 'Integer',
        'title' => $this->translate('Client type'),
        'required' => true,
        'enumValues' => $mClientType->getNamesById(),
      ],
      'hlthcr_employee_count' => [
        'class' => 'Integer',
        'title' => $this->translate('Number of employees'),
      ],
      'hlthcr_audit_interval_months' => [
        'class' => 'Integer',
        'title' => $this->translate('Audit interval'),
        'unit' => $this->translate('months'),
        'defaultValue' => $this->getDefaultAuditIntervalMonths(),
      ],
    ];
  }

  public function getDefaultAuditIntervalMonths(): int
  {
    $clientsApp = $this->appManager()->getApp(\Hubleto\App\External\WaiBlue\HlthcrClients\Loader::class);
    $configuredMonths = 0;
    if ($clientsApp !== null) {
      $configuredMonths = $clientsApp->configAsInteger('defaultAuditIntervalMonths');
    }
    if ($configuredMonths > 0) return $configuredMonths;
    return self::DEFAULT_AUDIT_INTERVAL_MONTHS;
  }

  /**
   * Called from Loader::init(). Customer::describeColumns() reads this config key.
   */
  public function registerInCustomerModel(): void
  {
    $configPath = 'models/' . Customer::class . '/customColumns';
    $this->config()->set($configPath, json_encode($this->getColumnDefinitions()));
  }

  /**
   * Hubleto's required "Manažér" is not required and not shown, and visibility does not use it (AccessScope).
   * One legacy app has a regional manager, but only for notifications of its own modules. The column stays in the table.
   */
  public function hideManagerInCustomerModel(): void
  {
    $this->getModel(Customer::class)->getColumn('id_manager')
      ->setRequired(false)
      ->setDefaultHidden()
      ->setReactComponent('HlthcrCoreNoInput')
    ;
  }

  /**
   * Adds missing columns to the "customers" table. Safe to run repeatedly.
   */
  public function installMissingColumns(): void
  {
    $existingColumns = [];
    $columnRows = $this->db()->fetchAll(
      "SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'customers'"
    );
    foreach ($columnRows as $columnRow) {
      $existingColumns[] = $columnRow['COLUMN_NAME'];
    }

    foreach ($this->getSqlTypes() as $columnName => $sqlType) {
      if (in_array($columnName, $existingColumns)) continue;
      $this->db()->execute("ALTER TABLE `customers` ADD COLUMN `{$columnName}` {$sqlType}");
    }
  }
}
