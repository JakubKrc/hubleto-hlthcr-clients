<?php

namespace Hubleto\App\External\WaiBlue\HlthcrClients\HubletoOverrides;

use Hubleto\App\Community\Customers\Models\Customer as CommunityCustomer;

/**
 * Hubleto's Customer model with the Hlthcr client visibility (registered in the HlthcrClients loader).
 * Hubleto's own customer dropdown shows every client name to everybody (see the patches documents).
 */
class Customer extends CommunityCustomer
{
  use \Hubleto\App\External\WaiBlue\HlthcrCore\Models\Traits\OffersOnlyAllowedChanges;

  public string $recordManagerClass = CustomerRecordManager::class;

  public static function register(): void
  {
    \Hubleto\Framework\DependencyInjection::setServiceProvider(CommunityCustomer::class, self::class);
  }

  public function __construct()
  {
    parent::__construct();

    // Behave as the community model everywhere else: same migrations, config keys and translations.
    $communityModel = new \ReflectionClass(CommunityCustomer::class);
    $this->fullName = $communityModel->getName();
    $this->shortName = $communityModel->getShortName();
    $this->namespace = $communityModel->getNamespaceName();
    $this->srcFolder = pathinfo((string) $communityModel->getFileName(), PATHINFO_DIRNAME);
    $this->translationContext = 'hubleto-app-community-customers-loader';
    $this->translationContextInner = 'Models\\Customer';

    // Hubleto describes the columns in its constructor, before the name is reset; custom columns depend on it.
    $this->columns = $this->describeColumns();
  }

  // Counts and intervals of the occupational health columns cannot be negative.
  const NOT_NEGATIVE_COLUMNS = ['hlthcr_employee_count', 'hlthcr_audit_interval_months'];

  public function onBeforeCreate(array $record): array
  {
    $record = parent::onBeforeCreate($record);
    $this->refuseNegativeValues($record);
    return $record;
  }

  public function onBeforeUpdate(array $record): array
  {
    $record = parent::onBeforeUpdate($record);
    $this->refuseNegativeValues($record);
    return $record;
  }

  private function refuseNegativeValues(array $record): void
  {
    foreach (self::NOT_NEGATIVE_COLUMNS as $column) {
      $value = $record[$column] ?? null;
      $isNegative = is_numeric($value) && (int) $value < 0;
      if ($isNegative) {
        throw new \Hubleto\Framework\Exceptions\RecordSaveException($this->translate('The number of employees and the audit interval cannot be negative.'));
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

    return $columns;
  }

  // Medical staff see clients and contacts read-only.
  public function describeForm(): \Hubleto\Framework\Description\Form
  {
    $description = parent::describeForm();
    return $this->offerOnlyAllowedChanges($description);
  }

  public function describeTable(): \Hubleto\Framework\Description\Table
  {
    $description = parent::describeTable();
    return $this->offerOnlyAllowedChanges($description);
  }
}
