<?php
// src/train_model.php

require 'vendor/autoload.php';

use Rubix\ML\Datasets\Labeled;
use Rubix\ML\Classifiers\RandomForest;
use Rubix\ML\Transformers\ZScaleStandardizer;
use Rubix\ML\Persisters\Filesystem;

function loadDataset(string $csvPath): Labeled
{
    if (!file_exists($csvPath)) {
        die("Erreur: Dataset non trouvé $csvPath.\n");
    }

    $samples = [];
    $labels = [];

    $file = fopen($csvPath, 'r');
    if ($file === false) {
        die("Erreur: Impossible d'ouvrir le fichier $csvPath.\n");
    }

    $totalLines = count(file($csvPath)); // Nombre total de lignes
    $currentLine = 0;

    while (($data = fgetcsv($file)) !== false) {
        $labels[] = array_pop($data);
        $samples[] = array_map('floatval', $data);

        $currentLine++;
        if ($currentLine % 1000 === 0 || $currentLine === $totalLines) {
            echo "Chargement des données : $currentLine / $totalLines lignes traitées\r";
        }
    }
    fclose($file);

    echo "\nChargement du dataset terminé.\n";
    return new Labeled($samples, $labels);
}

$csvPath = __DIR__ . '/../datasets/features.csv';
echo "Lecture du dataset depuis $csvPath...\n";
$dataset = loadDataset($csvPath);

// Normalisation des données
echo "Application de la normalisation...\n";
$dataset->apply(new ZScaleStandardizer());
echo "Normalisation terminée.\n";

// Initialisation et entraînement du modèle
$estimator = new RandomForest(100); // Random Forest avec 100 arbres

echo "Entrainement du modèle...\n";

// Simulation d'avancement (RandomForest n'a pas de progression native)
$totalSamples = count($dataset->samples());
$chunkSize = max(1, (int)($totalSamples / 10));
for ($i = 0; $i < $totalSamples; $i += $chunkSize) {
    usleep(50000); // Simulation d'un délai
    $progress = min(100, (int)(($i + $chunkSize) / $totalSamples * 100));
    echo "Progression de l'entraînement : $progress% terminé\r";
}
$estimator->train($dataset);

echo "\nEntrainement terminé !\n";

// Sauvegarde du modèle
$modelPath = __DIR__ . '/../datasets/models/model.rbx';
$persister = new Filesystem($modelPath);

echo "Sauvegarde du modèle en cours...\n";
try {
    $persister->save($estimator);
    echo "Entrainement du modèle terminé. Modèle sauvegardé sur $modelPath.\n";
} catch (\Throwable $e) {
    die("Erreur d'enregistrement du modèle: " . $e->getMessage() . "\n");
}
