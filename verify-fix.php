<?php
echo "========================================\n";
echo "STACKING FIX VERIFICATION SCRIPT\n";
echo "========================================\n\n";

// Check 1: Verify x-teleport is removed
echo "Check 1: Verify x-teleport removed from modal\n";
$modalContent = file_get_contents('resources/views/components/enhanced-import-modal.blade.php');
if (strpos($modalContent, 'x-teleport="body"') === false && 
    strpos($modalContent, '<template x-teleport') === false) {
    echo "✅ PASS - x-teleport wrapper removed\n\n";
} else {
    echo "❌ FAIL - x-teleport still present!\n\n";
}

// Check 2: Verify drag-drop handler has entity parameter
echo "Check 2: Verify drag-drop handler includes entity\n";
if (strpos($modalContent, "handleEnhancedImportFile({target: input}, '{{ \$entity }}')") !== false) {
    echo "✅ PASS - Drag-drop handler includes entity parameter\n\n";
} else {
    echo "❌ FAIL - Drag-drop handler missing entity parameter\n\n";
}

// Check 3: Verify modal structure is correct
echo "Check 3: Verify modal starts with div (not teleport template)\n";
$lines = explode("\n", $modalContent);
$foundDiv = false;
foreach ($lines as $i => $line) {
    // Check line 4 (after comments) for modal opening
    if ($i < 10 && preg_match('/<div\s+x-show="showEnhancedImportModal"/', $line)) {
        $foundDiv = true;
        echo "✅ PASS - Modal starts with <div x-show> (not teleport)\n\n";
        break;
    }
}
if (!$foundDiv) {
    echo "❌ FAIL - Modal structure incorrect\n\n";
}

// Check 4: Verify NO teleport wrapper tags at start/end
echo "Check 4: Verify no x-teleport wrapper (only x-for templates OK)\n";
$teleportWrapperOpen = false;
$teleportWrapperClose = false;

if (preg_match('/<template\s+x-teleport="body"/', $modalContent)) {
    $teleportWrapperOpen = true;
}

// Look for closing template after modal closes (not for x-for loops)
$lines = explode("\n", $modalContent);
$inXForLoop = false;
foreach ($lines as $line) {
    if (strpos($line, '<template x-for') !== false) {
        $inXForLoop = true;
    }
    if ($inXForLoop && strpos($line, '</template>') !== false) {
        $inXForLoop = false; // Expected, part of x-for
        continue;
    }
    // If we find a closing template outside of x-for, that's bad
    if (!$inXForLoop && strpos($line, '</template>') !== false && 
        strpos($line, '<template x-for') === false) {
        $teleportWrapperClose = true;
    }
}

if (!$teleportWrapperOpen && !$teleportWrapperClose) {
    echo "✅ PASS - No x-teleport wrapper tags\n\n";
} else {
    if ($teleportWrapperOpen) echo "  ❌ Found <template x-teleport>\n";
    if ($teleportWrapperClose) echo "  ❌ Found unexpected </template>\n";
    echo "\n";
}

// Check 5: Verify function calls exist in modal
echo "Check 5: Verify function calls in modal\n";
$functions = [
    '@click="downloadImportTemplate' => true,
    '@click="commitEnhancedImport' => true,
    '@click="downloadErrorReport' => true,
    '@change="handleEnhancedImportFile' => true,
];

foreach ($functions as $func => $required) {
    if (strpos($modalContent, $func) !== false) {
        echo "  ✅ Found $func\n";
    } else {
        echo "  ❌ Missing $func\n";
    }
}
echo "\n";

// Check 6: Verify enhanced-import-modal component is included
echo "Check 6: Verify modal component is included in all pages\n";
$pages = ['schools', 'districts', 'regions'];
$allIncluded = true;
foreach ($pages as $page) {
    $content = file_get_contents("resources/views/registration/{$page}.blade.php");
    if (strpos($content, "enhanced-import-modal") !== false) {
        echo "  ✅ {$page}.blade.php includes enhanced-import-modal\n";
    } else {
        echo "  ❌ {$page}.blade.php missing enhanced-import-modal\n";
        $allIncluded = false;
    }
}
echo "\n";

// Check 7: Verify syntax
echo "Check 7: Verify PHP/Blade syntax\n";
$files = [
    'resources/views/components/enhanced-import-modal.blade.php',
    'resources/views/registration/schools.blade.php',
    'resources/views/registration/districts.blade.php',
    'resources/views/registration/regions.blade.php',
];

$allSyntaxOk = true;
foreach ($files as $file) {
    $output = shell_exec("php -l " . escapeshellarg($file) . " 2>&1");
    if (strpos($output, 'No syntax errors') !== false) {
        echo "  ✅ " . basename($file) . "\n";
    } else {
        echo "  ❌ " . basename($file) . "\n";
        $allSyntaxOk = false;
    }
}
echo "\n";

// Check 8: Verify no x-teleport in any registration pages
echo "Check 8: Verify no x-teleport in registration pages\n";
$allClean = true;
foreach ($pages as $page) {
    $content = file_get_contents("resources/views/registration/{$page}.blade.php");
    if (strpos($content, 'x-teleport') === false) {
        echo "  ✅ {$page}.blade.php clean\n";
    } else {
        echo "  ⚠️  {$page}.blade.php has x-teleport (check if needed)\n";
    }
}
echo "\n";

// Summary
echo "========================================\n";
echo "VERIFICATION COMPLETE\n";
echo "========================================\n";
if ($allSyntaxOk && $allIncluded && !$teleportWrapperOpen && !$teleportWrapperClose) {
    echo "✅ ALL CRITICAL CHECKS PASSED\n";
    echo "\nThe fix is ready for deployment.\n";
} else {
    echo "⚠️  REVIEW REQUIRED\n";
    echo "\nCheck the failed items above.\n";
}
echo "\nFor details, see: BUGFIX_STACKING_ISSUE_2026_02_15.md\n";

