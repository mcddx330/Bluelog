<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class SettingService
{
    const CACHE_KEY_PREFIX = 'settings.';

    const KEY_SINGLE_USER_ONLY = 'single_user_only';
    const KEY_INVITATION_REQUIRED = 'invitation_required';

    /**
     * 設定値を取得する。
     *
     * @param string $key 設定のキー
     * @param string $default デフォルト値
     *
     * @return mixed
     */
    public function get(string $key, string $default = self::KEY_SINGLE_USER_ONLY): mixed
    {
        return Cache::rememberForever(self::CACHE_KEY_PREFIX . $key, function () use ($key, $default) {
            $setting = Setting::where('key', $key)->first();

            if (!$setting) {
                return $default;
            }

            return $this->castValue($setting->value, $setting->type);
        });
    }

    /**
     * 設定値を更新または作成する。
     *
     * @param string $key 設定のキー
     * @param mixed $value 設定値
     * @param string|null $type 値の型 (例: 'boolean', 'string', 'integer', 'json')
     * @param string|null $description 設定の説明
     * @return Setting
     */
    public function set(string $key, mixed $value, ?string $type = null, ?string $description = null): Setting
    {
        $setting = Setting::firstOrNew(['key' => $key]);
        $setting->value = $this->prepareValue($value, $type);
        $setting->type = $type ?? $this->detectType($value);
        $setting->description = $description ?? $setting->description;
        $setting->save();

        Cache::forget(self::CACHE_KEY_PREFIX . $key);

        return $setting;
    }

    /**
     * 値を適切な型にキャストする。
     *
     * @param string|null $value
     * @param string|null $type
     * @return mixed
     */
    protected function castValue(?string $value, ?string $type): mixed
    {
        return match ($type) {
            'boolean' => (bool) $value,
            'integer' => (int) $value,
            'json' => json_decode($value, true),
            default => $value,
        };
    }

    /**
     * 値を保存用に準備する。
     *
     * @param mixed $value
     * @param string|null $type
     * @return string|null
     */
    protected function prepareValue(mixed $value, ?string $type): ?string
    {
        if ($type === 'json' || (is_array($value) || is_object($value))) {
            return json_encode($value);
        }
        return (string) $value;
    }

    /**
     * 値から型を自動検出する。
     *
     * @param mixed $value
     * @return string
     */
    protected function detectType(mixed $value): string
    {
        if (is_bool($value)) {
            return 'boolean';
        } elseif (is_int($value)) {
            return 'integer';
        } elseif (is_array($value) || is_object($value)) {
            return 'json';
        } else {
            return 'string';
        }
    }
}
