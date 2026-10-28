<?php

namespace Hubleto\App\External\WaiBlue\HlthcrClients\Models;

use Hubleto\Framework\Db\Column\Varchar;

/**
 * Type of the service contracted by the client. Both legacy apps have it, with different values,
 * so it is a code list the administrator edits.
 */
class ClientType extends \Hubleto\App\External\WaiBlue\HlthcrCore\Models\HlthcrModel
{
  public string $table = 'hlthcr_client_types';
  public string $recordManagerClass = RecordManagers\ClientType::class;
  public ?string $lookupSqlValue = '{%TABLE%}.name';
  public ?string $lookupUrlDetail = 'hlthcr-clients/client-types/{%ID%}';

  public function describeColumns(): array
  {
    return array_merge(parent::describeColumns(), [
      'name' => (new Varchar($this, $this->translate('Name')))->setRequired()->setDefaultVisible(),
    ]);
  }

  public function describeTable(): \Hubleto\Framework\Description\Table
  {
    $description = parent::describeTable();
    $description->ui['addButtonText'] = $this->translate('Add client type');
    $description->show(['header', 'fulltextSearch', 'columnSearch', 'moreActionsButton']);
    $description->hide(['footer']);
    return $description;
  }

  public function describeForm(): \Hubleto\Framework\Description\Form
  {
    return parent::describeForm();
  }

  // [id => name] for the client type column of the customers.
  public function getNamesById(): array
  {
    $namesById = [];
    foreach ($this->record->orderBy('id')->get() as $clientType) {
      $namesById[$clientType->id] = $clientType->name;
    }
    return $namesById;
  }
}
