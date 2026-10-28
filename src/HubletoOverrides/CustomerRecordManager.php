<?php

namespace Hubleto\App\External\WaiBlue\HlthcrClients\HubletoOverrides;

use Hubleto\App\External\WaiBlue\HlthcrCore\Services\AccessScope;

/**
 * Customers the user may see, also in dropdowns (a hygienist: own clients).
 */
class CustomerRecordManager extends \Hubleto\App\Community\Customers\Models\RecordManagers\Customer
{
  public function prepareReadQuery(mixed $query = null, int $level = 0, array|null $includeRelations = null): mixed
  {
    $query = parent::prepareReadQuery($query, $level, $includeRelations);
    return $this->restrictToVisibleCustomers($query);
  }

  // Dropdowns offer active clients only, as in both legacy apps (a chosen one still shows in its form).
  public function prepareLookupQuery(string $search): mixed
  {
    $query = parent::prepareLookupQuery($search);
    $query = $query->where($this->table . '.is_active', 1);
    return $this->restrictToVisibleCustomers($query);
  }

  private function restrictToVisibleCustomers(mixed $query): mixed
  {
    $hubleto = \Hubleto\Erp\Loader::getGlobalApp();
    /** @var AccessScope */
    $accessScope = $hubleto->getService(AccessScope::class);
    return $accessScope->restrictToVisibleCustomers($query, $this->table . '.id');
  }
}
