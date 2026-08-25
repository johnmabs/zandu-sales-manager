<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Domain;

enum UnitOfMeasureDimension: string
{
    case Count = 'COUNT';
    case Mass = 'MASS';
    case Volume = 'VOLUME';
    case Length = 'LENGTH';
    case Time = 'TIME';
    case Other = 'OTHER';
}
