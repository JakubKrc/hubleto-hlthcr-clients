<?php

namespace Hubleto\App\External\WaiBlue\HlthcrClients\Controllers\Api;

use Hubleto\App\External\WaiBlue\HlthcrClients\Services\EmailExport;

class DownloadEmailExport extends \Hubleto\Erp\Controller
{
  // Raw CSV, without the Hubleto desktop around it.
  public int $returnType = self::RETURN_TYPE_STRING;

  public function renderString(): string
  {
    $option = $this->router()->urlParamAsString('option');
    $emails = $this->getService(EmailExport::class)->getEmails($option);

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="email-export-' . date('Y-m-d') . '.csv"');

    $csv = "email\n";
    foreach ($emails as $email) {
      $csv .= $email . "\n";
    }
    return $csv;
  }
}
