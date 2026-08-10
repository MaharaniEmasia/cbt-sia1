<?php

namespace Database\Seeders;

use App\Models\Answer;
use App\Models\Question;
use App\Models\Subject;
use Illuminate\Database\Seeder;

class QuestionSeeder extends Seeder
{
    public function run(): void
    {
        // 3 Mata Pelajaran Baru
        $subjectsData = [
            'Jaringan Komputer',
            'Pemrograman Web',
            'Keamanan Siber'
        ];

        // Kosakata acak untuk membuat Soal yang baru
        $tanya = [
            'Apa fungsi utama dari protokol', 
            'Bagaimana cara mengimplementasikan', 
            'Apa yang dimaksud dengan kerentanan pada', 
            'Sebutkan kelebihan penggunaan framework pada', 
            'Manakah port default yang digunakan untuk layanan', 
            'Bagaimana cara paling efektif mencegah serangan pada'
        ];
        
        $istilah = [
            'IP Address', 'routing dinamis', 'SQL Injection', 
            'Cross-Site Scripting (XSS)', 'REST API', 'Model OSI', 
            'algoritma kriptografi', 'React JS', 'Laravel', 
            'firewall', 'DNS (Domain Name System)', 'serangan DDoS'
        ];
        
        $konteks = [
            'di lingkungan jaringan lokal (LAN)', 
            'pada pengembangan aplikasi e-commerce', 
            'dalam menjaga kerahasiaan data pengguna', 
            'untuk mengoptimalkan performa server', 
            'saat melakukan audit keamanan sistem', 
            'pada arsitektur aplikasi berbasis cloud'
        ];

        // Kosakata acak untuk membuat Jawaban yang baru
        $jawabDepan = [
            'Berfungsi untuk mengarahkan rute', 
            'Metode ini sangat ampuh untuk mengamankan', 
            'Adalah proses menerjemahkan nama domain menjadi', 
            'Digunakan untuk merender antarmuka pengguna pada', 
            'Teknik ini secara otomatis memblokir akses ilegal ke dalam', 
            'Merupakan standar protokol komunikasi data antar'
        ];
        
        $jawabBelakang = [
            'komputer klien di seluruh jaringan global.', 
            'database dari pencurian kredensial.', 
            'halaman web dinamis dan responsif.', 
            'paket data yang dicurigai sebagai malware.', 
            'sistem operasi server Linux.', 
            'endpoint API dari pihak ketiga.'
        ];

        foreach ($subjectsData as $subjectName) {
            // 1. Buat Mata Pelajaran Baru
            $subject = Subject::create([
                'name' => $subjectName,
                'description' => "Evaluasi pemahaman untuk mata kuliah $subjectName.",
                'is_active' => true,
            ]);

            // 2. Buat 150 Soal per Mata Pelajaran Baru
            for ($i = 1; $i <= 150; $i++) {
                
                // Merangkai kalimat pertanyaan acak
                $acakTanya = $tanya[array_rand($tanya)];
                $acakIstilah = $istilah[array_rand($istilah)];
                $acakKonteks = $konteks[array_rand($konteks)];
                
                $payloadText = "{$acakTanya} {$acakIstilah} {$acakKonteks}?";

                $question = Question::create([
                    'subject_id' => $subject->id,
                    'payload' => $payloadText,
                    'score' => 10,
                    'description' => 'Silakan pilih satu jawaban yang paling tepat.',
                    'is_active' => true,
                ]);

                // 3. Buat 4 Pilihan Jawaban
                $correctIndex = rand(0, 3); // Tentukan acak (A, B, C, atau D)

                for ($j = 0; $j < 4; $j++) {
                    $acakJawabDepan = $jawabDepan[array_rand($jawabDepan)];
                    $acakJawabBelakang = $jawabBelakang[array_rand($jawabBelakang)];
                    
                    Answer::create([
                        'question_id' => $question->id,
                        'text' => "{$acakJawabDepan} {$acakJawabBelakang}", 
                        'is_correct' => ($j === $correctIndex), 
                        'is_active' => true,
                    ]);
                }
            }
        }
    }
}