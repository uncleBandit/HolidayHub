<?php

namespace App\Shared\Support;

use Illuminate\Support\Str;
use Locale;

/**
 * Every country, keyed by its ISO 3166-1 alpha-2 code.
 *
 * Names come from ICU (the intl extension) so they are the standard English
 * names. Listings store the country *name*, as they always have; the code is
 * what map and place searches use to stay inside the right country.
 */
final class Countries
{
    private const CODES = [
        'AD', 'AE', 'AF', 'AG', 'AI', 'AL', 'AM', 'AO', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AW', 'AX', 'AZ',
        'BA', 'BB', 'BD', 'BE', 'BF', 'BG', 'BH', 'BI', 'BJ', 'BL', 'BM', 'BN', 'BO', 'BQ', 'BR', 'BS',
        'BT', 'BV', 'BW', 'BY', 'BZ', 'CA', 'CC', 'CD', 'CF', 'CG', 'CH', 'CI', 'CK', 'CL', 'CM', 'CN',
        'CO', 'CR', 'CU', 'CV', 'CW', 'CX', 'CY', 'CZ', 'DE', 'DJ', 'DK', 'DM', 'DO', 'DZ', 'EC', 'EE',
        'EG', 'EH', 'ER', 'ES', 'ET', 'FI', 'FJ', 'FK', 'FM', 'FO', 'FR', 'GA', 'GB', 'GD', 'GE', 'GF',
        'GG', 'GH', 'GI', 'GL', 'GM', 'GN', 'GP', 'GQ', 'GR', 'GS', 'GT', 'GU', 'GW', 'GY', 'HK', 'HM',
        'HN', 'HR', 'HT', 'HU', 'ID', 'IE', 'IL', 'IM', 'IN', 'IO', 'IQ', 'IR', 'IS', 'IT', 'JE', 'JM',
        'JO', 'JP', 'KE', 'KG', 'KH', 'KI', 'KM', 'KN', 'KP', 'KR', 'KW', 'KY', 'KZ', 'LA', 'LB', 'LC',
        'LI', 'LK', 'LR', 'LS', 'LT', 'LU', 'LV', 'LY', 'MA', 'MC', 'MD', 'ME', 'MF', 'MG', 'MH', 'MK',
        'ML', 'MM', 'MN', 'MO', 'MP', 'MQ', 'MR', 'MS', 'MT', 'MU', 'MV', 'MW', 'MX', 'MY', 'MZ', 'NA',
        'NC', 'NE', 'NF', 'NG', 'NI', 'NL', 'NO', 'NP', 'NR', 'NU', 'NZ', 'OM', 'PA', 'PE', 'PF', 'PG',
        'PH', 'PK', 'PL', 'PM', 'PN', 'PR', 'PS', 'PT', 'PW', 'PY', 'QA', 'RE', 'RO', 'RS', 'RU', 'RW',
        'SA', 'SB', 'SC', 'SD', 'SE', 'SG', 'SH', 'SI', 'SJ', 'SK', 'SL', 'SM', 'SN', 'SO', 'SR', 'SS',
        'ST', 'SV', 'SX', 'SY', 'SZ', 'TC', 'TD', 'TF', 'TG', 'TH', 'TJ', 'TK', 'TL', 'TM', 'TN', 'TO',
        'TR', 'TT', 'TV', 'TW', 'TZ', 'UA', 'UG', 'UM', 'US', 'UY', 'UZ', 'VA', 'VC', 'VE', 'VG', 'VI',
        'VN', 'VU', 'WF', 'WS', 'XK', 'YE', 'YT', 'ZA', 'ZM', 'ZW',
    ];

    private static ?array $names = null;

    /**
     * @return array<string, string> code => name, sorted by name
     */
    public static function all(): array
    {
        if (self::$names === null) {
            $names = [];

            foreach (self::CODES as $code) {
                $name = Locale::getDisplayRegion('-'.$code, 'en');
                // ICU answers with the code itself when it has no name for it.
                $names[$code] = $name && $name !== $code ? $name : $code;
            }

            asort($names, SORT_NATURAL | SORT_FLAG_CASE);
            self::$names = $names;
        }

        return self::$names;
    }

    public static function codes(): array
    {
        return array_keys(self::all());
    }

    public static function name(?string $code): ?string
    {
        return $code ? (self::all()[Str::upper($code)] ?? null) : null;
    }

    /**
     * The code for a stored country name ("Kenya" => "KE"), forgiving case
     * and accents. Also accepts a code.
     */
    public static function codeFor(?string $country): ?string
    {
        if (blank($country)) {
            return null;
        }

        $country = trim($country);

        if (strlen($country) === 2 && isset(self::all()[Str::upper($country)])) {
            return Str::upper($country);
        }

        $needle = Str::lower(Str::ascii($country));

        foreach (self::all() as $code => $name) {
            if (Str::lower(Str::ascii($name)) === $needle) {
                return $code;
            }
        }

        return null;
    }
}
