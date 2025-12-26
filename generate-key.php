<?php

/**
 * Generate a cryptographically secure key in the format:
 * XXXXX-XXXXX-XXXXX-XXXXX-XXXXX
 *
 * Each segment contains 5 uppercase alphanumeric characters (A-Z, 0-9)
 */
function generateSecureKey(): string
{
    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $segments = [];

    // Generate 5 segments
    for ($i = 0; $i < 5; $i++) {
        $segment = '';

        // Generate 5 characters per segment
        for ($j = 0; $j < 5; $j++) {
            $randomIndex = random_int(0, strlen($characters) - 1);
            $segment .= $characters[$randomIndex];
        }

        $segments[] = $segment;
    }

    return implode('-', $segments);
}

// Generate and display the key
$key = generateSecureKey();
$hash = hash('sha3-256', $key);

echo "Generated key: " . $key . PHP_EOL;
echo "Bcrypt hash:   " . $hash . PHP_EOL;
