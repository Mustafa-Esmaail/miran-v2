<?php

namespace App\Enums;

enum LessonType: string
{
    case Pdf = 'pdf';
    case Text = 'text';
    case Video = 'video';
}
