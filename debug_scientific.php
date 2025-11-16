<?php
/**
 * Debug the scientific notation conversion issue
 */

// Test the specific case
$excelValue = '2.34E+11';
echo "Excel value: '$excelValue'\n";

// Show what floatval converts it to
$floatValue = floatval($excelValue);
echo "floatval result: $floatValue\n";

// Show what number_format produces
$numberFormatResult = number_format($floatValue, 0, '.', '');
echo "number_format result: '$numberFormatResult'\n";

// Show the difference
$expected = '233548664851';
echo "Expected: '$expected'\n";
echo "Actual: '$numberFormatResult'\n";
echo "Match: " . ($expected === $numberFormatResult ? 'YES' : 'NO') . "\n";

// Test with the actual expected scientific notation
$correctScientific = '2.33548664851E+11';
echo "\nCorrect scientific notation:\n";
echo "Value: '$correctScientific'\n";
$correctFloat = floatval($correctScientific);
echo "floatval: $correctFloat\n";
$correctFormatted = number_format($correctFloat, 0, '.', '');
echo "number_format: '$correctFormatted'\n";
echo "Match with expected: " . ($expected === $correctFormatted ? 'YES' : 'NO') . "\n";
?>
