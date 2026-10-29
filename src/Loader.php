<?php

namespace Hubleto\App\External\WaiBlue\HlthcrClients;

use Hubleto\App\Community\Contacts\Models\Contact;
use Hubleto\App\Community\Contacts\Models\Value as ContactValue;
use Hubleto\App\Community\Customers\Models\Customer;
use Hubleto\App\External\WaiBlue\HlthcrCore\Services\DemoData;

class Loader extends \Hubleto\App\External\WaiBlue\HlthcrCore\HlthcrApp
{
  // Starting values of the client type code list (the types both legacy apps share).
  const DEFAULT_CLIENT_TYPES = ['Paušálny dohľad', 'Audit/reaudit', 'Kombinácia'];

  // Apps are constructed before any model is used, so the replaced models apply everywhere.
  public function __construct()
  {
    parent::__construct();
    HubletoOverrides\Customer::register();
    HubletoOverrides\Contact::register();
  }

  public function init(): void
  {
    parent::init();

    $this->router()->get([
      '/^hlthcr-clients\/?$/' => Controllers\Home::class,
      '/^hlthcr-clients\/settings\/?$/' => Controllers\Settings::class,
      '/^hlthcr-clients\/email-export\/?$/' => Controllers\EmailExport::class,
      '/^hlthcr-clients\/api\/download-email-export\/?$/' => Controllers\Api\DownloadEmailExport::class,
    ]);
    $this->router()->crud('hlthcr-clients/client-types', Controllers\ClientTypes::class);

    /** @var \Hubleto\App\Community\Settings\Loader $settingsApp */
    $settingsApp = $this->appManager()->getApp(\Hubleto\App\Community\Settings\Loader::class);
    $settingsApp->addSetting($this, [
      'title' => $this->translate('Client types'),
      'icon' => 'fas fa-tags',
      'url' => 'hlthcr-clients/client-types',
    ]);

    // Client type, number of employees and audit interval are columns of the community Customer model.
    /** @var Services\CustomerColumns $customerColumns */
    $customerColumns = $this->getService(Services\CustomerColumns::class);
    $customerColumns->registerInCustomerModel();
    $customerColumns->hideManagerInCustomerModel();
    $customerColumns->offerOnlyHygienistsAsOwner();
  }

  public function installApp(int $round): void
  {
    if ($round == 1) {
      $this->getModel(Models\ClientType::class)->upgradeSchema();
      // "php hubleto app install" runs only round 1, so foreign keys are created here too (Hubleto skips them later).
      $this->installForeignKeys();
      $this->getService(Services\CustomerColumns::class)->installMissingColumns();
      $this->installDefaultClientTypes();
    }
  }

  private function installDefaultClientTypes(): void
  {
    /** @var Models\ClientType */
    $mClientType = $this->getModel(Models\ClientType::class);
    foreach (self::DEFAULT_CLIENT_TYPES as $clientTypeName) {
      $mClientType->record->firstOrCreate(['name' => $clientTypeName]);
    }
  }

  public function renderSecondSidebar(): string
  {
    return '
      ' . $this->secondSidebarTitle() . '
      <div class="app-sidebar-buttons">
        ' . $this->secondSidebarButton('customers', 'fas fa-building', $this->translate('Clients')) . '
        ' . $this->secondSidebarButton('hlthcr-clients/client-types', 'fas fa-tags', $this->translate('Client types')) . '
        ' . $this->secondSidebarButton('hlthcr-clients/email-export', 'fas fa-at', $this->translate('Export of e-mail addresses')) . '
        ' . $this->secondSidebarButton('hlthcr-clients/settings', 'fas fa-cog', $this->translate('Settings')) . '
      </div>
    ';
  }

  public function renderAlerts(): string
  {
    return '';
  }

  public function getSidebarBadgeNumber(): int
  {
    return 0;
  }

  public function generateHlthcrDemoData(): void
  {
    /** @var Customer */
    $mCustomer = $this->getModel(Customer::class);
    /** @var Models\ClientType */
    $mClientType = $this->getModel(Models\ClientType::class);
    /** @var DemoData */
    $demoData = $this->getService(DemoData::class);

    $this->installDefaultClientTypes();
    $clientTypeIdsByName = array_flip($mClientType->getNamesById());
    $defaultAuditIntervalMonths = $this->getService(Services\CustomerColumns::class)->getDefaultAuditIntervalMonths();

    foreach ($demoData->getDemoClients($demoData->ensureDemoUsers()) as $demoClient) {
      $customer = $mCustomer->record->where('company_id', $demoClient['company_id'])->first();
      if ($customer) {
        $this->ensureDemoContactPerson((int) $customer->id, $demoClient['company_id']);
        continue;
      }

      $createdCustomer = $mCustomer->record->recordCreate([
        'name' => $demoClient['name'],
        'company_id' => $demoClient['company_id'],
        'tax_id' => $demoClient['tax_id'],
        'vat_id' => $demoClient['vat_id'],
        'street_line_1' => $demoClient['street'],
        'city' => $demoClient['city'],
        'postal_code' => $demoClient['postal_code'],
        'id_owner' => $demoClient['id_hygienist'],
        'id_manager' => $demoClient['id_hygienist'],
        'is_active' => true,
        'date_created' => date('Y-m-d'),
      ]);

      // Set directly: during "php hubleto init" the Customer model is loaded before this app adds its columns,
      // so recordCreate() would skip them.
      $mCustomer->record->where('id', $createdCustomer['id'])->update([
        'id_hlthcr_client_type' => $clientTypeIdsByName[$demoClient['client_type']] ?? null,
        'hlthcr_employee_count' => $demoClient['employee_count'],
        'hlthcr_audit_interval_months' => $defaultAuditIntervalMonths,
      ]);
      $this->ensureDemoContactPerson((int) $createdCustomer['id'], $demoClient['company_id']);
    }
  }

  /**
   * One FAKE contact person per demo client, with an @example.com address.
   */
  private function ensureDemoContactPerson(int $idCustomer, string $companyId): void
  {
    /** @var Contact */
    $mContact = $this->getModel(Contact::class);
    $contactExists = $mContact->record->where('id_customer', $idCustomer)->exists();
    if ($contactExists) return;

    $createdContact = $mContact->record->create([
      'first_name' => 'Demo',
      'last_name' => 'Kontakt ' . $companyId,
      'id_customer' => $idCustomer,
      'is_primary' => 1,
      'is_valid' => 1,
      'date_created' => date('Y-m-d'),
    ]);
    $this->getModel(ContactValue::class)->record->create([
      'id_contact' => $createdContact->id,
      'type' => 'email',
      'value' => 'kontakt-' . $companyId . '@example.com',
    ]);
  }
}
