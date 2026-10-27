<?php
declare(strict_types=1);

namespace App\Core;

final class App
{
    public function run(): void
    {
        try {
            Session::start();
            $path = Request::path();
            $router = new Router();

            if (!Config::installed()) {
                Lang::detect('en');
                if (!str_starts_with($path, '/install')) {
                    redirect(base_url('/install'));
                }
                (require BASE_PATH . '/routes/install.php')($router);
                $this->dispatch($router, $path);
                return;
            }

            [$tenant, $path] = Tenant::resolve(Request::host(), $path);

            if ($tenant) {
                Tenant::set($tenant);
                Lang::detect((string) Settings::get('theme.default_locale', 'en'));
                if ($tenant['status'] === 'suspended' && !str_starts_with($path, '/lang')) {
                    http_response_code(503);
                    echo View::render('errors/suspended', ['tenant' => $tenant]);
                    return;
                }
                (require BASE_PATH . '/routes/tenant.php')($router);
            } else {
                Lang::detect((string) Settings::get('theme.default_locale', 'en'));
                (require BASE_PATH . '/routes/platform.php')($router);
            }

            $this->dispatch($router, $path);
        } catch (HttpException $e) {
            // Validation-type errors on a form post: go back to the form with a message.
            if ($e->status === 422 && Request::method() === 'POST' && !Request::wantsJson() && !empty($_SERVER['HTTP_REFERER'])) {
                Session::flash('error', $e->getMessage() ?: __('Something is not right'));
                Session::flash('old', $_POST);
                back();
            }
            $this->renderError($e->status, $e->getMessage());
        } catch (\Throwable $e) {
            error_log((string) $e);
            $this->renderError(500, Config::get('debug') ? $e->getMessage() . "\n" . $e->getFile() . ':' . $e->getLine() : '');
        }
    }

    private function dispatch(Router $router, string $path): void
    {
        $match = $router->match(Request::method(), $path);
        if (!$match) {
            throw new HttpException(404);
        }
        [$handler, $params, $middleware] = $match;

        if (Request::method() !== 'GET' && Request::method() !== 'HEAD'
            && !in_array('nocsrf', $middleware, true) && !Csrf::verify()) {
            throw new HttpException(419, __('Your session expired, please try again.'));
        }

        foreach ($middleware as $name) {
            Middleware::handle($name);
        }

        $args = array_values($params);
        $result = $handler instanceof \Closure
            ? $handler(...$args)
            : (new $handler[0]())->{$handler[1]}(...$args);

        if (is_array($result)) {
            json_response($result);
        }
        if (is_string($result)) {
            echo $result;
        }
    }

    private function renderError(int $status, string $message): void
    {
        if (!headers_sent()) {
            http_response_code($status);
        }
        if (Request::wantsJson() || str_starts_with(Request::path(), '/api')) {
            json_response(['error' => $message ?: 'Error ' . $status], $status);
        }
        try {
            echo View::render('errors/error', ['status' => $status, 'message' => $message]);
        } catch (\Throwable $e) {
            echo '<h1>' . $status . '</h1><pre>' . e($message) . '</pre>';
        }
    }
}
