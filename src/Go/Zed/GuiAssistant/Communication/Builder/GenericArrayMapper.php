<?php

namespace Go\Zed\GuiAssistant\Communication\Builder;

final class GenericArrayMapper
{
    public static function get(mixed $data, string $path, mixed $default = null): mixed {
        if ($path === '') return $data;
        foreach (explode('.', $path) as $seg) {
            if (!is_array($data)) {
                return $default;               // <- don't touch array_key_exists on scalars
            }
            if (!array_key_exists($seg, $data)) {
                return $default;
            }
            $data = $data[$seg];
        }
        return $data;
    }

    public static function set(array &$target, string $path, mixed $value): void {
        if ($path === '') { $target = $value; return; }
        $ref = &$target;
        foreach (explode('.', $path) as $seg) {
            if (!isset($ref[$seg]) || !is_array($ref[$seg])) $ref[$seg] = [];
            $ref = &$ref[$seg];
        }
        $ref = $value;
    }

    /**
     * @param array $source
     * @param array $config
     * @param array $vars   External vars, e.g. ['stores'=>..., 'currencies'=>..., 'priceTypes'=>..., 'locales'=>...]
     */
    public static function map(array $source, array $config, array $vars = []): array {
        $result = [];
        foreach ($config as $rule) {
            if (isset($rule['map'])) {
                // List mapping
                $list = self::get($source, $rule['from'], []);
                $out  = [];
                $useAssoc = isset($rule['keyBy']) && is_callable($rule['keyBy']);
                $pluck    = $rule['pluck'] ?? null; // string|null

                foreach ((array)$list as $item) {
                    $mapped = self::mapListItem($item, $rule['map'], $source, $vars);

                    if ($useAssoc) {
                        $key = ($rule['keyBy'])($item, $source, $vars);
                        $out[$key] = $pluck ? ($mapped[$pluck] ?? null) : $mapped;
                    } else {
                        $out[] = $pluck ? ($mapped[$pluck] ?? null) : $mapped;
                    }
                }

                self::set($result, rtrim($rule['to'], '[]'), $out);
                continue;
            }

            // Scalar mapping
            if (array_key_exists('const', $rule)) {
                $value = $rule['const'];
            } else {
                $value = self::get($source, $rule['from']);
            }
            if (isset($rule['transform']) && is_callable($rule['transform'])) {
                $value = ($rule['transform'])($value, $source, $vars);
            }
            self::set($result, $rule['to'], $value);
        }
        return $result;
    }

    private static function getFromItem(mixed $item, string $path): mixed {
        if ($path === '' || $path === '$') return $item;
        return is_array($item) ? self::get($item, $path) : null;
    }

    private static function mapListItem(mixed $item, array $fieldMap, array $root, array $vars): array {
        $out = [];
        foreach ($fieldMap as $toKey => $def) {
            // nested object...
            if (is_array($def) && array_key_exists('map', $def)) {
                $out[$toKey] = self::mapListItem($item, $def['map'], $root, $vars);
                continue;
            }

            // lookupVar with support for scalar items via keyFrom: ''
            if (is_array($def) && isset($def['lookupVar'])) {
                $lk       = $def['lookupVar'];
                $varName  = $lk['var']     ?? null;
                $keyFrom  = $lk['keyFrom'] ?? null;   // '' or '$' => use scalar item
                $valuePath= $lk['path']    ?? null;

                $row = null;
                if ($varName !== null && array_key_exists($varName, $vars)) {
                    $key = ($keyFrom === '' || $keyFrom === '$')
                        ? $item
                        : (is_array($item)
                            ? (array_key_exists($keyFrom, $item) ? $item[$keyFrom] : self::get($item, $keyFrom))
                            : null);

                    if ($key !== null && isset($vars[$varName][$key])) {
                        $row = $vars[$varName][$key];
                        if (is_object($row) && method_exists($row, 'toArray')) {
                            $row = $row->toArray();   // auto-normalize objects
                        }
                    }
                }
                $out[$toKey] = $row ? ($valuePath ? self::get($row, $valuePath) : $row) : null;
                continue;
            }

            // field with 'from' (+ optional transform) — use scalar-safe reader
            if (is_array($def) && isset($def['from'])) {
                $val = self::getFromItem($item, $def['from']);
                if (isset($def['transform']) && is_callable($def['transform'])) {
                    $val = ($def['transform'])($val, $item, $root, $vars);
                }
                $out[$toKey] = $val;
                continue;
            }

            // simple path string inside item — scalar-safe
            if (is_string($def)) {
                $out[$toKey] = self::getFromItem($item, $def);
                continue;
            }

            $out[$toKey] = $def; // literal
        }
        return $out;
    }
}
