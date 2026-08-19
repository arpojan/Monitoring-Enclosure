<?php

namespace App\Services;

use App\Models\Enclosure;

/**
 * AnimalKnowledgeBase
 *
 * Basis pengetahuan parameter lingkungan ideal untuk spesies reptil dan amfibi.
 * Digunakan oleh DssService untuk menghitung Stability Score dan menghasilkan
 * Insight Lingkungan serta Rekomendasi Parameter berbasis aturan (rule-based).
 *
 * Satuan:
 *   - Suhu         : °C (Celsius)
 *   - Kelembaban   : % (Relatif/RH)
 *
 * Struktur threshold per spesies:
 *   temp_min        → batas minimum absolut (bahaya dingin)
 *   temp_ideal_min  → batas bawah rentang ideal
 *   temp_ideal_max  → batas atas rentang ideal
 *   temp_max        → batas maksimum absolut (bahaya panas)
 *   humid_min       → batas minimum absolut (bahaya kering)
 *   humid_ideal_min → batas bawah kelembaban ideal
 *   humid_ideal_max → batas atas kelembaban ideal
 *   humid_max       → batas maksimum absolut (bahaya lembab berlebih)
 *
 * Sumber data:
 *   [1] Putra & Suwarno (2022) — Leopard Gecko (dewasa)
 *   [2] Pratama (2024)         — Leopard Gecko (bayi/juvenil)
 *   [3] Ningsih & Hutabri (2024) — Sulcata Tortoise
 *   [4] Zain (2025)            — Bearded Dragon
 *   [5] ExoticPetExpos Reptile Temperature & Humidity Chart (50+ species)
 *   [6] Merck Veterinary Manual — Housing for Amphibians
 *   [7] SensorPush Reptile & Amphibian Care Reference
 */
class AnimalKnowledgeBase
{
    /**
     * Mengembalikan seluruh data spesies yang didukung oleh sistem.
     *
     * @return array<string, array>
     */
    public static function getSpecies(): array
    {
        return array_merge(
            self::snakes(),
            self::lizards(),
            self::geckos(),
            self::tortoises(),
            self::frogs(),
        );
    }

    /**
     * Mengembalikan daftar kategori yang tersedia.
     *
     * @return array<string, string>
     */
    public static function getCategories(): array
    {
        return [
            'snake'    => 'Ular',
            'lizard'   => 'Kadal',
            'gecko'    => 'Gecko',
            'tortoise' => 'Kura-kura Darat',
            'frog'     => 'Katak & Kodok',
        ];
    }

    /**
     * Mengembalikan data satu spesies berdasarkan key.
     *
     * @param  string $key
     * @return array|null
     */
    public static function getSpeciesByKey(string $key): ?array
    {
        return self::getSpecies()[$key] ?? null;
    }

    /**
     * Mengembalikan seluruh spesies dalam kategori tertentu.
     *
     * @param  string $category
     * @return array<string, array>
     */
    public static function getSpeciesByCategory(string $category): array
    {
        return array_filter(
            self::getSpecies(),
            fn($s) => $s['category'] === $category
        );
    }

    // =========================================================================
    // ULAR (SNAKES)
    // =========================================================================

    private static function snakes(): array
    {
        return [
            'ball_python' => [
                'name'            => 'Ball Python',
                'scientific_name' => 'Python regius',
                'category'        => 'snake',
                'habitat_type'    => 'tropical',
                'temperature'     => [
                    'temp_min'        => 22,
                    'temp_ideal_min'  => 25,
                    'temp_ideal_max'  => 27,
                    'temp_max'        => 31,
                ],
                'humidity'        => [
                    'humid_min'       => 55,
                    'humid_ideal_min' => 60,
                    'humid_ideal_max' => 80,
                    'humid_max'       => 90,
                ],
                'notes' => 'Naikkan kelembaban hingga 80% saat proses shed. Gunakan under-tank heater atau radiant panel untuk sisi hangat (31–33°C).',
            ],

            'corn_snake' => [
                'name'            => 'Corn Snake',
                'scientific_name' => 'Pantherophis guttatus',
                'category'        => 'snake',
                'habitat_type'    => 'sub_tropical',
                'temperature'     => [
                    'temp_min'        => 18,
                    'temp_ideal_min'  => 24,
                    'temp_ideal_max'  => 28,
                    'temp_max'        => 32,
                ],
                'humidity'        => [
                    'humid_min'       => 30,
                    'humid_ideal_min' => 40,
                    'humid_ideal_max' => 60,
                    'humid_max'       => 70,
                ],
                'notes' => 'Sangat toleran terhadap variasi suhu. Hindari kelembaban berlebih — rentan infeksi saluran pernapasan.',
            ],

            'boa_constrictor' => [
                'name'            => 'Boa Constrictor',
                'scientific_name' => 'Boa constrictor',
                'category'        => 'snake',
                'habitat_type'    => 'tropical',
                'temperature'     => [
                    'temp_min'        => 22,
                    'temp_ideal_min'  => 25,
                    'temp_ideal_max'  => 29,
                    'temp_max'        => 33,
                ],
                'humidity'        => [
                    'humid_min'       => 50,
                    'humid_ideal_min' => 60,
                    'humid_ideal_max' => 75,
                    'humid_max'       => 85,
                ],
                'notes' => 'Boa Hog Island lebih menyukai kondisi kering (50–60%). Sesuaikan dengan lokalitas asal hewan.',
            ],

            'green_tree_python' => [
                'name'            => 'Green Tree Python',
                'scientific_name' => 'Morelia viridis',
                'category'        => 'snake',
                'habitat_type'    => 'tropical',
                'temperature'     => [
                    'temp_min'        => 22,
                    'temp_ideal_min'  => 27,
                    'temp_ideal_max'  => 29,
                    'temp_max'        => 32,
                ],
                'humidity'        => [
                    'humid_min'       => 60,
                    'humid_ideal_min' => 70,
                    'humid_ideal_max' => 90,
                    'humid_max'       => 100,
                ],
                'notes' => 'Arboreal — sediakan ranting untuk bertengger. Lakukan misting malam hari. Kelembaban tinggi sangat penting.',
            ],

            'blood_python' => [
                'name'            => 'Blood Python',
                'scientific_name' => 'Python brongersmai',
                'category'        => 'snake',
                'habitat_type'    => 'tropical',
                'temperature'     => [
                    'temp_min'        => 22,
                    'temp_ideal_min'  => 27,
                    'temp_ideal_max'  => 29,
                    'temp_max'        => 32,
                ],
                'humidity'        => [
                    'humid_min'       => 70,
                    'humid_ideal_min' => 80,
                    'humid_ideal_max' => 90,
                    'humid_max'       => 100,
                ],
                'notes' => 'Kelembaban sangat tinggi mutlak diperlukan. Substrat berbasis gambut (peat) bekerja baik. Hindari suhu di atas 32°C.',
            ],

            'reticulated_python' => [
                'name'            => 'Reticulated Python',
                'scientific_name' => 'Malayopython reticulatus',
                'category'        => 'snake',
                'habitat_type'    => 'tropical',
                'temperature'     => [
                    'temp_min'        => 22,
                    'temp_ideal_min'  => 27,
                    'temp_ideal_max'  => 30,
                    'temp_max'        => 33,
                ],
                'humidity'        => [
                    'humid_min'       => 50,
                    'humid_ideal_min' => 60,
                    'humid_ideal_max' => 80,
                    'humid_max'       => 90,
                ],
                'notes' => 'Ular terpanjang di dunia. Enklosur yang aman dan kokoh sangat penting. Kecerdasan tinggi.',
            ],

            'kenyan_sand_boa' => [
                'name'            => 'Kenyan Sand Boa',
                'scientific_name' => 'Eryx colubrinus',
                'category'        => 'snake',
                'habitat_type'    => 'desert',
                'temperature'     => [
                    'temp_min'        => 18,
                    'temp_ideal_min'  => 24,
                    'temp_ideal_max'  => 29,
                    'temp_max'        => 35,
                ],
                'humidity'        => [
                    'humid_min'       => 15,
                    'humid_ideal_min' => 25,
                    'humid_ideal_max' => 40,
                    'humid_max'       => 50,
                ],
                'notes' => 'Spesies gurun yang suka menggali. Substrat pasir kering yang dalam sangat penting. Kelembaban rendah mencegah infeksi pernapasan.',
            ],

            'brazilian_rainbow_boa' => [
                'name'            => 'Brazilian Rainbow Boa',
                'scientific_name' => 'Epicrates cenchria',
                'category'        => 'snake',
                'habitat_type'    => 'tropical',
                'temperature'     => [
                    'temp_min'        => 22,
                    'temp_ideal_min'  => 25,
                    'temp_ideal_max'  => 29,
                    'temp_max'        => 32,
                ],
                'humidity'        => [
                    'humid_min'       => 80,
                    'humid_ideal_min' => 85,
                    'humid_ideal_max' => 100,
                    'humid_max'       => 100,
                ],
                'notes' => 'Salah satu ular dengan kebutuhan kelembaban tertinggi di antara ular peliharaan umum. Sisik iridescent tampak indah di kondisi optimal.',
            ],
        ];
    }

    // =========================================================================
    // KADAL (LIZARDS)
    // =========================================================================

    private static function lizards(): array
    {
        return [
            'bearded_dragon' => [
                'name'            => 'Bearded Dragon',
                'scientific_name' => 'Pogona vitticeps',
                'category'        => 'lizard',
                'habitat_type'    => 'desert',
                'temperature'     => [
                    'temp_min'        => 18,
                    'temp_ideal_min'  => 27,
                    'temp_ideal_max'  => 29,
                    'temp_max'        => 38,
                ],
                'humidity'        => [
                    'humid_min'       => 10,
                    'humid_ideal_min' => 20,
                    'humid_ideal_max' => 40,
                    'humid_max'       => 50,
                ],
                'notes' => 'Zona basking 38–43°C di permukaan. UVB wajib. Kelembaban tinggi memicu infeksi saluran pernapasan. [Sumber: Zain, 2025]',
            ],

            'veiled_chameleon' => [
                'name'            => 'Veiled Chameleon',
                'scientific_name' => 'Chamaeleo calyptratus',
                'category'        => 'lizard',
                'habitat_type'    => 'semi_arid',
                'temperature'     => [
                    'temp_min'        => 15,
                    'temp_ideal_min'  => 22,
                    'temp_ideal_max'  => 27,
                    'temp_max'        => 32,
                ],
                'humidity'        => [
                    'humid_min'       => 40,
                    'humid_ideal_min' => 50,
                    'humid_ideal_max' => 70,
                    'humid_max'       => 80,
                ],
                'notes' => 'Enklosur screen wajib untuk ventilasi. Sistem drip atau misting untuk sumber air minum. Sangat sensitif terhadap stres.',
            ],

            'panther_chameleon' => [
                'name'            => 'Panther Chameleon',
                'scientific_name' => 'Furcifer pardalis',
                'category'        => 'lizard',
                'habitat_type'    => 'tropical',
                'temperature'     => [
                    'temp_min'        => 18,
                    'temp_ideal_min'  => 24,
                    'temp_ideal_max'  => 28,
                    'temp_max'        => 32,
                ],
                'humidity'        => [
                    'humid_min'       => 50,
                    'humid_ideal_min' => 60,
                    'humid_ideal_max' => 75,
                    'humid_max'       => 85,
                ],
                'notes' => 'Enklosur screen wajib. Ventilasi baik mencegah infeksi pernapasan. Warna bervariasi berdasarkan lokalitas asal.',
            ],

            'chinese_water_dragon' => [
                'name'            => 'Chinese Water Dragon',
                'scientific_name' => 'Physignathus cocincinus',
                'category'        => 'lizard',
                'habitat_type'    => 'tropical',
                'temperature'     => [
                    'temp_min'        => 22,
                    'temp_ideal_min'  => 27,
                    'temp_ideal_max'  => 31,
                    'temp_max'        => 35,
                ],
                'humidity'        => [
                    'humid_min'       => 60,
                    'humid_ideal_min' => 70,
                    'humid_ideal_max' => 80,
                    'humid_max'       => 90,
                ],
                'notes' => 'Semi-arboreal dan semi-akuatik. Perlu fitur air (kolam kecil) dan ranting panjat. Enklosur tinggi diperlukan.',
            ],

            'green_iguana' => [
                'name'            => 'Green Iguana',
                'scientific_name' => 'Iguana iguana',
                'category'        => 'lizard',
                'habitat_type'    => 'tropical',
                'temperature'     => [
                    'temp_min'        => 24,
                    'temp_ideal_min'  => 29,
                    'temp_ideal_max'  => 35,
                    'temp_max'        => 40,
                ],
                'humidity'        => [
                    'humid_min'       => 55,
                    'humid_ideal_min' => 65,
                    'humid_ideal_max' => 75,
                    'humid_max'       => 85,
                ],
                'notes' => 'Arboreal — enklosur sangat tinggi dengan ranting panjat. UVB wajib. Bisa mencapai panjang 1,5 m. Perawatan tingkat lanjut.',
            ],

            'frilled_dragon' => [
                'name'            => 'Frilled Dragon',
                'scientific_name' => 'Chlamydosaurus kingii',
                'category'        => 'lizard',
                'habitat_type'    => 'tropical',
                'temperature'     => [
                    'temp_min'        => 22,
                    'temp_ideal_min'  => 27,
                    'temp_ideal_max'  => 32,
                    'temp_max'        => 36,
                ],
                'humidity'        => [
                    'humid_min'       => 50,
                    'humid_ideal_min' => 60,
                    'humid_ideal_max' => 80,
                    'humid_max'       => 90,
                ],
                'notes' => 'Enklosur tinggi untuk aktivitas arboreal. Frill/kolar mengembang saat terancam. Aktif berjemur.',
            ],
        ];
    }

    // =========================================================================
    // GECKO
    // =========================================================================

    private static function geckos(): array
    {
        return [
            'leopard_gecko_adult' => [
                'name'            => 'Leopard Gecko (Dewasa)',
                'scientific_name' => 'Eublepharis macularius',
                'category'        => 'gecko',
                'habitat_type'    => 'semi_arid',
                'temperature'     => [
                    'temp_min'        => 18,
                    'temp_ideal_min'  => 27,
                    'temp_ideal_max'  => 33,
                    'temp_max'        => 36,
                ],
                'humidity'        => [
                    'humid_min'       => 15,
                    'humid_ideal_min' => 20,
                    'humid_ideal_max' => 40,
                    'humid_max'       => 50,
                ],
                'notes' => 'Under-tank heater lebih disarankan daripada heat lamp. Sediakan moist hide permanen untuk shed. [Sumber: Putra & Suwarno, 2022]',
            ],

            'leopard_gecko_juvenile' => [
                'name'            => 'Leopard Gecko (Bayi/Juvenil)',
                'scientific_name' => 'Eublepharis macularius',
                'category'        => 'gecko',
                'habitat_type'    => 'semi_arid',
                'temperature'     => [
                    'temp_min'        => 20,
                    'temp_ideal_min'  => 30,
                    'temp_ideal_max'  => 33,
                    'temp_max'        => 35,
                ],
                'humidity'        => [
                    'humid_min'       => 40,
                    'humid_ideal_min' => 50,
                    'humid_ideal_max' => 70,
                    'humid_max'       => 75,
                ],
                'notes' => 'Bayi gecko sangat sensitif terhadap stres. Sebagian substrat alas perlu dijaga tetap lembab untuk kemudahan ganti kulit. [Sumber: Pratama, 2024]',
            ],

            'crested_gecko' => [
                'name'            => 'Crested Gecko',
                'scientific_name' => 'Correlophus ciliatus',
                'category'        => 'gecko',
                'habitat_type'    => 'tropical',
                'temperature'     => [
                    'temp_min'        => 18,
                    'temp_ideal_min'  => 22,
                    'temp_ideal_max'  => 26,
                    'temp_max'        => 29,
                ],
                'humidity'        => [
                    'humid_min'       => 50,
                    'humid_ideal_min' => 60,
                    'humid_ideal_max' => 80,
                    'humid_max'       => 90,
                ],
                'notes' => 'JANGAN melebihi 29°C — risiko heat stress tinggi. Arboreal, enklosur tinggi. Lakukan misting malam hari. Biasanya tidak perlu pemanas tambahan.',
            ],

            'tokay_gecko' => [
                'name'            => 'Tokay Gecko',
                'scientific_name' => 'Gekko gecko',
                'category'        => 'gecko',
                'habitat_type'    => 'tropical',
                'temperature'     => [
                    'temp_min'        => 22,
                    'temp_ideal_min'  => 27,
                    'temp_ideal_max'  => 31,
                    'temp_max'        => 35,
                ],
                'humidity'        => [
                    'humid_min'       => 60,
                    'humid_ideal_min' => 70,
                    'humid_ideal_max' => 90,
                    'humid_max'       => 95,
                ],
                'notes' => 'Telur cangkang keras menempel di permukaan. Temperamen agresif — tangani dengan hati-hati. Vokalisasi keras adalah perilaku normal.',
            ],
        ];
    }

    // =========================================================================
    // KURA-KURA DARAT (TORTOISES)
    // =========================================================================

    private static function tortoises(): array
    {
        return [
            'sulcata_tortoise' => [
                'name'            => 'Sulcata Tortoise',
                'scientific_name' => 'Centrochelys sulcata',
                'category'        => 'tortoise',
                'habitat_type'    => 'desert',
                'temperature'     => [
                    'temp_min'        => 18,
                    'temp_ideal_min'  => 31,
                    'temp_ideal_max'  => 35,
                    'temp_max'        => 40,
                ],
                'humidity'        => [
                    'humid_min'       => 10,
                    'humid_ideal_min' => 20,
                    'humid_ideal_max' => 40,
                    'humid_max'       => 50,
                ],
                'notes' => 'Kura-kura darat terbesar ketiga. Asal Sahara — jaga tetap kering. Suhu di atas 35°C menyebabkan dehidrasi. [Sumber: Ningsih & Hutabri, 2024]',
            ],

            'red_footed_tortoise' => [
                'name'            => 'Red-footed Tortoise',
                'scientific_name' => 'Chelonoidis carbonarius',
                'category'        => 'tortoise',
                'habitat_type'    => 'tropical',
                'temperature'     => [
                    'temp_min'        => 21,
                    'temp_ideal_min'  => 27,
                    'temp_ideal_max'  => 31,
                    'temp_max'        => 35,
                ],
                'humidity'        => [
                    'humid_min'       => 60,
                    'humid_ideal_min' => 70,
                    'humid_ideal_max' => 85,
                    'humid_max'       => 95,
                ],
                'notes' => 'Spesies hutan tropis — butuh kelembaban lebih tinggi dibanding kura-kura mediterania. Tidak hibernasi. Omnivora.',
            ],

            'hermanns_tortoise' => [
                'name'            => "Hermann's Tortoise",
                'scientific_name' => 'Testudo hermanni',
                'category'        => 'tortoise',
                'habitat_type'    => 'semi_arid',
                'temperature'     => [
                    'temp_min'        => 15,
                    'temp_ideal_min'  => 24,
                    'temp_ideal_max'  => 29,
                    'temp_max'        => 35,
                ],
                'humidity'        => [
                    'humid_min'       => 30,
                    'humid_ideal_min' => 40,
                    'humid_ideal_max' => 60,
                    'humid_max'       => 70,
                ],
                'notes' => 'Spesies mediterania. Bisa hibernasi. UVB wajib. Sangat populer di Eropa sebagai hewan peliharaan.',
            ],

            'indian_star_tortoise' => [
                'name'            => 'Indian Star Tortoise',
                'scientific_name' => 'Geochelone elegans',
                'category'        => 'tortoise',
                'habitat_type'    => 'semi_arid',
                'temperature'     => [
                    'temp_min'        => 21,
                    'temp_ideal_min'  => 27,
                    'temp_ideal_max'  => 32,
                    'temp_max'        => 36,
                ],
                'humidity'        => [
                    'humid_min'       => 50,
                    'humid_ideal_min' => 60,
                    'humid_ideal_max' => 80,
                    'humid_max'       => 90,
                ],
                'notes' => 'Spesies iklim monsun — butuh variasi kelembaban musiman. Terdaftar dalam CITES. Pola bintang pada tempurung sangat khas.',
            ],
        ];
    }

    // =========================================================================
    // KATAK & KODOK (FROGS & TOADS)
    // =========================================================================

    private static function frogs(): array
    {
        return [
            'poison_dart_frog' => [
                'name'            => 'Poison Dart Frog',
                'scientific_name' => 'Dendrobatidae spp.',
                'category'        => 'frog',
                'habitat_type'    => 'tropical_rainforest',
                'temperature'     => [
                    'temp_min'        => 18,
                    'temp_ideal_min'  => 22,
                    'temp_ideal_max'  => 27,
                    'temp_max'        => 28,
                ],
                'humidity'        => [
                    'humid_min'       => 70,
                    'humid_ideal_min' => 80,
                    'humid_ideal_max' => 100,
                    'humid_max'       => 100,
                ],
                'notes' => 'JANGAN melebihi 28°C — heat spike adalah penyebab kematian utama. Katak tropis-dingin, bukan spesies panas. Kelembaban tinggi dengan siklus naik-turun alami.',
            ],

            'red_eyed_tree_frog' => [
                'name'            => 'Red-eyed Tree Frog',
                'scientific_name' => 'Agalychnis callidryas',
                'category'        => 'frog',
                'habitat_type'    => 'tropical_rainforest',
                'temperature'     => [
                    'temp_min'        => 18,
                    'temp_ideal_min'  => 24,
                    'temp_ideal_max'  => 29,
                    'temp_max'        => 32,
                ],
                'humidity'        => [
                    'humid_min'       => 70,
                    'humid_ideal_min' => 80,
                    'humid_ideal_max' => 100,
                    'humid_max'       => 100,
                ],
                'notes' => 'Arboreal dari hutan hujan tropis. Enklosur berventilasi dengan ruang panjat vertikal. Nocturnal — aktif malam hari.',
            ],

            'whites_tree_frog' => [
                'name'            => "White's Tree Frog",
                'scientific_name' => 'Litoria caerulea',
                'category'        => 'frog',
                'habitat_type'    => 'tropical',
                'temperature'     => [
                    'temp_min'        => 18,
                    'temp_ideal_min'  => 22,
                    'temp_ideal_max'  => 27,
                    'temp_max'        => 31,
                ],
                'humidity'        => [
                    'humid_min'       => 40,
                    'humid_ideal_min' => 50,
                    'humid_ideal_max' => 80,
                    'humid_max'       => 90,
                ],
                'notes' => 'Salah satu katak pohon paling toleran dan mudah dipelihara. Cocok untuk pemula. Ukuran lebih besar dibanding tree frog lain.',
            ],

            'american_green_tree_frog' => [
                'name'            => 'American Green Tree Frog',
                'scientific_name' => 'Hyla cinerea',
                'category'        => 'frog',
                'habitat_type'    => 'sub_tropical',
                'temperature'     => [
                    'temp_min'        => 18,
                    'temp_ideal_min'  => 22,
                    'temp_ideal_max'  => 27,
                    'temp_max'        => 30,
                ],
                'humidity'        => [
                    'humid_min'       => 40,
                    'humid_ideal_min' => 50,
                    'humid_ideal_max' => 70,
                    'humid_max'       => 80,
                ],
                'notes' => 'Malam: 18–21°C. Siang: 22–27°C. Enklosur tinggi dengan tutup berventilasi. Aktif dan atraktif sebagai display pet.',
            ],

            'pacman_frog' => [
                'name'            => 'Pacman Frog',
                'scientific_name' => 'Ceratophrys spp.',
                'category'        => 'frog',
                'habitat_type'    => 'sub_tropical',
                'temperature'     => [
                    'temp_min'        => 18,
                    'temp_ideal_min'  => 23,
                    'temp_ideal_max'  => 26,
                    'temp_max'        => 30,
                ],
                'humidity'        => [
                    'humid_min'       => 60,
                    'humid_ideal_min' => 70,
                    'humid_ideal_max' => 80,
                    'humid_max'       => 90,
                ],
                'notes' => 'Katak terestrial yang hampir tidak bergerak. Sebagian besar waktu dihabiskan dengan membenamkan diri di substrat. Bisa memangsa hewan lebih besar dari kepalanya.',
            ],

            'tomato_frog' => [
                'name'            => 'Tomato Frog',
                'scientific_name' => 'Dyscophus spp.',
                'category'        => 'frog',
                'habitat_type'    => 'tropical',
                'temperature'     => [
                    'temp_min'        => 18,
                    'temp_ideal_min'  => 23,
                    'temp_ideal_max'  => 28,
                    'temp_max'        => 32,
                ],
                'humidity'        => [
                    'humid_min'       => 60,
                    'humid_ideal_min' => 70,
                    'humid_ideal_max' => 90,
                    'humid_max'       => 95,
                ],
                'notes' => 'Asal Madagaskar. Warna merah-oranye cerah sebagai peringatan predator. Terestrial dan suka menggali. Relatif mudah dipelihara.',
            ],
        ];
    }

    /**
     * Mengevaluasi kondisi kelembaban saat ini berdasarkan jenis hewan di kandang.
     * Mengembalikan status, durasi misting yang disarankan, dan insight DSS.
     */
    public static function evaluateHumidity(Enclosure $enclosure, float $currentHumidity): ?array
    {
        if (empty($enclosure->jenis_hewan)) {
            return null; // Tidak ada jenis hewan
        }

        $config = self::getSpeciesByKey($enclosure->jenis_hewan);
        if (!$config) {
            return null; // Hewan tidak dikenali
        }

        $min = $config['humidity']['humid_ideal_min'];
        $max = $config['humidity']['humid_ideal_max'];
        $habitat = $config['habitat_type'];
        $name = $config['name'];

        $status = 'Stable';
        $mistingDuration = null;
        $insight = "Kelembaban {$currentHumidity}% sangat ideal untuk {$name}.";

        if ($currentHumidity < $min) {
            // Terlalu Kering
            $diff = $min - $currentHumidity;
            if ($diff <= 5) {
                $status = 'Warning';
                $insight = "Kelembaban {$currentHumidity}% mendekati batas bawah ideal ({$min}%) untuk {$name}.";
                $mistingDuration = 10;
            } else {
                $status = 'Danger';
                $insight = "Kelembaban {$currentHumidity}% terlalu kering untuk {$name}, berisiko dehidrasi ekstrem.";
                $mistingDuration = (str_contains($habitat, 'tropical') || $config['category'] === 'frog') ? 20 : 10;
            }
        } elseif ($currentHumidity > $max) {
            // Terlalu Lembab
            $diff = $currentHumidity - $max;
            if ($diff <= 5) {
                $status = 'Warning';
                $insight = "Kelembaban {$currentHumidity}% mendekati batas atas ideal ({$max}%) untuk {$name}.";
                $mistingDuration = 0; // Matikan misting
            } else {
                $status = 'Danger';
                $insight = "Kelembaban {$currentHumidity}% terlalu tinggi untuk {$name}, berisiko infeksi pernapasan. Misting dimatikan.";
                $mistingDuration = 0; // Matikan misting secara paksa
            }
        }

        return [
            'status' => $status,
            'recommendation_misting_duration' => $mistingDuration,
            'ai_insight' => $insight, // Keep this key as ai_insight to not break DssService.php array access
        ];
    }
}
