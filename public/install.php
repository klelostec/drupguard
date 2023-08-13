<?php

use App\InstallKernel;
$_SERVER['APP_RUNTIME_OPTIONS'] = ['disable_dotenv' => TRUE];
require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (array $context) {
    return new InstallKernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
