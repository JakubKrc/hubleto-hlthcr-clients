<?php

namespace Hubleto\App\External\WaiBlue\HlthcrClients\Services;

use Hubleto\App\Community\Customers\Models\Customer;
use Hubleto\App\External\WaiBlue\HlthcrCore\Controllers\Api\UserLookup;
use Hubleto\App\External\WaiBlue\HlthcrCore\Services\Roles;

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
      'id_hlthcr_client_type' => 'int(8) NULL DEFAULT NULL',
      'hlthcr_employee_count' => 'int(8) NULL DEFAULT NULL',
      'hlthcr_audit_interval_months' => 'int(8) NULL DEFAULT NULL',
    ];
  }

  /**
   * Column name => Hubleto column definition (format of Column::fromArray()).
   * The client type is a lookup into its code list; its foreign key is added by the ClientType migrations.
   */
  public function getColumnDefinitions(): array
  {
    return [
      'id_hlthcr_client_type' => [
        'class' => 'Lookup',
        'title' => $this->translate('Client type'),
        'required' => true,
        'lookupModel' => \Hubleto\App\External\WaiBlue\HlthcrClients\Models\ClientType::class,
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
        // Column::fromArray() checks "description" but reads "hint".
        'description' => $this->getAuditIntervalHint(),
        'hint' => $this->getAuditIntervalHint(),
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

  // An empty interval is stored as 0, and then the default applies (Audit::getAuditIntervalMonths).
  private function getAuditIntervalHint(): string
  {
    return $this->translate('Empty: the default of {{ months }} months.', ['months' => $this->getDefaultAuditIntervalMonths()]);
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

  // The client's hygienist (Hubleto's owner) is chosen from hygienists only, as in one legacy app.
  public function offerOnlyHygienistsAsOwner(): void
  {
    $this->getModel(Customer::class)->getColumn('id_owner')
      ->setReactComponent('')
      ->setEndpoint(UserLookup::endpointFor(Roles::HYGIENIST, 'id_owner'))
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
