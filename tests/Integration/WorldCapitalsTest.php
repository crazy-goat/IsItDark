<?php

declare(strict_types=1);

namespace CrazyGoat\IsItDark\Tests\Integration;

use CrazyGoat\IsItDark\Enum\SunState;
use CrazyGoat\IsItDark\IsItDark;
use CrazyGoat\IsItDark\Location;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;

class WorldCapitalsTest extends TestCase
{
    /**
     * @dataProvider capitalsProvider
     */
    public function testCapitalDoesNotThrow(string $capital, string $country, float $lat, float $lng): void
    {
        $location = new Location($lat, $lng);
        $utc = new DateTimeZone('UTC');

        $summerNoon = new DateTimeImmutable('2025-06-21 12:00:00', $utc);
        $winterNoon = new DateTimeImmutable('2025-12-21 12:00:00', $utc);
        $summerMidnight = new DateTimeImmutable('2025-06-21 00:00:00', $utc);

        $isItDarkSummerNoon = new IsItDark($location, $summerNoon);
        $isItDarkWinterNoon = new IsItDark($location, $winterNoon);
        $isItDarkSummerMidnight = new IsItDark($location, $summerMidnight);

        self::assertIsBool($isItDarkSummerNoon->isDark(), "{$capital}, {$country}: isDark() should return bool at summer noon");
        self::assertIsBool($isItDarkWinterNoon->isDark(), "{$capital}, {$country}: isDark() should return bool at winter noon");
        self::assertIsBool($isItDarkSummerMidnight->isDark(), "{$capital}, {$country}: isDark() should return bool at summer midnight");

        self::assertNotSame($isItDarkSummerNoon->isDark(), $isItDarkSummerNoon->isDay(), "{$capital}, {$country}: isDark() and isDay() must be opposites");
        self::assertNotSame($isItDarkWinterNoon->isDark(), $isItDarkWinterNoon->isDay(), "{$capital}, {$country}: isDark() and isDay() must be opposites");
        self::assertNotSame($isItDarkSummerMidnight->isDark(), $isItDarkSummerMidnight->isDay(), "{$capital}, {$country}: isDark() and isDay() must be opposites");
    }

    /**
     * @dataProvider capitalsProvider
     */
    public function testCapitalStateIsConsistentWithDark(string $capital, string $country, float $lat, float $lng): void
    {
        $location = new Location($lat, $lng);
        $utc = new DateTimeZone('UTC');
        $noon = new DateTimeImmutable('2025-06-21 12:00:00', $utc);

        $isItDark = new IsItDark($location, $noon);
        $isDark = $isItDark->isDark();
        $state = $isItDark->state();

        if ($isDark) {
            self::assertNotSame(SunState::DAY, $state, "{$capital}, {$country}: isDark() is true but state() is DAY");
        } else {
            self::assertInstanceOf(SunState::class, $state, "{$capital}, {$country}: isDay() is true but state() is invalid");
        }
    }

    public static function capitalsProvider(): array
    {
        return [
            ['Oranjestad', 'Aruba', 12.5, -69.96666666],
            ['Kabul', 'Afghanistan', 33, 65],
            ['Luanda', 'Angola', -12.5, 18.5],
            ['The Valley', 'Anguilla', 18.25, -63.16666666],
            ['Mariehamn', 'Åland Islands', 60.116667, 19.9],
            ['Tirana', 'Albania', 41, 20],
            ['Andorra la Vella', 'Andorra', 42.5, 1.5],
            ['Abu Dhabi', 'United Arab Emirates', 24, 54],
            ['Buenos Aires', 'Argentina', -34, -64],
            ['Yerevan', 'Armenia', 40, 45],
            ['Pago Pago', 'American Samoa', -14.33333333, -170],
            ['Port-aux-Français', 'French Southern and Antarctic Lands', -49.25, 69.167],
            ['Saint John\'s', 'Antigua and Barbuda', 17.05, -61.8],
            ['Canberra', 'Australia', -27, 133],
            ['Vienna', 'Austria', 47.33333333, 13.33333333],
            ['Baku', 'Azerbaijan', 40.5, 47.5],
            ['Gitega', 'Burundi', -3.5, 30],
            ['Brussels', 'Belgium', 50.83333333, 4],
            ['Porto-Novo', 'Benin', 9.5, 2.25],
            ['Ouagadougou', 'Burkina Faso', 13, -2],
            ['Dhaka', 'Bangladesh', 24, 90],
            ['Sofia', 'Bulgaria', 43, 25],
            ['Manama', 'Bahrain', 26, 50.55],
            ['Nassau', 'Bahamas', 24.25, -76],
            ['Sarajevo', 'Bosnia and Herzegovina', 44, 18],
            ['Gustavia', 'Saint Barthélemy', 18.5, -63.41666666],
            ['Jamestown', 'Saint Helena, Ascension and Tristan da Cunha', -15.95, -5.72],
            ['Minsk', 'Belarus', 53, 28],
            ['Belmopan', 'Belize', 17.25, -88.75],
            ['Hamilton', 'Bermuda', 32.33333333, -64.75],
            ['Sucre', 'Bolivia', -17, -65],
            ['Kralendijk', 'Caribbean Netherlands', 12.18, -68.25],
            ['Brasília', 'Brazil', -10, -55],
            ['Bridgetown', 'Barbados', 13.16666666, -59.53333333],
            ['Bandar Seri Begawan', 'Brunei', 4.5, 114.66666666],
            ['Thimphu', 'Bhutan', 27.5, 90.5],
            ['Gaborone', 'Botswana', -22, 24],
            ['Bangui', 'Central African Republic', 7, 21],
            ['Ottawa', 'Canada', 60, -95],
            ['West Island', 'Cocos (Keeling) Islands', -12.5, 96.83333333],
            ['Bern', 'Switzerland', 47, 8],
            ['Santiago', 'Chile', -30, -71],
            ['Beijing', 'China', 35, 105],
            ['Yamoussoukro', 'Ivory Coast', 8, -5],
            ['Yaoundé', 'Cameroon', 6, 12],
            ['Kinshasa', 'DR Congo', 0, 25],
            ['Brazzaville', 'Congo', -1, 15],
            ['Avarua', 'Cook Islands', -21.23333333, -159.76666666],
            ['Bogotá', 'Colombia', 4, -72],
            ['Moroni', 'Comoros', -12.16666666, 44.25],
            ['Praia', 'Cape Verde', 16, -24],
            ['San José', 'Costa Rica', 10, -84],
            ['Havana', 'Cuba', 21.5, -80],
            ['Willemstad', 'Curaçao', 12.116667, -68.933333],
            ['Flying Fish Cove', 'Christmas Island', -10.5, 105.66666666],
            ['George Town', 'Cayman Islands', 19.5, -80.5],
            ['Nicosia', 'Cyprus', 35, 33],
            ['Prague', 'Czechia', 49.75, 15.5],
            ['Berlin', 'Germany', 51, 9],
            ['Djibouti', 'Djibouti', 11.5, 43],
            ['Roseau', 'Dominica', 15.41666666, -61.33333333],
            ['Copenhagen', 'Denmark', 56, 10],
            ['Santo Domingo', 'Dominican Republic', 19, -70.66666666],
            ['Algiers', 'Algeria', 28, 3],
            ['Quito', 'Ecuador', -2, -77.5],
            ['Cairo', 'Egypt', 27, 30],
            ['Asmara', 'Eritrea', 15, 39],
            ['El Aaiún', 'Western Sahara', 24.5, -13],
            ['Madrid', 'Spain', 40, -4],
            ['Tallinn', 'Estonia', 59, 26],
            ['Addis Ababa', 'Ethiopia', 8, 38],
            ['Helsinki', 'Finland', 64, 26],
            ['Suva', 'Fiji', -18, 175],
            ['Stanley', 'Falkland Islands', -51.75, -59],
            ['Paris', 'France', 46, 2],
            ['Tórshavn', 'Faroe Islands', 62, -7],
            ['Palikir', 'Micronesia', 6.91666666, 158.25],
            ['Libreville', 'Gabon', -1, 11.75],
            ['London', 'United Kingdom', 54, -2],
            ['Tbilisi', 'Georgia', 42, 43.5],
            ['St. Peter Port', 'Guernsey', 49.46666666, -2.58333333],
            ['Accra', 'Ghana', 8, -2],
            ['Gibraltar', 'Gibraltar', 36.13333333, -5.35],
            ['Conakry', 'Guinea', 11, -10],
            ['Basse-Terre', 'Guadeloupe', 16.25, -61.583333],
            ['Banjul', 'Gambia', 13.46666666, -16.56666666],
            ['Bissau', 'Guinea-Bissau', 12, -15],
            ['Malabo', 'Equatorial Guinea', 2, 10],
            ['Athens', 'Greece', 39, 22],
            ['St. George\'s', 'Grenada', 12.11666666, -61.66666666],
            ['Nuuk', 'Greenland', 72, -40],
            ['Guatemala City', 'Guatemala', 15.5, -90.25],
            ['Cayenne', 'French Guiana', 4, -53],
            ['Hagåtña', 'Guam', 13.46666666, 144.78333333],
            ['Georgetown', 'Guyana', 5, -59],
            ['City of Victoria', 'Hong Kong', 22.267, 114.188],
            ['Tegucigalpa', 'Honduras', 15, -86.5],
            ['Zagreb', 'Croatia', 45.16666666, 15.5],
            ['Port-au-Prince', 'Haiti', 19, -72.41666666],
            ['Budapest', 'Hungary', 47, 20],
            ['Jakarta', 'Indonesia', -5, 120],
            ['Douglas', 'Isle of Man', 54.25, -4.5],
            ['New Delhi', 'India', 20, 77],
            ['Diego Garcia', 'British Indian Ocean Territory', -6, 71.5],
            ['Dublin', 'Ireland', 53, -8],
            ['Tehran', 'Iran', 32, 53],
            ['Baghdad', 'Iraq', 33, 44],
            ['Reykjavik', 'Iceland', 65, -18],
            ['Jerusalem', 'Israel', 31.47, 35.13],
            ['Rome', 'Italy', 42.83333333, 12.83333333],
            ['Kingston', 'Jamaica', 18.25, -77.5],
            ['Saint Helier', 'Jersey', 49.25, -2.16666666],
            ['Amman', 'Jordan', 31, 36],
            ['Tokyo', 'Japan', 36, 138],
            ['Astana', 'Kazakhstan', 48, 68],
            ['Nairobi', 'Kenya', 1, 38],
            ['Bishkek', 'Kyrgyzstan', 41, 75],
            ['Phnom Penh', 'Cambodia', 13, 105],
            ['South Tarawa', 'Kiribati', 1.41666666, 173],
            ['Basseterre', 'Saint Kitts and Nevis', 17.33333333, -62.75],
            ['Seoul', 'South Korea', 37, 127.5],
            ['Pristina', 'Kosovo', 42.666667, 21.166667],
            ['Kuwait City', 'Kuwait', 29.5, 45.75],
            ['Vientiane', 'Laos', 18, 105],
            ['Beirut', 'Lebanon', 33.83333333, 35.83333333],
            ['Monrovia', 'Liberia', 6.5, -9.5],
            ['Tripoli', 'Libya', 25, 17],
            ['Castries', 'Saint Lucia', 13.88333333, -60.96666666],
            ['Vaduz', 'Liechtenstein', 47.26666666, 9.53333333],
            ['Colombo', 'Sri Lanka', 7, 81],
            ['Maseru', 'Lesotho', -29.5, 28.5],
            ['Vilnius', 'Lithuania', 56, 24],
            ['Luxembourg', 'Luxembourg', 49.75, 6.16666666],
            ['Riga', 'Latvia', 57, 25],
            ['Marigot', 'Saint Martin', 18.08333333, -63.95],
            ['Rabat', 'Morocco', 32, -5],
            ['Monaco', 'Monaco', 43.73333333, 7.4],
            ['Chișinău', 'Moldova', 47, 29],
            ['Antananarivo', 'Madagascar', -20, 47],
            ['Malé', 'Maldives', 3.25, 73],
            ['Mexico City', 'Mexico', 23, -102],
            ['Majuro', 'Marshall Islands', 9, 168],
            ['Skopje', 'North Macedonia', 41.83333333, 22],
            ['Bamako', 'Mali', 17, -4],
            ['Valletta', 'Malta', 35.83333333, 14.58333333],
            ['Naypyidaw', 'Myanmar', 22, 98],
            ['Podgorica', 'Montenegro', 42.5, 19.3],
            ['Ulan Bator', 'Mongolia', 46, 105],
            ['Saipan', 'Northern Mariana Islands', 15.2, 145.75],
            ['Maputo', 'Mozambique', -18.25, 35],
            ['Nouakchott', 'Mauritania', 20, -12],
            ['Plymouth', 'Montserrat', 16.75, -62.2],
            ['Fort-de-France', 'Martinique', 14.666667, -61],
            ['Port Louis', 'Mauritius', -20.28333333, 57.55],
            ['Lilongwe', 'Malawi', -13.5, 34],
            ['Kuala Lumpur', 'Malaysia', 2.5, 112.5],
            ['Mamoudzou', 'Mayotte', -12.83333333, 45.16666666],
            ['Windhoek', 'Namibia', -22, 17],
            ['Nouméa', 'New Caledonia', -21.5, 165.5],
            ['Niamey', 'Niger', 16, 8],
            ['Kingston', 'Norfolk Island', -29.03333333, 167.95],
            ['Abuja', 'Nigeria', 10, 8],
            ['Managua', 'Nicaragua', 13, -85],
            ['Alofi', 'Niue', -19.03333333, -169.86666666],
            ['Amsterdam', 'Netherlands', 52.5, 5.75],
            ['Oslo', 'Norway', 62, 10],
            ['Kathmandu', 'Nepal', 28, 84],
            ['Yaren', 'Nauru', -0.53333333, 166.91666666],
            ['Wellington', 'New Zealand', -41, 174],
            ['Muscat', 'Oman', 21, 57],
            ['Islamabad', 'Pakistan', 30, 70],
            ['Panama City', 'Panama', 9, -80],
            ['Adamstown', 'Pitcairn Islands', -25.06666666, -130.1],
            ['Lima', 'Peru', -10, -76],
            ['Manila', 'Philippines', 13, 122],
            ['Ngerulmud', 'Palau', 7.5, 134.5],
            ['Port Moresby', 'Papua New Guinea', -6, 147],
            ['Warsaw', 'Poland', 52, 20],
            ['San Juan', 'Puerto Rico', 18.25, -66.5],
            ['Pyongyang', 'North Korea', 40, 127],
            ['Lisbon', 'Portugal', 39.5, -8],
            ['Asunción', 'Paraguay', -23, -58],
            ['Ramallah', 'Palestine', 31.9, 35.2],
            ['Papeetē', 'French Polynesia', -15, -140],
            ['Doha', 'Qatar', 25.5, 51.25],
            ['Saint-Denis', 'Réunion', -21.15, 55.5],
            ['Bucharest', 'Romania', 46, 25],
            ['Moscow', 'Russia', 60, 100],
            ['Kigali', 'Rwanda', -2, 30],
            ['Riyadh', 'Saudi Arabia', 25, 45],
            ['Khartoum', 'Sudan', 15, 30],
            ['Dakar', 'Senegal', 14, -14],
            ['Singapore', 'Singapore', 1.36666666, 103.8],
            ['King Edward Point', 'South Georgia', -54.5, -37],
            ['Longyearbyen', 'Svalbard and Jan Mayen', 78, 20],
            ['Honiara', 'Solomon Islands', -8, 159],
            ['Freetown', 'Sierra Leone', 8.5, -11.5],
            ['San Salvador', 'El Salvador', 13.83333333, -88.91666666],
            ['City of San Marino', 'San Marino', 43.76666666, 12.41666666],
            ['Mogadishu', 'Somalia', 10, 49],
            ['Saint-Pierre', 'Saint Pierre and Miquelon', 46.83333333, -56.33333333],
            ['Belgrade', 'Serbia', 44, 21],
            ['Juba', 'South Sudan', 7, 30],
            ['São Tomé', 'São Tomé and Príncipe', 1, 7],
            ['Paramaribo', 'Suriname', 4, -56],
            ['Bratislava', 'Slovakia', 48.66666666, 19.5],
            ['Ljubljana', 'Slovenia', 46.11666666, 14.81666666],
            ['Stockholm', 'Sweden', 62, 15],
            ['Lobamba', 'Eswatini', -26.5, 31.5],
            ['Philipsburg', 'Sint Maarten', 18.033333, -63.05],
            ['Victoria', 'Seychelles', -4.58333333, 55.66666666],
            ['Damascus', 'Syria', 35, 38],
            ['Cockburn Town', 'Turks and Caicos Islands', 21.75, -71.58333333],
            ['N\'Djamena', 'Chad', 15, 19],
            ['Lomé', 'Togo', 8, 1.16666666],
            ['Bangkok', 'Thailand', 15, 100],
            ['Dushanbe', 'Tajikistan', 39, 71],
            ['Fakaofo', 'Tokelau', -9, -172],
            ['Ashgabat', 'Turkmenistan', 40, 60],
            ['Dili', 'Timor-Leste', -8.83333333, 125.91666666],
            ['Nuku\'alofa', 'Tonga', -20, -175],
            ['Port of Spain', 'Trinidad and Tobago', 11, -61],
            ['Tunis', 'Tunisia', 34, 9],
            ['Ankara', 'Türkiye', 39, 35],
            ['Funafuti', 'Tuvalu', -8, 178],
            ['Taipei', 'Taiwan', 23.5, 121],
            ['Dodoma', 'Tanzania', -6, 35],
            ['Kampala', 'Uganda', 1, 32],
            ['Kyiv', 'Ukraine', 49, 32],
            ['Montevideo', 'Uruguay', -33, -56],
            ['Washington D.C.', 'United States', 38, -97],
            ['Tashkent', 'Uzbekistan', 41, 64],
            ['Vatican City', 'Vatican City', 41.9, 12.45],
            ['Kingstown', 'Saint Vincent and the Grenadines', 13.25, -61.2],
            ['Caracas', 'Venezuela', 8, -66],
            ['Road Town', 'British Virgin Islands', 18.431383, -64.62305],
            ['Charlotte Amalie', 'United States Virgin Islands', 18.35, -64.933333],
            ['Hanoi', 'Vietnam', 16.16666666, 107.83333333],
            ['Port Vila', 'Vanuatu', -16, 167],
            ['Mata-Utu', 'Wallis and Futuna', -13.3, -176.2],
            ['Apia', 'Samoa', -13.58333333, -172.33333333],
            ['Sana\'a', 'Yemen', 15, 48],
            ['Pretoria', 'South Africa', -29, 24],
            ['Lusaka', 'Zambia', -15, 30],
            ['Harare', 'Zimbabwe', -20, 30],
        ];
    }
}
