<?php

namespace Hubleto\App\External\WaiBlue\HlthcrClients\Models\RecordManagers;

class ClientType extends \Hubleto\Erp\RecordManager
{
  public $table = 'hlthcr_client_types';

  public function prepareReadQuery(mixed $query = null, int $level = 0, array|null $includeRelations = null): mixed
  {
    return parent::prepareReadQuery($query, $level, $includeRelations);
  }

  public function addUrlFiltersToQuery(mixed $query): mixed
  {
    return parent::addUrlFiltersToQuery($query);
  }

  public function prepareLookupData(array $dataRaw): array
  {
    return parent::prepareLookupData($dataRaw);
  }
}
