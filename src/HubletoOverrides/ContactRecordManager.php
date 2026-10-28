<?php

namespace Hubleto\App\External\WaiBlue\HlthcrClients\HubletoOverrides;

use Hubleto\App\External\WaiBlue\HlthcrCore\Services\AccessScope;

/**
 * Contact persons of the clients the user may see, also in dropdowns. Medical staff see none (both legacy apps).
 */
class ContactRecordManager extends \Hubleto\App\Community\Contacts\Models\RecordManagers\Contact
{
  public function prepareReadQuery(mixed $query = null, int $level = 0, array|null $includeRelations = null): mixed
  {
    $query = parent::prepareReadQuery($query, $level, $includeRelations);
    return $this->restrictToVisibleCustomers($query);
  }

  public function prepareLookupQuery(string $search): mixed
  {
    $query = parent::prepareLookupQuery($search);
    return $this->restrictToVisibleCustomers($query);
  }

  private function restrictToVisibleCustomers(mixed $query): mixed
  {
    $hubleto = \Hubleto\Erp\Loader::getGlobalApp();
    /** @var AccessScope */
    $accessScope = $hubleto->getService(AccessScope::class);
    // Medical staff have no work records and no contact persons.
    $query = $accessScope->restrictToWorkRecordReaders($query);
    return $accessScope->restrictToVisibleCustomers($query, $this->table . '.id_customer');
  }
}
