<?php
/**
 * Renders a connector configuration form from its schema.
 * @var array  $fields
 * @var array  $values
 * @var string $prefix input name prefix (cfg)
 */
$prefix = $prefix ?? 'cfg';
?>
<div class="grid md:grid-cols-2 gap-4">
    <?php foreach ($fields as $f):
        $name = $prefix . '[' . $f['name'] . ']';
        $value = $values[$f['name']] ?? ($f['default'] ?? '');
        $wide = in_array($f['type'], ['textarea'], true) || str_contains($f['name'], 'url') || str_contains($f['name'], 'query');
        ?>
        <label class="field <?= $wide ? 'md:col-span-2' : '' ?>">
            <span class="label-text"><?= e(__($f['label'])) ?><?= !empty($f['required']) ? ' <span class="text-error">*</span>' : '' ?></span>
            <?php if ($f['type'] === 'select'): ?>
                <select name="<?= e($name) ?>" class="select w-full">
                    <?php foreach ($f['options'] as $k => $label): ?>
                        <option value="<?= e($k) ?>" <?= (string) $value === (string) $k ? 'selected' : '' ?>><?= e(__($label)) ?></option>
                    <?php endforeach; ?>
                </select>
            <?php elseif ($f['type'] === 'textarea'): ?>
                <textarea name="<?= e($name) ?>" rows="<?= str_contains($f['name'], 'body') || str_contains($f['name'], 'map') ? 7 : 3 ?>" class="textarea w-full font-mono text-sm" dir="ltr" placeholder="<?= e($f['placeholder'] ?? '') ?>"><?= e($value) ?></textarea>
            <?php elseif ($f['type'] === 'password'): ?>
                <input type="password" name="<?= e($name) ?>" class="input w-full" dir="ltr" autocomplete="new-password"
                       placeholder="<?= !empty($values[$f['name']]) || !empty($hasSecret) ? '•••••••• ' . e(__('(unchanged)')) : '' ?>">
            <?php else: ?>
                <input type="<?= $f['type'] === 'number' ? 'number' : 'text' ?>" name="<?= e($name) ?>" value="<?= e($value) ?>" class="input w-full" dir="ltr" placeholder="<?= e($f['placeholder'] ?? '') ?>">
            <?php endif; ?>
            <?php if (!empty($f['help'])): ?><span class="text-xs opacity-60"><?= e(__($f['help'])) ?></span><?php endif; ?>
        </label>
    <?php endforeach; ?>
</div>
