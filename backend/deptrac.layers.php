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

    $symfony = Layer::withName('Symfony')->collectors(
        ClassLikeConfig::create(
            '.*Symfony\\.*'
        ),
    );

    $doctrine = Layer::withName('Doctrine')->collectors(
        ClassLikeConfig::create(
            '.*Doctrine\\.*'
        ),
    );

    $apiPlatform = Layer::withName('ApiPlatform')->collectors(
        ClassLikeConfig::create(
            '.*ApiPlatform\\.*'
        ),
    );

    $brickMath = Layer::withName('BrickMath')->collectors(
        ClassLikeConfig::create(
            '.*Brick\\Math\\.*'
        ),
    );

    $openTelemetry = Layer::withName('OpenTelemetry')->collectors(
        ClassLikeConfig::create(
            '.*OpenTelemetry\\.*'
        ),
    );

    $monolog = Layer::withName('Monolog')->collectors(
        ClassLikeConfig::create(
            '.*Monolog\\.*'
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
            $symfony,
            $doctrine,
            $apiPlatform,
            $brickMath,
            $openTelemetry,
            $monolog,
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
                    $symfony,
                    $doctrine,
                    $apiPlatform,
                ),

            Ruleset::forLayer($presentation)
                ->accesses(
                    $application,
                    $sharedKernel,
                    $symfony,
                    $apiPlatform,
                ),

            Ruleset::forLayer($sharedKernel),

            Ruleset::forLayer($platform)
                ->accesses(
                    $sharedKernel,
                    $symfony,
                    $apiPlatform,
                    $brickMath,
                    $openTelemetry,
                    $monolog,
                ),
        );
};
