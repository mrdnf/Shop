<?php
declare(strict_types=1);

namespace App\Controllers\Platform;

use App\Core\Controller;
use App\Core\Database;
use App\Core\DB;

abstract class PlatformController extends Controller
{
    protected string $layout = 'layouts/platform';

    protected function db(): Database
    {
        return DB::platform();
    }
}
