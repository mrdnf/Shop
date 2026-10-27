<?php
declare(strict_types=1);

namespace App\Core;

final class View
{
    private static array $shared = [];
    private static array $stacks = [];

    /** Collect HTML (extra <head> tags, page scripts) for the layout to output. */
    public static function push(string $stack, string $html): void
    {
        self::$stacks[$stack][] = $html;
    }

    public static function stack(string $stack): string
    {
        return implode("\n", self::$stacks[$stack] ?? []);
    }

    public static function share(string $key, mixed $value): void
    {
        self::$shared[$key] = $value;
    }

    public static function render(string $view, array $data = [], ?string $layout = null): string
    {
        $data = array_merge(self::$shared, $data);
        $content = self::partial($view, $data);
        if ($layout === null) {
            return $content;
        }
        return self::partial($layout, array_merge($data, ['content' => $content]));
    }

    public static function partial(string $view, array $data = []): string
    {
        $file = BASE_PATH . '/app/Views/' . $view . '.php';
        if (!is_file($file)) {
            throw new \RuntimeException("View not found: {$view}");
        }
        extract(array_merge(self::$shared, $data), EXTR_SKIP);
        ob_start();
        try {
            include $file;
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }
        return (string) ob_get_clean();
    }
}
