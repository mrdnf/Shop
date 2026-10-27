<?php
declare(strict_types=1);

namespace App\Core;

abstract class Controller
{
    protected string $layout = 'layouts/shop';

    protected function view(string $view, array $data = [], ?string $layout = null): string
    {
        return View::render($view, $data, $layout ?? $this->layout);
    }

    /**
     * Validate request input; on failure flash errors + old input and go back.
     */
    protected function validate(array $rules): array
    {
        $data = [];
        foreach (array_keys($rules) as $field) {
            $data[$field] = Request::input($field);
        }
        $errors = Validator::check($data, $rules);
        if ($errors) {
            if (Request::wantsJson()) {
                json_response(['errors' => $errors, 'message' => reset($errors)], 422);
            }
            Session::flash('errors', $errors);
            Session::flash('old', Request::all());
            Session::flash('error', reset($errors));
            back();
        }
        return $data;
    }

    protected function db(): Database
    {
        return DB::tenant();
    }
}
