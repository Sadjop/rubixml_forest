<?php
// src/predict.php

require 'vendor/autoload.php';

use Rubix\ML\Persisters\Filesystem;

$modelPath = __DIR__ . '/../datasets/models/model.rbx';
$persister = new Filesystem($modelPath);

try {
    $estimator = $persister->load();
    echo "Model loaded successfully.\n";
} catch (\Throwable $e) {
    die("Erreur du chargement du modèle: " . $e->getMessage() . "\n");
}

// Exemples de données pour prédiction
$newSamples = [
    [120, 150, 130, /* ... 32x32 grayscale pixel values */],
    [100, 110, 140, /* ... */],
];

echo "Predicting...\n";
$predictions = $estimator->predict($newSamples);

foreach ($predictions as $i => $prediction) {
    echo "Échantillon $i: Résultat prédit: $prediction\n";
}
