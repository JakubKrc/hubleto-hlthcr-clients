<?php

namespace Hubleto\App\External\WaiBlue\HlthcrClients\Controllers;

class Home extends \Hubleto\Erp\Controller
{
  public function prepareView(): void
  {
    parent::prepareView();
    $this->setView('@Hubleto:App:External:WaiBlue:HlthcrClients/Home.twig');
  }
}
