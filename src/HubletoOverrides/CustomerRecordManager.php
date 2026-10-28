<?php

namespace Hubleto\App\External\WaiBlue\HlthcrClients\HubletoOverrides;

use Hubleto\App\External\WaiBlue\HlthcrCore\Models\RecordManagers\Traits\OffersActiveRecords;
use Hubleto\App\External\WaiBlue\HlthcrCore\Services\AccessScope;

/**
 * Customers the user may see, also in dropdowns (a hygienist: own clients).
 */
class CustomerRecordManager extends \Hubleto\App\Community\Customers\Models\RecordManagers\Customer
{
  use OffersActiveRecords;

  public function prepareReadQuery(mixed $query = null, int $level = 0, array|null $includeRelations = null): mixed
  {
    if ($this->accessScope()->activeUserHasHlthcrRole()) {
      // The Hlthcr role decides, so Hubleto's limit by user type is left out (customers have no junctions).
      $query = \Hubleto\Framework\RecordManager::prepareReadQuery($query, $level, $includeRelations);
      $query = $this->addTagsCount($query);
    } else {
      $query = parent::prepareReadQuery($query, $level, $includeRelations);
    }

    return $this->restrictToVisibleCustomers($query);
  }

  // Also builds the text of linked clients, so the owner of a branch sees the name of its client.
  public function prepareLookupQuery(string $search): mixed
  {
    $query = parent::prepareLookupQuery($search);
    return $this->accessScope()->restrictToLinkableCustomers($query, $this->table . '.id');
  }

  // Dropdowns offer active clients only, as in both legacy apps.
  public function restrictToOfferedRecords(mixed $query): mixed
  {
    return $this->restrictToActiveOrChosen($query, 'id_customer');
  }

  // The same virtual column as the community record manager adds.
  private function addTagsCount(mixed $query): mixed
  {
    return $query->selectSub(function ($tagsOfCustomer) {
      $tagsOfCustomer->from('cross_customer_tags')
        ->join('customer_tags', 'customer_tags.id', '=', 'cross_customer_tags.id_tag')
        ->whereColumn('cross_customer_tags.id_customer', 'customers.id')
        ->selectRaw('COUNT(DISTINCT customer_tags.id)');
    }, 'tags_count');
  }

  private function restrictToVisibleCustomers(mixed $query): mixed
  {
    return $this->accessScope()->restrictToVisibleCustomers($query, $this->table . '.id');
  }

  private function accessScope(): AccessScope
  {
    $hubleto = \Hubleto\Erp\Loader::getGlobalApp();
    return $hubleto->getService(AccessScope::class);
  }
}
