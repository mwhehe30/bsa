<?php

namespace App\Imports;

use App\Models\Question;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class QuestionsImport implements ToModel, WithHeadingRow
{
    protected $exam_id;
    protected $isPersonality;

    public function __construct($exam_id, $isPersonality = false)
    {
        $this->exam_id = $exam_id;
        $this->isPersonality = $isPersonality;
    }

    public function model(array $row)
    {
        $data = [
            'exam_id'   => $this->exam_id,
            'question'  => $row['question'],
            'option_1'  => $row['option_1'],
            'option_2'  => $row['option_2'],
            'option_3'  => $row['option_3'],
            'option_4'  => $row['option_4'],
            'option_5'  => $row['option_5'],
        ];

        if ($this->isPersonality) {
            $data['answer'] = 1;
            $data['points'] = [
                '1' => isset($row['point_1']) ? (int) $row['point_1'] : 5,
                '2' => isset($row['point_2']) ? (int) $row['point_2'] : 4,
                '3' => isset($row['point_3']) ? (int) $row['point_3'] : 3,
                '4' => isset($row['point_4']) ? (int) $row['point_4'] : 2,
                '5' => isset($row['point_5']) ? (int) $row['point_5'] : 1,
            ];
        } else {
            $data['answer'] = $row['answer'];
        }

        return new Question($data);
    }
}
