<?php
function extractFeatures(string $imagePath, int $resizeWidth = 100, int $resizeHeight = 100): array
{
    $image = @imagecreatefromstring(file_get_contents($imagePath));
    if (!$image) {
        echo "Attention: impossible de process l'image: $imagePath\n";
        return [];
    }

    $resized = imagescale($image, $resizeWidth, $resizeHeight);

    $features = [];
    for ($y = 0; $y < $resizeHeight; $y++) {
        for ($x = 0; $x < $resizeWidth; $x++) {
            $rgb = imagecolorat($resized, $x, $y);
            $r = ($rgb >> 16) & 0xFF;
            $g = ($rgb >> 8) & 0xFF;
            $b = $rgb & 0xFF;
            $features[] = ($r + $g + $b) / 3;
        }
    }
    imagedestroy($image);
    imagedestroy($resized);
    return $features;
}

function processDataset(string $inputDir, string $outputCsv): void
{
    if (!is_dir($inputDir)) {
        die("Erreur: Le repertoire $inputDir n'existe pas.\n");
    }

    $output = fopen($outputCsv, 'w');
    if ($output === false) {
        die("Erreur: Impossible d'ouvrir $outputCsv pour écriture.\n");
    }

    $directories = scandir($inputDir);

    foreach ($directories as $dir) {
        if ($dir === '.' || $dir === '..') {
            continue;
        }

        $classDir = $inputDir . DIRECTORY_SEPARATOR . $dir;
        if (is_dir($classDir)) {
            $label = preg_replace('/\s*\d+$/', '', $dir);
            $files = scandir($classDir);

            foreach ($files as $file) {
                if (pathinfo($file, PATHINFO_EXTENSION) === 'jpg') {
                    $imagePath = $classDir . DIRECTORY_SEPARATOR . $file;
                    $features = extractFeatures($imagePath);

                    if (!empty($features)) {
                        fputcsv($output, array_merge($features, [$label]));
                    }
                }
            }
        }
    }

    fclose($output);
}

$inputDir = __DIR__ . '/../datasets/train';
$outputCsv = __DIR__ . '/../datasets/features.csv';

processDataset($inputDir, $outputCsv);

echo "Extraction des éléments réussis. Sauvegardé dans $outputCsv.\n";
