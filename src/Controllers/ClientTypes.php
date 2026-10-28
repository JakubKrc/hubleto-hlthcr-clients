<?php

namespace Hubleto\App\External\WaiBlue\HlthcrClients\Controllers;

class ClientTypes extends \Hubleto\Erp\Controller
{
  public function prepareView(): void
  {
    parent::prepareView();
    $this->setView('@Hubleto:App:External:WaiBlue:HlthcrClients/ClientTypes.twig');
  }
}
