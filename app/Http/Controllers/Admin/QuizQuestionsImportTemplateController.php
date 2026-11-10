<?php

namespace App\Http\Controllers\Admin;

use App\Filament\Admin\Resources\Quizzes\QuizResource;
use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class QuizQuestionsImportTemplateController extends Controller
{
    public function __invoke(): BinaryFileResponse
    {
        abort_unless(auth('admin')->check() && QuizResource::canViewAny(), 403);

        return response()->download(
            public_path('templates/quiz-questions-import-template-v2.xlsx'),
            'quiz-questions-import-template-v2.xlsx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );
    }
}
