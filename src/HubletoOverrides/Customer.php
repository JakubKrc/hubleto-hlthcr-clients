<?php

namespace Hubleto\App\External\WaiBlue\HlthcrClients\HubletoOverrides;

use Hubleto\App\Community\Customers\Models\Customer as CommunityCustomer;

/**
 * Hubleto's Customer model with the Hlthcr client visibility (registered in the HlthcrClients loader).
 * Hubleto's own customer dropdown shows every client name to everybody (see the patches documents).
 */
class Customer extends CommunityCustomer
{
  use \Hubleto\App\External\WaiBlue\HlthcrCore\Models\Traits\BehavesAsCommunityModel;
  use \Hubleto\App\External\WaiBlue\HlthcrCore\Models\Traits\OffersOnlyAllowedChanges;
  use \Hubleto\App\External\WaiBlue\HlthcrCore\Models\Traits\UsesStoredValues;

  public string $recordManagerClass = CustomerRecordManager::class;

  // Hubleto's dropdown text prefers the identifier, which Hlthcr does not offer: the client's name instead.
  public ?string $lookupSqlValue = '{%TABLE%}.name';

  // Hubleto fields that neither legacy app has: not offered in the form or the list (the columns stay).
  const COLUMNS_IN_NEITHER_LEGACY_APP = ['identifier', 'region', 'id_country', 'shared_with', 'shared_folder'];

  public static function register(): void
  {
    \Hubleto\Framework\DependencyInjection::setServiceProvider(CommunityCustomer::class, self::class);
  }

  public function __construct()
  {
    parent::__construct();

    $this->behaveAsCommunityModel(CommunityCustomer::class, 'hubleto-app-community-customers-loader');

    // Hubleto describes the columns in its constructor, before the name is reset; custom columns depend on it.
    $this->columns = $this->describeColumns();
  }

  // Counts and intervals of the occupational health columns cannot be negative.
  const NOT_NEGATIVE_COLUMNS = ['hlthcr_employee_count', 'hlthcr_employee_count_at_risk', 'hlthcr_audit_interval_months'];

  public function onBeforeCreate(array $record): array
  {
    $record = parent::onBeforeCreate($record);
    $this->refuseNegativeValues($record);
    return $record;
  }

  public function onBeforeUpdate(array $record): array
  {
    $record = parent::onBeforeUpdate($record);
    // A save that leaves out a number column would store 0 for it (Hubleto), so the stored value is used.
    $record = $this->addStoredValuesOfMissingColumns($record, self::NOT_NEGATIVE_COLUMNS);
    $this->refuseNegativeValues($record);
    return $record;
  }

  private function refuseNegativeValues(array $record): void
  {
    foreach (self::NOT_NEGATIVE_COLUMNS as $column) {
      $value = $record[$column] ?? null;
      $isNegative = is_numeric($value) && (int) $value < 0;
      if ($isNegative) {
        throw new \Hubleto\Framework\Exceptions\RecordSaveException($this->translate('The numbers of employees and the audit interval cannot be negative.'));
      }
    }
  }

  // Hubleto reads custom columns by the class in use; HlthcrClients stores them for the community model.
  public function describeColumns(): array
  {
    $columns = parent::describeColumns();
    $customColumns = $this->config()->forModel(CommunityCustomer::class)->getAsJson('customColumns');

    foreach ($customColumns as $columnName => $columnDefinition) {
      if (!is_array($columnDefinition)) continue;
      $columnClass = 'Hubleto\\Framework\\Db\\Column\\' . ($columnDefinition['class'] ?? '');
      if (!class_exists($columnClass)) continue;

      $columns[$columnName] = (new $columnClass($this, $columnDefinition['title'] ?? ''))
        ->fromArray($columnDefinition)
        ->setProperty('isCustom', true)
        ->setDefaultVisible()
      ;
    }

    // The client list of both legacy apps shows the street, not the notes or the audit interval.
    if (isset($columns['street_line_1'])) $columns['street_line_1']->setDefaultVisible();
    if (isset($columns['note'])) $columns['note']->setDefaultHidden();
    if (isset($columns['hlthcr_audit_interval_months'])) $columns['hlthcr_audit_interval_months']->setDefaultHidden();

    return $columns;
  }

  // Medical staff see clients and contacts read-only.
  public function describeForm(): \Hubleto\Framework\Description\Form
  {
    $description = parent::describeForm();
    // Without its description, Hubleto's form shows nothing for an input.
    foreach (self::COLUMNS_IN_NEITHER_LEGACY_APP as $columnName) {
      unset($description->inputs[$columnName]);
    }
    return $this->offerOnlyAllowedChanges($description);
  }

  public function describeTable(): \Hubleto\Framework\Description\Table
  {
    $description = parent::describeTable();
    $description->hideColumns(array_merge(self::COLUMNS_IN_NEITHER_LEGACY_APP, ['virt_tags']));
    return $this->offerOnlyAllowedChanges($description);
  }
}
