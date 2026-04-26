<?php

namespace App\Flags\Entity\Enum;

enum GameType: string
{
    case CAPITALS_AFRICA = 'CAPITALS_AFRICA';
    case CAPITALS_EUROPE = 'CAPITALS_EUROPE';
    case CAPITALS_OCEANIA = 'CAPITALS_OCEANIA';
    case CAPITALS_ASIA = 'CAPITALS_ASIA';
    case CAPITALS_AMERICAS = 'CAPITALS_AMERICAS';
    case FLAGS_WORLD = 'FLAGS_WORLD';
}
