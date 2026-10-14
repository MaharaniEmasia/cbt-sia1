<?php

namespace Database\Seeders;

use App\Models\Answer;
use App\Models\Question;
use App\Models\Subject;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class QuestionAnswerSeeder extends Seeder
{
    private const TOTAL_PER_SUBJECT = 150;

    public function run(): void
    {
        mt_srand(2026); // agar hasil acak konsisten setiap kali seeder dijalankan

        $map = [
            'IPS'         => 'ipsQuestions',
            'Sejarah'     => 'sejarahQuestions',
            'Matematika'  => 'matematikaQuestions',
        ];

        foreach ($map as $subjectName => $method) {
            $subject = Subject::where('name', $subjectName)->first();

            if (! $subject) {
                $this->command?->warn("Mata pelajaran '{$subjectName}' tidak ditemukan, dilewati.");

                continue;
            }

            $pool = self::{$method}();
            // Ambil tepat 150 soal untuk setiap mata pelajaran.
            $pool = array_slice($pool, 0, self::TOTAL_PER_SUBJECT);

            if (count($pool) !== self::TOTAL_PER_SUBJECT) {
                $this->command?->error("{$subjectName}: hanya tersedia " . count($pool) . " soal; dibutuhkan tepat " . self::TOTAL_PER_SUBJECT . ".");
                continue;
            }

            DB::transaction(function () use ($subject, $pool, $subjectName) {
                // Bersihkan soal lama milik pelajaran ini (jawabannya ikut terhapus via cascadeOnDelete)
                Question::withTrashed()->where('subject_id', $subject->id)->forceDelete();

                foreach ($pool as $item) {
                    $question = Question::create([
                        'subject_id'  => $subject->id,
                        'payload'     => $item['question'],
                        'score'       => 1,
                        'description' => null,
                        'is_active'   => true,
                    ]);

                    foreach ($item['options'] as $option) {
                        Answer::create([
                            'question_id' => $question->id,
                            'text'        => $option['text'],
                            'is_correct'  => $option['correct'],
                            'is_active'   => true,
                        ]);
                    }
                }

                $this->command?->info("{$subjectName}: " . count($pool) . " soal berhasil dibuat.");
            });
        }
    }

    private static function buildMcq(string $question, string $correct, array $wrongs): array
    {
        $options = [['text' => $correct, 'correct' => true]];

        foreach ($wrongs as $w) {
            $options[] = ['text' => $w, 'correct' => false];
        }

        shuffle($options);

        return ['question' => $question, 'options' => $options];
    }

    private static function pickDistractors(array $pairs, int|string $excludeKey, int $n, bool $wantValue, array $fallbackPool = []): array
    {
        $excludeValue = $wantValue ? ($pairs[$excludeKey] ?? '') : $excludeKey;
        $excludeNormalized = mb_strtolower(trim((string) $excludeValue));

        $seen = [$excludeNormalized => true];
        $result = [];

        $candidates = $pairs;
        unset($candidates[$excludeKey]);
        $keys = array_keys($candidates);
        shuffle($keys);

        foreach ($keys as $k) {
            $val = $wantValue ? (string) $candidates[$k] : (string) $k;
            $norm = mb_strtolower(trim($val));
            if (isset($seen[$norm])) {
                continue;
            }
            $seen[$norm] = true;
            $result[] = $val;
            if (count($result) >= $n) {
                break;
            }
        }

        if (count($result) < $n && ! empty($fallbackPool)) {
            $extra = $fallbackPool;
            shuffle($extra);
            foreach ($extra as $val) {
                $norm = mb_strtolower(trim($val));
                if (isset($seen[$norm])) {
                    continue;
                }
                $seen[$norm] = true;
                $result[] = $val;
                if (count($result) >= $n) {
                    break;
                }
            }
        }

        return $result;
    }

    private static function buildLookupQuestions(array $pairs, string $askValue, ?string $askKey = null, int $distractorCount = 3, array $valueFallbackPool = []): array
    {
        $items = [];

        foreach ($pairs as $key => $value) {
            $wrongValues = self::pickDistractors($pairs, $key, $distractorCount, true, $valueFallbackPool);
            if (count($wrongValues) < $distractorCount) {
                continue;
            }
            $items[] = self::buildMcq(sprintf($askValue, $key), (string) $value, $wrongValues);

            if ($askKey !== null) {
                $wrongKeys = self::pickDistractors($pairs, $key, $distractorCount, false);
                if (count($wrongKeys) < $distractorCount) {
                    continue;
                }
                $items[] = self::buildMcq(sprintf($askKey, $value), (string) $key, $wrongKeys);
            }
        }

        return $items;
    }

    private static function numericDistractors(int|float $correct, int $minDelta = 1, int $maxDeltaPercent = 30): array
    {
        $wrongs = [];
        $attempts = 0;

        while (count($wrongs) < 3 && $attempts < 50) {
            $attempts++;
            $deltaBase = max($minDelta, (int) round(abs($correct) * mt_rand(5, $maxDeltaPercent) / 100));
            $delta = mt_rand(1, max(1, $deltaBase)) * (mt_rand(0, 1) ? 1 : -1);
            $w = $correct + $delta;

            if ($w == $correct || $w < 0) {
                continue;
            }

            $wStr = (string) (is_float($correct) ? round($w, 2) : (int) $w);

            if (! in_array($wStr, $wrongs, true)) {
                $wrongs[] = $wStr;
            }
        }

        $i = 1;
        while (count($wrongs) < 3) {
            $w = (string) ($correct + $i);
            if ($w !== (string) $correct && ! in_array($w, $wrongs, true)) {
                $wrongs[] = $w;
            }
            $i++;
        }

        return $wrongs;
    }

    private static function gcd(int $a, int $b): int
    {
        return $b === 0 ? max($a, 1) : self::gcd($b, $a % $b);
    }

    // IPS: 50 fakta x 3 variasi pertanyaan = 150 soal.
    private static function ipsQuestions(): array
    {
        $facts = [
            'Peta' => 'gambaran permukaan bumi pada bidang datar dengan skala tertentu',
            'Atlas' => 'kumpulan peta yang dibukukan',
            'Globe' => 'model bumi berbentuk bola',
            'Skala peta' => 'perbandingan jarak pada peta dengan jarak sebenarnya',
            'Legenda peta' => 'keterangan simbol-simbol yang digunakan pada peta',
            'Garis lintang' => 'garis khayal yang sejajar dengan khatulistiwa',
            'Garis bujur' => 'garis khayal yang menghubungkan Kutub Utara dan Kutub Selatan',
            'Khatulistiwa' => 'garis lintang 0 derajat yang membagi bumi menjadi belahan utara dan selatan',
            'Iklim' => 'rata-rata keadaan cuaca dalam waktu yang panjang',
            'Cuaca' => 'keadaan udara pada tempat dan waktu tertentu',
            'Erosi' => 'pengikisan tanah oleh air, angin, atau es',
            'Sedimentasi' => 'proses pengendapan material hasil erosi',
            'Reboisasi' => 'penanaman kembali hutan yang gundul',
            'Urbanisasi' => 'perpindahan penduduk dari desa ke kota',
            'Transmigrasi' => 'perpindahan penduduk dari daerah padat ke daerah yang lebih jarang penduduknya',
            'Imigrasi' => 'masuknya penduduk dari negara lain ke suatu negara',
            'Emigrasi' => 'keluarnya penduduk dari suatu negara untuk menetap di negara lain',
            'Sensus penduduk' => 'pencatatan seluruh penduduk pada waktu tertentu',
            'Natalitas' => 'angka kelahiran dalam suatu wilayah dan periode tertentu',
            'Mortalitas' => 'angka kematian dalam suatu wilayah dan periode tertentu',
            'Kepadatan penduduk' => 'perbandingan jumlah penduduk dengan luas wilayah',
            'Kebutuhan primer' => 'kebutuhan pokok seperti pangan, sandang, dan papan',
            'Kebutuhan sekunder' => 'kebutuhan pelengkap setelah kebutuhan primer terpenuhi',
            'Kebutuhan tersier' => 'kebutuhan yang berkaitan dengan kemewahan',
            'Produksi' => 'kegiatan menghasilkan atau menambah nilai guna barang dan jasa',
            'Distribusi' => 'kegiatan menyalurkan barang atau jasa dari produsen kepada konsumen',
            'Konsumsi' => 'kegiatan menggunakan barang atau jasa untuk memenuhi kebutuhan',
            'Produsen' => 'pihak yang menghasilkan barang atau jasa',
            'Konsumen' => 'pihak yang menggunakan barang atau jasa',
            'Pasar' => 'tempat atau mekanisme bertemunya penjual dan pembeli',
            'Permintaan' => 'jumlah barang atau jasa yang ingin dan mampu dibeli pada berbagai tingkat harga',
            'Penawaran' => 'jumlah barang atau jasa yang ingin dan mampu dijual pada berbagai tingkat harga',
            'Inflasi' => 'kenaikan harga barang dan jasa secara umum dan terus-menerus',
            'Koperasi' => 'badan usaha yang beranggotakan orang atau badan hukum dengan asas kekeluargaan',
            'Pajak' => 'kontribusi wajib kepada negara berdasarkan undang-undang tanpa imbalan langsung',
            'Bank' => 'lembaga yang menghimpun dana masyarakat dan menyalurkannya kembali',
            'Uang' => 'alat tukar yang diterima secara umum',
            'Ekspor' => 'kegiatan menjual barang atau jasa ke luar negeri',
            'Impor' => 'kegiatan membeli barang atau jasa dari luar negeri',
            'ASEAN' => 'organisasi kerja sama negara-negara di kawasan Asia Tenggara',
            'Globalisasi' => 'proses meningkatnya keterhubungan antarnegara dan masyarakat dunia',
            'Interaksi sosial' => 'hubungan timbal balik antara individu atau kelompok',
            'Sosialisasi' => 'proses mempelajari nilai dan norma yang berlaku dalam masyarakat',
            'Norma sosial' => 'aturan atau pedoman perilaku dalam kehidupan masyarakat',
            'Lembaga sosial' => 'sistem norma untuk memenuhi kebutuhan pokok masyarakat',
            'Mobilitas sosial' => 'perpindahan posisi atau status seseorang maupun kelompok dalam masyarakat',
            'Konflik sosial' => 'pertentangan antarindividu atau kelompok karena perbedaan kepentingan',
            'Akomodasi' => 'upaya meredakan atau menyelesaikan pertentangan sosial',
            'Akulturasi' => 'percampuran dua kebudayaan tanpa menghilangkan unsur asli sepenuhnya',
            'Asimilasi' => 'pembauran kebudayaan yang menghasilkan kebudayaan baru dan mengurangi perbedaan',
        ];

        return self::buildThreeFormFactQuestions($facts, 'IPS');
    }

    // Sejarah: 50 fakta x 3 variasi pertanyaan = 150 soal.
    private static function sejarahQuestions(): array
    {
        $facts = [
            'Kerajaan Kutai' => 'kerajaan bercorak Hindu yang berkembang di Kalimantan Timur',
            'Yupa' => 'prasasti berbentuk tiang batu peninggalan Kerajaan Kutai',
            'Raja Mulawarman' => 'raja Kutai yang dikenal dalam prasasti Yupa',
            'Kerajaan Tarumanegara' => 'kerajaan Hindu di wilayah Jawa Barat yang berkaitan dengan Raja Purnawarman',
            'Prasasti Ciaruteun' => 'prasasti Tarumanegara yang memuat telapak kaki Raja Purnawarman',
            'Kerajaan Sriwijaya' => 'kerajaan maritim bercorak Buddha yang berpusat di Sumatra',
            'Kerajaan Majapahit' => 'kerajaan besar di Jawa Timur yang mencapai puncak kejayaan pada masa Hayam Wuruk',
            'Gajah Mada' => 'mahapatih Majapahit yang terkenal dengan Sumpah Palapa',
            'Sumpah Palapa' => 'sumpah Gajah Mada untuk menyatukan wilayah Nusantara',
            'Borobudur' => 'candi Buddha yang dibangun di Jawa Tengah',
            'Prambanan' => 'kompleks candi Hindu yang berada di wilayah Yogyakarta dan Jawa Tengah',
            'Wali Songo' => 'sebutan bagi sembilan tokoh yang berperan dalam penyebaran Islam di Jawa',
            'Samudra Pasai' => 'kesultanan Islam awal yang berkembang di wilayah Aceh',
            'Kesultanan Demak' => 'kerajaan Islam awal yang berkembang di pesisir utara Jawa',
            'VOC' => 'kongsi dagang Belanda yang didirikan pada 1602',
            'Tanam Paksa' => 'kebijakan kolonial yang mewajibkan penanaman tanaman ekspor pada masa Hindia Belanda',
            'Perang Diponegoro' => 'perang besar di Jawa yang berlangsung pada 1825 hingga 1830',
            'Pattimura' => 'tokoh perlawanan terhadap Belanda di Maluku pada 1817',
            'Tuanku Imam Bonjol' => 'tokoh penting dalam Perang Padri di Sumatra Barat',
            'Cut Nyak Dhien' => 'pahlawan perempuan yang memimpin perlawanan terhadap Belanda di Aceh',
            'Budi Utomo' => 'organisasi yang berdiri pada 20 Mei 1908 dan menjadi tonggak Kebangkitan Nasional',
            'Sarekat Islam' => 'organisasi pergerakan nasional yang berkembang dari Sarekat Dagang Islam',
            'Indische Partij' => 'organisasi pergerakan yang didirikan oleh Tiga Serangkai pada 1912',
            'Tiga Serangkai' => 'sebutan untuk Douwes Dekker, Ki Hajar Dewantara, dan dr. Cipto Mangunkusumo',
            'Sumpah Pemuda' => 'ikrar persatuan pemuda yang diucapkan pada 28 Oktober 1928',
            'Kongres Pemuda II' => 'pertemuan pemuda yang menghasilkan ikrar Sumpah Pemuda',
            'Ki Hajar Dewantara' => 'tokoh pendidikan nasional dan pendiri Taman Siswa',
            'R.A. Kartini' => 'tokoh emansipasi perempuan yang dikenal melalui surat-suratnya',
            'BPUPKI' => 'badan yang dibentuk pada masa pendudukan Jepang untuk menyelidiki persiapan kemerdekaan Indonesia',
            'PPKI' => 'panitia yang mengesahkan UUD 1945 dan menetapkan presiden serta wakil presiden pada 18 Agustus 1945',
            'Peristiwa Rengasdengklok' => 'peristiwa pengamanan Soekarno dan Hatta oleh golongan muda menjelang proklamasi',
            'Proklamasi Kemerdekaan Indonesia' => 'pernyataan kemerdekaan Indonesia pada 17 Agustus 1945',
            'Soekarno' => 'tokoh yang membacakan teks Proklamasi Kemerdekaan Indonesia',
            'Mohammad Hatta' => 'tokoh yang mendampingi Soekarno dan menandatangani teks proklamasi atas nama bangsa Indonesia',
            'Sayuti Melik' => 'tokoh yang mengetik naskah Proklamasi Kemerdekaan Indonesia',
            'UUD 1945' => 'konstitusi yang disahkan PPKI pada 18 Agustus 1945',
            'Pertempuran Surabaya' => 'pertempuran besar pada November 1945 yang diperingati melalui Hari Pahlawan',
            'Bandung Lautan Api' => 'peristiwa pembumihangusan sebagian wilayah Bandung oleh pejuang pada Maret 1946',
            'Perjanjian Linggarjati' => 'perundingan Indonesia-Belanda pada 1946 yang mengakui secara de facto wilayah Republik',
            'Perjanjian Renville' => 'perjanjian Indonesia-Belanda yang ditandatangani di atas kapal USS Renville pada 1948',
            'Konferensi Meja Bundar' => 'perundingan pada 1949 yang menghasilkan pengakuan kedaulatan kepada Republik Indonesia Serikat',
            'Agresi Militer Belanda I' => 'serangan militer Belanda terhadap wilayah Republik pada Juli 1947',
            'Agresi Militer Belanda II' => 'serangan Belanda pada Desember 1948 yang menyerang Yogyakarta',
            'Dekret Presiden 5 Juli 1959' => 'keputusan yang memberlakukan kembali UUD 1945 dan mengakhiri berlakunya UUDS 1950',
            'Supersemar' => 'surat perintah 11 Maret 1966 yang menjadi bagian penting peralihan kekuasaan politik',
            'Reformasi 1998' => 'gerakan yang mengakhiri pemerintahan Orde Baru dan mendorong perubahan politik',
            'ASEAN didirikan' => 'pembentukan organisasi ASEAN pada 8 Agustus 1967 di Bangkok',
            'Konferensi Asia Afrika' => 'konferensi negara-negara Asia dan Afrika yang berlangsung di Bandung pada 1955',
            'Gerakan Non-Blok' => 'gerakan negara-negara yang tidak secara resmi memihak blok kekuatan utama Perang Dingin',
            'Situs Sangiran' => 'situs arkeologi di Jawa Tengah yang terkenal dengan temuan fosil manusia purba',
        ];

        return self::buildThreeFormFactQuestions($facts, 'Sejarah');
    }

    /**
     * Membentuk tiga variasi pilihan ganda untuk setiap fakta.
     * 50 fakta menghasilkan tepat 150 pertanyaan per mata pelajaran.
     */
    private static function buildThreeFormFactQuestions(array $facts, string $subjectName): array
    {
        $items = [];
        foreach ($facts as $term => $description) {
            $wrongDescriptions = self::pickDistractors($facts, $term, 3, true);
            $wrongTerms = self::pickDistractors($facts, $term, 3, false);

            if (count($wrongDescriptions) < 3 || count($wrongTerms) < 3) {
                continue;
            }

            $items[] = self::buildMcq(
                "Dalam {$subjectName}, pengertian {$term} adalah...",
                $description,
                $wrongDescriptions
            );
            $items[] = self::buildMcq(
                "Istilah yang sesuai dengan keterangan \"{$description}\" adalah...",
                (string) $term,
                $wrongTerms
            );
            $items[] = self::buildMcq(
                "Pernyataan yang tepat mengenai {$term} adalah...",
                $description,
                $wrongDescriptions
            );
        }

        return array_slice($items, 0, self::TOTAL_PER_SUBJECT);
    }

    // MATEMATIKA (150 Soal Algoritmik Tepat)

    private static function matematikaQuestions(): array
    {
        $items = [];

        // 1. Operasi hitung bilangan bulat (40 soal)
        $count = 0;
        for ($i = 0; $count < 40 && $i < 100; $i++) {
            $op = ['+', '-', 'x', ':'][$i % 4];

            switch ($op) {
                case '+':
                    $a = mt_rand(10, 500);
                    $b = mt_rand(10, 500);
                    $correct = $a + $b;
                    $question = "Hasil dari {$a} + {$b} adalah...";
                    break;
                case '-':
                    $a = mt_rand(100, 900);
                    $b = mt_rand(1, $a - 1);
                    $correct = $a - $b;
                    $question = "Hasil dari {$a} - {$b} adalah...";
                    break;
                case 'x':
                    $a = mt_rand(2, 50);
                    $b = mt_rand(2, 50);
                    $correct = $a * $b;
                    $question = "Hasil dari {$a} x {$b} adalah...";
                    break;
                default:
                    $b = mt_rand(2, 25);
                    $q = mt_rand(2, 50);
                    $a = $b * $q;
                    $correct = $q;
                    $question = "Hasil dari {$a} : {$b} adalah...";
                    break;
            }

            if (! isset($items[$question])) {
                $items[$question] = self::buildMcq($question, (string) $correct, self::numericDistractors($correct));
                $count++;
            }
        }

        // 2. Penjumlahan pecahan sepenyebut (20 soal)
        $denoms = [2, 3, 4, 5, 6, 8, 10, 12, 15, 20];
        $count = 0;
        for ($i = 0; $count < 20 && $i < 60; $i++) {
            $d = $denoms[$i % count($denoms)];
            $n1 = mt_rand(1, $d - 1);
            $n2 = mt_rand(1, $d - 1);
            $sumN = $n1 + $n2;
            $g = self::gcd($sumN, $d);
            $correct = ($sumN / $g) . '/' . ($d / $g);
            $question = "Hasil dari {$n1}/{$d} + {$n2}/{$d} adalah...";

            $wrongs = [];
            while (count($wrongs) < 3) {
                $wn = max(1, $sumN + mt_rand(-2, 2));
                $cand = $wn . '/' . $d;
                if ($cand !== $correct && ! in_array($cand, $wrongs, true)) {
                    $wrongs[] = $cand;
                }
            }

            if (! isset($items[$question])) {
                $items[$question] = self::buildMcq($question, $correct, $wrongs);
                $count++;
            }
        }

        // 3. Pecahan ke bentuk desimal (10 soal)
        $fracToDecimal = [
            '1/2' => '0,5',
            '1/4' => '0,25',
            '3/4' => '0,75',
            '1/5' => '0,2',
            '2/5' => '0,4',
            '3/5' => '0,6',
            '4/5' => '0,8',
            '1/8' => '0,125',
            '1/10' => '0,1',
            '3/10' => '0,3',
        ];
        foreach ($fracToDecimal as $frac => $dec) {
            $decVal = (float) str_replace(',', '.', $dec);
            $wrongs = [];
            $guard = 0;
            while (count($wrongs) < 3 && $guard < 50) {
                $guard++;
                $w = round($decVal + (mt_rand(-30, 30) / 100), 3);
                if ($w <= 0) {
                    continue;
                }
                $wStr = rtrim(rtrim(number_format($w, 3, '.', ''), '0'), '.');
                $wStr = str_replace('.', ',', $wStr);
                if ($wStr !== $dec && ! in_array($wStr, $wrongs, true)) {
                    $wrongs[] = $wStr;
                }
            }
            $q = "Bentuk desimal dari pecahan {$frac} adalah...";
            $items[$q] = self::buildMcq($q, $dec, $wrongs);
        }

        // 4. Desimal ke bentuk persen (10 soal)
        $decToPercent = [
            '0,1' => '10%',
            '0,15' => '15%',
            '0,2' => '20%',
            '0,25' => '25%',
            '0,3' => '30%',
            '0,4' => '40%',
            '0,5' => '50%',
            '0,6' => '60%',
            '0,75' => '75%',
            '0,9' => '90%',
        ];
        foreach ($decToPercent as $dec => $persen) {
            $val = (int) rtrim($persen, '%');
            $wrongs = [];
            $guard = 0;
            while (count($wrongs) < 3 && $guard < 50) {
                $guard++;
                $w = $val + mt_rand(-3, 3) * 5;
                if ($w <= 0 || $w === $val) {
                    continue;
                }
                $wStr = $w . '%';
                if (! in_array($wStr, $wrongs, true)) {
                    $wrongs[] = $wStr;
                }
            }
            $q = "Bentuk persen dari {$dec} adalah...";
            $items[$q] = self::buildMcq($q, $persen, $wrongs);
        }

        // 5. Persentase dari suatu bilangan (20 soal)
        $percents = [5, 10, 15, 20, 25, 30, 40, 50, 60, 70, 75, 80, 90];
        $count = 0;
        for ($i = 0; $count < 20 && $i < 60; $i++) {
            $p = $percents[$i % count($percents)];
            $base = 20 * mt_rand(1, 25);
            $correct = ($p * $base) / 100;
            $question = "Hasil dari {$p}% dari {$base} adalah...";
            if (! isset($items[$question])) {
                $items[$question] = self::buildMcq($question, (string) $correct, self::numericDistractors($correct));
                $count++;
            }
        }

        // 6. Aljabar sederhana - mencari nilai x (25 soal)
        $count = 0;
        for ($i = 0; $count < 25 && $i < 60; $i++) {
            $a = mt_rand(2, 9);
            $x = mt_rand(1, 20);
            $b = mt_rand(1, 50);
            $sign = $i % 2 === 0 ? '+' : '-';
            $c = $sign === '+' ? ($a * $x + $b) : ($a * $x - $b);
            $question = "Jika {$a}x {$sign} {$b} = {$c}, maka nilai x yang memenuhi adalah...";
            if (! isset($items[$question])) {
                $items[$question] = self::buildMcq($question, (string) $x, self::numericDistractors($x, 1, 25));
                $count++;
            }
        }

        // 7. Geometri: luas & keliling (25 soal)
        $count = 0;
        for ($i = 0; $count < 25 && $i < 60; $i++) {
            switch ($i % 4) {
                case 0:
                    $s = mt_rand(3, 30);
                    if ($i % 8 < 4) {
                        $correct = $s * $s;
                        $question = "Luas persegi dengan panjang sisi {$s} cm adalah...cm2";
                    } else {
                        $correct = 4 * $s;
                        $question = "Keliling persegi dengan panjang sisi {$s} cm adalah...cm";
                    }
                    break;
                case 1:
                    $p = mt_rand(6, 40);
                    $l = mt_rand(3, $p - 1);
                    if ($i % 8 < 4) {
                        $correct = $p * $l;
                        $question = "Luas persegi panjang dengan panjang {$p} cm dan lebar {$l} cm adalah...cm2";
                    } else {
                        $correct = 2 * ($p + $l);
                        $question = "Keliling persegi panjang dengan panjang {$p} cm dan lebar {$l} cm adalah...cm";
                    }
                    break;
                case 2:
                    $alas = 2 * mt_rand(3, 20);
                    $tinggi = mt_rand(3, 20);
                    $correct = ($alas * $tinggi) / 2;
                    $question = "Luas segitiga dengan alas {$alas} cm dan tinggi {$tinggi} cm adalah...cm2";
                    break;
                default:
                    $r = 7 * mt_rand(1, 10);
                    if ($i % 8 < 4) {
                        $correct = (22 / 7) * $r * $r;
                        $question = "Luas lingkaran dengan jari-jari {$r} cm (menggunakan pi=22/7) adalah...cm2";
                    } else {
                        $correct = 2 * (22 / 7) * $r;
                        $question = "Keliling lingkaran dengan jari-jari {$r} cm (menggunakan pi=22/7) adalah...cm";
                    }
                    break;
            }
            $correct = (int) round($correct);
            if (! isset($items[$question])) {
                $items[$question] = self::buildMcq($question, (string) $correct, self::numericDistractors($correct));
                $count++;
            }
        }

        return array_values($items);
    }
}
