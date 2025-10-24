<?php
if (! defined('ABSPATH')) {
  exit;
}

function xppwots_get_option(string $key, $default = null)
{
  $o = get_option('xppwots_settings', []);
  return $o[$key] ?? $default;
}

function xppwots_now(): int
{
  return time();
}

function xppwots_format_time(int $ts): string
{
  return date_i18n('Y-m-d H:i', $ts);
}

function xppwots_mock_records(): array
{
  return [
    ['id' => 1, 'title' => 'Item A', 'updated_at' => time() - 3600, 'content' => 'Example content A'],
    ['id' => 2, 'title' => 'Item B', 'updated_at' => time() - 7200, 'content' => 'Example content B'],
    ['id' => 3, 'title' => 'Item C', 'updated_at' => time() - 86400, 'content' => 'Example content C'],
  ];
}
