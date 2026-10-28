<?php

namespace Hubleto\App\External\WaiBlue\HlthcrClients\HubletoOverrides;

use Hubleto\App\Community\Contacts\Models\Contact as CommunityContact;

/**
 * Hubleto's Contact model with the Hlthcr client visibility (registered in the HlthcrClients loader).
 * Hubleto shows every contact person to everybody (see the patches documents).
 */
class Contact extends CommunityContact
{
  use \Hubleto\App\External\WaiBlue\HlthcrCore\Models\Traits\OffersOnlyAllowedChanges;

  public string $recordManagerClass = ContactRecordManager::class;

  public static function register(): void
  {
    \Hubleto\Framework\DependencyInjection::setServiceProvider(CommunityContact::class, self::class);
  }

  public function __construct()
  {
    parent::__construct();

    // Behave as the community model everywhere else: same migrations, config keys and translations.
    $communityModel = new \ReflectionClass(CommunityContact::class);
    $this->fullName = $communityModel->getName();
    $this->shortName = $communityModel->getShortName();
    $this->namespace = $communityModel->getNamespaceName();
    $this->srcFolder = pathinfo((string) $communityModel->getFileName(), PATHINFO_DIRNAME);
    $this->translationContext = 'hubleto-app-community-contacts-loader';
    $this->translationContextInner = 'Models\\Contact';

    // Hubleto describes the columns in its constructor, before the name is reset; custom columns depend on it.
    $this->columns = $this->describeColumns();
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
