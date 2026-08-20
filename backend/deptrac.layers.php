<?php

declare(strict_types=1);

use Deptrac\Deptrac\Contract\Config\Collector\ClassLikeConfig;
use Deptrac\Deptrac\Contract\Config\DeptracConfig;
use Deptrac\Deptrac\Contract\Config\Layer;
use Deptrac\Deptrac\Contract\Config\Ruleset;

return static function (DeptracConfig $config): void {
    $domain = Layer::withName('Domain')->collectors(
        ClassLikeConfig::create(
            '.*Zandu\\Modules\\[^\\]+\\Domain\\.*'
        ),
    );

    $application = Layer::withName('Application')->collectors(
        ClassLikeConfig::create(
            '.*Zandu\\Modules\\[^\\]+\\Application\\.*'
        ),
    );

    $infrastructure = Layer::withName('Infrastructure')->collectors(
        ClassLikeConfig::create(
            '.*Zandu\\Modules\\[^\\]+\\Infrastructure\\.*'
        ),
    );

    $presentation = Layer::withName('Presentation')->collectors(
        ClassLikeConfig::create(
            '.*Zandu\\Modules\\[^\\]+\\Presentation\\.*'
        ),
    );

    $sharedKernel = Layer::withName('SharedKernel')->collectors(
        ClassLikeConfig::create(
            '.*Zandu\\SharedKernel\\.*'
        ),
    );

    $platform = Layer::withName('Platform')->collectors(
        ClassLikeConfig::create(
            '.*Zandu\\Platform\\.*'
        ),
    );

    $config
        ->paths('./src')
        ->layers(
            $domain,
            $application,
            $infrastructure,
            $presentation,
            $sharedKernel,
            $platform,
        )
        ->rulesets(
            Ruleset::forLayer($domain)
                ->accesses($sharedKernel),

            Ruleset::forLayer($application)
                ->accesses(
                    $domain,
                    $sharedKernel,
                ),

            Ruleset::forLayer($infrastructure)
                ->accesses(
                    $domain,
                    $application,
                    $sharedKernel,
                    $platform,
                ),

            Ruleset::forLayer($presentation)
                ->accesses(
                    $application,
                    $sharedKernel,
                ),

            Ruleset::forLayer($sharedKernel),

            Ruleset::forLayer($platform)
                ->accesses($sharedKernel),
        );
};
