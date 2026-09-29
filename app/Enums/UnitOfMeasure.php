<?php

namespace App\Enums;

enum UnitOfMeasure: string
{
    case BAG = 'BAG';
    case ROLL = 'ROLL';
    case PACK = 'PACK';
    case PIECE = 'PIECE';
    case KG = 'KG';
    case LITRE = 'LITRE';
    case CARTON = 'CARTON';
    case BOTTLE = 'BOTTLE';
    case SACHET = 'SACHET';
}
