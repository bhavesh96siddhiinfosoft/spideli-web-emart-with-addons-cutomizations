<?php

namespace App\Support;

use App\Helpers\FirestoreHelper;

/**
 * Client point 68: every add-on has its own quantity, independent of the
 * product's (2 Organic Eggs, 3 Cold Pack, 1 Insulated Bag). The contract is
 * ADDON-QUANTITY-SPEC.md (30 Oct 2026); the browser half of these rules is
 * in layouts/footer (lineAddonsTotal, lineAddonRows, lineTotal, writeAddons).
 *
 * A line - a session cart line or an order line - carries
 *   addons  [{name, price, quantity}]   the new format, the source of truth
 * and, for readers that predate it,
 *   extra / extras        the names
 *   extra_price           (cart) / extras_price (order) = add-ons total / quantity
 * so the old formula (unit + extra_price) x quantity still gives the right
 * total. A line WITHOUT `addons` is a legacy line: extra_price charged once
 * per product unit.
 */
class OrderLineAddons
{
    private static $docs = [];

    /** Total of a line's add-ons. */
    public static function total(array $line)
    {
        if (isset($line['addons']) && is_array($line['addons'])) {
            $total = 0.0;
            foreach ($line['addons'] as $a) {
                $total += (float)($a['price'] ?? 0) * (int)($a['quantity'] ?? 0);
            }
            return $total;
        }
        $perUnit = $line['extra_price'] ?? ($line['extras_price'] ?? 0);
        return (float)$perUnit * (int)($line['quantity'] ?? 0); // legacy
    }

    /** Product part of a cart line: the charged unit price x quantity. */
    public static function cartLineTotal(array $line)
    {
        return (float)($line['item_price'] ?? 0) * (int)($line['quantity'] ?? 0) + self::total($line);
    }

    /** Re-derives the compatibility fields from `addons`. Call after every change. */
    public static function syncCompat(array $line)
    {
        if (!isset($line['addons']) || !is_array($line['addons'])) {
            return $line;
        }
        $line['addons'] = self::clean($line['addons']);
        $quantity = (int)($line['quantity'] ?? 0);
        $line['extra'] = array_map(function ($a) { return $a['name']; }, $line['addons']);
        $line['extra_price'] = $quantity > 0 ? self::total($line) / $quantity : 0;
        return $line;
    }

    /** Read tolerantly: drop blank names and quantities below 1. */
    public static function clean($addons)
    {
        $out = [];
        foreach ((array)$addons as $a) {
            if (!is_array($a)) {
                continue;
            }
            $name = trim((string)($a['name'] ?? ''));
            $quantity = (int)($a['quantity'] ?? 0);
            if ($name === '' || $quantity < 1) {
                continue;
            }
            $out[] = ['name' => $name, 'price' => (float)($a['price'] ?? 0), 'quantity' => $quantity];
        }
        return $out;
    }

    /**
     * Prices the requested add-ons from the product itself - never from the
     * browser. $requested: [{name, quantity}]. Unknown names and quantities
     * below 1 are dropped. Returns null when the product cannot be read.
     */
    public static function price($productId, $requested)
    {
        $product = self::doc('vendor_products/' . $productId);
        if (!$product) {
            return null;
        }
        $titles = array_values((array)($product['addOnsTitle'] ?? []));
        $prices = array_values((array)($product['addOnsPrice'] ?? []));
        $byName = [];
        foreach ($titles as $i => $title) {
            $key = mb_strtolower(trim((string)$title));
            if ($key !== '' && !isset($byName[$key])) {
                $byName[$key] = ['name' => trim((string)$title), 'base' => (float)($prices[$i] ?? 0)];
            }
        }
        $rule = self::commissionRule((string)($product['vendorID'] ?? ''));

        $out = [];
        foreach ((array)$requested as $r) {
            $key = mb_strtolower(trim((string)($r['name'] ?? '')));
            $quantity = (int)($r['quantity'] ?? 0);
            if ($quantity < 1 || !isset($byName[$key])) {
                continue;
            }
            $out[$key] = [
                'name' => $byName[$key]['name'],
                'price' => self::withCommission($byName[$key]['base'], $rule),
                'quantity' => $quantity,
            ];
        }
        return array_values($out);
    }

    /** The product's add-on titles, or null when the product cannot be read. */
    public static function productAddonTitles($productId)
    {
        $product = self::doc('vendor_products/' . $productId);
        return $product ? array_map('strval', array_values((array)($product['addOnsTitle'] ?? []))) : null;
    }

    /* The commission of the product's own price on the website (footer
     * processVendorData): the store's own rate, else the section's - applied
     * only while settings/AdminCommission.isEnabled and the section's
     * adminCommision.enable are both on. */
    private static function commissionRule($vendorId)
    {
        $global = self::doc('settings/AdminCommission');
        if (!$global || ($global['isEnabled'] ?? false) !== true || $vendorId === '') {
            return null;
        }
        $vendor = self::doc('vendors/' . $vendorId);
        $section = $vendor && !empty($vendor['section_id']) ? self::doc('sections/' . $vendor['section_id']) : null;
        $sectionRule = $section['adminCommision'] ?? null;
        if (!is_array($sectionRule) || ($sectionRule['enable'] ?? false) !== true) {
            return null;
        }
        $rule = (is_array($vendor['adminCommission'] ?? null) && $vendor['adminCommission']) ? $vendor['adminCommission'] : $sectionRule;
        return ['type' => (string)($rule['type'] ?? ''), 'value' => (float)($rule['commission'] ?? 0)];
    }

    /** A free add-on stays free: a fixed commission is never added to 0. */
    public static function withCommission($base, $rule)
    {
        $base = (float)$base;
        if ($base <= 0) {
            return 0.0;
        }
        if ($rule && $rule['type'] === 'percentage') {
            $base += $base * $rule['value'] / 100;
        } elseif ($rule && $rule['type'] === 'fixed') {
            $base += $rule['value'];
        }
        return round($base, 2);
    }

    private static function doc($path)
    {
        if (!array_key_exists($path, self::$docs)) {
            try {
                self::$docs[$path] = FirestoreHelper::getDocument($path);
            } catch (\Throwable $e) {
                self::$docs[$path] = null;
            }
        }
        return self::$docs[$path];
    }
}
