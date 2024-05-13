<?php

namespace App\Enums;

enum DefaultValue: string
{
    case Date = '0000-00-00';
    case DateTime = '0000-00-00 00:00:00';
    case Text = '-';
}
