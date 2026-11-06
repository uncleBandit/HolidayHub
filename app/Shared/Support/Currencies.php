<?php

namespace App\Shared\Support;

use Illuminate\Support\Str;
use NumberFormatter;
use ResourceBundle;

/**
 * The currencies a listing can be priced in: every country's own currency
 * (from ICU, so it stays current) plus the usual travel currencies.
 *
 * Prices are shown as "<symbol> <amount>" with the symbol travellers expect
 * (KSh, not KES; € and $ as usual).
 */
final class Currencies
{
    /** Always offered, and listed first. */
    private const MAJOR = ['USD', 'EUR', 'GBP', 'KES'];

    /** Symbols ICU leaves as the bare code in English but locals write differently. */
    private const SYMBOLS = [
        'KES' => 'KSh', 'UGX' => 'USh', 'TZS' => 'TSh', 'NGN' => '₦', 'GHS' => 'GH₵', 'ZAR' => 'R',
        'MAD' => 'MAD', 'EGP' => 'E£', 'AED' => 'AED', 'INR' => '₹', 'JPY' => '¥', 'CNY' => 'CN¥',
        'CHF' => 'CHF', 'AUD' => 'A$', 'CAD' => 'C$', 'NZD' => 'NZ$', 'RWF' => 'RF', 'ETB' => 'Br',
    ];

    private static ?array $all = null;

    /**
     * @return array<string, string> code => "Name (symbol)", majors first, then by name
     */
    public static function all(): array
    {
        if (self::$all !== null) {
            return self::$all;
        }

        $names = ResourceBundle::create('en', 'ICUDATA-curr')['Currencies'] ?? null;
        $codes = collect(Countries::codes())
            ->map(fn (string $country) => self::forCountry($country))
            ->merge(self::MAJOR)
            ->filter()
            ->unique();

        $list = $codes->mapWithKeys(function (string $code) use ($names) {
            $name = $names?->get($code)?->get(1) ?? $code;

            return [$code => $name];
        });

        $majors = collect(self::MAJOR)->mapWithKeys(fn ($code) => [$code => $list[$code]]);

        return self::$all = $majors
            ->union($list->except(self::MAJOR)->sort(SORT_NATURAL | SORT_FLAG_CASE))
            ->map(fn (string $name, string $code) => $name.' ('.self::symbol($code).')')
            ->all();
    }

    public static function codes(): array
    {
        return array_keys(self::all());
    }

    /** The local currency of a country ("JP" => "JPY"). */
    public static function forCountry(?string $countryCode): ?string
    {
        if (blank($countryCode)) {
            return null;
        }

        $code = (new NumberFormatter('en_'.Str::upper($countryCode), NumberFormatter::CURRENCY))
            ->getTextAttribute(NumberFormatter::CURRENCY_CODE);

        // ICU answers "XXX" when a region has no currency of its own.
        return $code && $code !== 'XXX' ? $code : null;
    }

    public static function symbol(?string $code): string
    {
        $code = Str::upper((string) ($code ?: 'KES'));

        if (isset(self::SYMBOLS[$code])) {
            return self::SYMBOLS[$code];
        }

        $symbol = (new NumberFormatter('en@currency='.$code, NumberFormatter::CURRENCY))->getSymbol(NumberFormatter::CURRENCY_SYMBOL);

        return $symbol ?: $code;
    }

    /** "KSh 24,500", "€ 180", "¥ 32,000". */
    public static function format(float|int|string|null $amount, ?string $code, int $decimals = 0): string
    {
        return self::symbol($code).' '.number_format((float) $amount, $decimals);
    }
}
