<?php

declare(strict_types=1);

namespace Chronhub\Message\Router;

enum ProducerStrategy : string
{
    case SYNC = 'sync';

    case ASYNC = 'async';

    case PER_MESSAGE = 'per_message';
}
