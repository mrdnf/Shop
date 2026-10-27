<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Crypto;
use App\Core\Request;

abstract class AdminController extends Controller
{
    protected string $layout = 'layouts/admin';

    /**
     * Read a dynamic config form (inventory provider / courier fields).
     * Empty password fields keep the previously stored secret.
     */
    protected function readConfigFields(array $fields, array $previous, string $prefix = 'cfg'): array
    {
        $input = (array) Request::input($prefix, []);
        $config = [];
        foreach ($fields as $field) {
            $name = $field['name'];
            $value = is_string($input[$name] ?? null) ? trim($input[$name]) : '';
            if ($field['type'] === 'password' && $value === '') {
                $value = (string) ($previous[$name] ?? '');
            }
            $config[$name] = $value;
        }
        return $config;
    }

    protected function encryptConfig(array $config): string
    {
        return Crypto::encryptArray($config);
    }
}
