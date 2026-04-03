<?php
$content = file_get_contents('nikkk.json');
$questions = json_decode($content, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo "JSON error: " . json_last_error_msg() . "\n";
    exit;
}

if (!is_array($questions)) {
    echo "Not an array.\n";
    exit;
}

foreach ($questions as $i => &$q) {
    if (!isset($q['id'])) echo "- Missing id at index $i\n";
    if (!isset($q['question'])) echo "- Missing question at index $i\n";
    if (!isset($q['options'])) echo "- Missing options at index $i\n";
    
    if (!isset($q['id'], $q['question'], $q['options'])) {
        echo "Failed condition 1 at index $i\n";
        continue;
    }
    
    if (!isset($q['answer']) && isset($q['correct'])) {
        $q['answer'] = max(0, (int)$q['correct'] - 1);
        unset($q['correct']);
    }
    
    if (!isset($q['answer'])) {
        echo "Failed condition 2 at index $i (No answer)\n";
        continue;
    }
}
echo "Validation finished.\n";
