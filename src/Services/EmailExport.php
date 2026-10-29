<?php

namespace Hubleto\App\External\WaiBlue\HlthcrClients\Services;

use Hubleto\App\Community\Auth\Models\User;
use Hubleto\App\Community\Auth\Models\UserRole;
use Hubleto\App\Community\Contacts\Models\Contact;
use Hubleto\App\External\WaiBlue\HlthcrCore\Services\AccessScope;
use Hubleto\App\External\WaiBlue\HlthcrCore\Services\Roles;

/**
 * E-mail addresses for the CSV export of both legacy apps ("Export emailových adries"):
 * users of one role, all users, or the contact persons of the clients the user sees.
 */
class EmailExport extends \Hubleto\Erp\Core
{
  const OPTION_ALL_USERS = 'all-users';
  const OPTION_CLIENT_CONTACTS = 'client-contacts';
  const OPTION_ROLE_PREFIX = 'role-';

  public string $translationContext = 'hubleto-app-external-waiblue-hlthcrclients-loader';
  public string $translationContextInner = 'Services\\EmailExport';

  // [option value => label] for the export form.
  public function getOptions(): array
  {
    $options = [self::OPTION_ALL_USERS => $this->translate('All users')];

    /** @var Roles */
    $roles = $this->getService(Roles::class);
    /** @var UserRole */
    $mUserRole = $this->getModel(UserRole::class);

    foreach (array_keys($roles->getRoleDefinitions()) as $roleKey) {
      $role = $mUserRole->record->where('id', $roles->getRoleId($roleKey))->first();
      if (!$role) continue;
      $options[self::OPTION_ROLE_PREFIX . $roleKey] = $role->role;
    }

    $options[self::OPTION_CLIENT_CONTACTS] = $this->translate('Client contact persons');
    return $options;
  }

  public function getEmails(string $option): array
  {
    if ($option == self::OPTION_CLIENT_CONTACTS) {
      return $this->getClientContactEmails();
    }

    /** @var User */
    $mUser = $this->getModel(User::class);
    $usersQuery = $mUser->record->where('is_active', true)->orderBy('last_name');

    $isRoleOption = str_starts_with($option, self::OPTION_ROLE_PREFIX);
    if ($isRoleOption) {
      $roleKey = substr($option, strlen(self::OPTION_ROLE_PREFIX));
      /** @var Roles */
      $roles = $this->getService(Roles::class);
      $usersQuery = $usersQuery->whereIn('id', $roles->userIdsWithRole($roleKey));
    } else if ($option != self::OPTION_ALL_USERS) {
      return [];
    }

    $emails = [];
    foreach ($usersQuery->get() as $user) {
      $this->addEmail($emails, (string) $user->email);
    }
    return $emails;
  }

  // Contact persons of active clients the active user sees.
  private function getClientContactEmails(): array
  {
    /** @var Contact */
    $mContact = $this->getModel(Contact::class);
    $query = $mContact->record
      ->with(['VALUES'])
      ->whereIn('contacts.id_customer', function ($activeCustomers) {
        $activeCustomers->select('id')->from('customers')->where('is_active', 1);
      })
    ;
    $query = $this->getService(AccessScope::class)->restrictToVisibleCustomers($query, 'contacts.id_customer');

    $emails = [];
    foreach ($query->get() as $contact) {
      foreach ($contact->VALUES as $value) {
        if ($value->type != 'email') continue;
        $this->addEmail($emails, (string) $value->value);
      }
    }
    return $emails;
  }

  private function addEmail(array &$emails, string $email): void
  {
    $email = trim($email);
    if ($email == '' || in_array($email, $emails)) return;
    $emails[] = $email;
  }
}
