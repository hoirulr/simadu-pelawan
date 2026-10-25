<?php

use App\Enums\RiskCategory;
use App\Services\RiskScoringService;

test('risk scoring service determines category correctly according to PRD', function () {
    $service = new RiskScoringService;

    // 0 - 4: Rendah (Hijau)
    expect($service->determineCategory(0))->toBe(RiskCategory::Rendah);
    expect($service->determineCategory(4))->toBe(RiskCategory::Rendah);

    // 5 - 9: Sedang (Kuning)
    expect($service->determineCategory(5))->toBe(RiskCategory::Sedang);
    expect($service->determineCategory(9))->toBe(RiskCategory::Sedang);

    // 10 - 15: Tinggi (Merah)
    expect($service->determineCategory(10))->toBe(RiskCategory::Tinggi);
    expect($service->determineCategory(15))->toBe(RiskCategory::Tinggi);
});

test('risk scoring service produces appropriate system recommendations', function () {
    $service = new RiskScoringService;

    $recLow = $service->getSystemRecommendation(RiskCategory::Rendah, 3);
    expect($recLow)->toContain('Kondisi Balai terkendali');

    $recMed = $service->getSystemRecommendation(RiskCategory::Sedang, 7);
    expect($recMed)->toContain('Terdapat catatan kekurangan/deviasi');

    $recHigh = $service->getSystemRecommendation(RiskCategory::Tinggi, 12);
    expect($recHigh)->toContain('berisiko tinggi');
});
