<?php

/** Evolution CMS is not a dev dependency, so static analysis has to be told its global helper exists. */

declare(strict_types=1);

namespace EvolutionCMS {
    class Core
    {
        public string $documentOutput = '';

        public function isLoggedIn(string $context = ''): bool
        {
            return false;
        }

        public function getConfig(string $key): mixed
        {
            return null;
        }
    }
}

namespace {
    function evo(): EvolutionCMS\Core
    {
        return new EvolutionCMS\Core();
    }
}
