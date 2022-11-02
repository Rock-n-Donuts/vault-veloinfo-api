<?php

namespace Rockndonuts\Hackqc\Transformers;

class ContributionTransformer
{
    public function transform(array $contribution): array
    {
        $contribution['location'] = explode(",", $contribution['location']);

        return $contribution;
    }

    public function transformMany(array $contributions): array
    {
        $parsed = [];
        foreach ($contributions as $contribution) {
            $parsed[] = $this->transform($contribution);
        }

        return $parsed;
    }
}