<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Style\Font;

class GeneratePersonalityTemplate extends Command
{
    protected $signature = 'template:personality';
    protected $description = 'Generate template Word untuk soal kepribadian';

    public function handle()
    {
        $this->info('🚀 Generating template soal kepribadian...');

        $phpWord = new PhpWord();
        
        // Metadata
        $properties = $phpWord->getDocInfo();
        $properties->setCreator('Sistem Ujian Online');
        $properties->setTitle('Template Soal Kepribadian');

        // Add section
        $section = $phpWord->addSection();

        // Langsung contoh soal (simple, no title, no instruction)
        $this->addSampleQuestion($section, 
            'Saya lebih suka bekerja dalam tim daripada bekerja sendiri',
            [
                'Sangat Setuju',
                'Setuju',
                'Netral',
                'Tidak Setuju',
                'Sangat Tidak Setuju',
            ]
        );

        $this->addSampleQuestion($section,
            'Saya merasa nyaman berbicara di depan banyak orang',
            [
                'Sangat Setuju',
                'Setuju',
                'Netral',
                'Tidak Setuju',
                'Sangat Tidak Setuju',
            ]
        );

        $this->addSampleQuestion($section,
            'Saya lebih suka mengikuti aturan yang sudah ada',
            [
                'Sangat Setuju',
                'Setuju',
                'Netral',
                'Tidak Setuju',
                'Sangat Tidak Setuju',
            ]
        );

        $section->addTextBreak(1);

        // Save file
        $filename = 'contoh_soal_kepribadian.docx';
        $filepath = public_path('assets/word/' . $filename);

        // Create directory if not exists
        if (!file_exists(public_path('assets/word'))) {
            mkdir(public_path('assets/word'), 0777, true);
        }

        $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($filepath);

        $this->info('✅ Template berhasil dibuat: ' . $filepath);
        $this->info('📄 File: ' . $filename);
    }

    private function addSampleQuestion($section, $question, $options)
    {
        // Pertanyaan pake NUMBERED LIST (1. 2. 3.) - no bold, plain text
        $section->addListItem(
            $question,
            0,
            ['name' => 'Arial', 'size' => 11],
            ['listType' => \PhpOffice\PhpWord\Style\ListItem::TYPE_NUMBER]
        );

        // Opsi pake LETTERED LIST (a. b. c. d. e.) - depth 1, plain text
        foreach ($options as $optionText) {
            $section->addListItem(
                $optionText,
                1,
                ['name' => 'Arial', 'size' => 11],
                ['listType' => \PhpOffice\PhpWord\Style\ListItem::TYPE_ALPHANUM]
            );
        }

        // Spacing after question
        $section->addTextBreak(1);
    }
}
