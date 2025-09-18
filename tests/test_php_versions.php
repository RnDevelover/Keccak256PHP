#!/usr/bin/env php
<?php

/**
 * Test script to verify keccak256 extension compatibility across PHP versions
 */

function test_keccak256_functionality() {
    echo "Testing keccak256 functionality...\n";
    
    // Test cases with expected results
    $test_cases = [
        '' => 'c5d2460186f7233c927e7db2dcc703c0e500b653ca82273b7bfad8045d85a470',
        '00' => 'bc36789e7a1e281436464229828f817d6612f7b477d66591ff96a9e064bcc98a',
        'deadbeef' => 'd4fd4e189132273036449fc9e11198c739161b4c0116a9a2dccdfa1c492006f1',
        'ff' => '8b1a944cf13a9a1c08facb2c9e98623ef3254d2ddb48113885c3e8e97fec8db9'
    ];
    
    $passed = 0;
    $total = count($test_cases);
    
    foreach ($test_cases as $input => $expected) {
        try {
            $result = keccak256($input);
            if ($result === $expected) {
                echo "✓ Test passed for input '$input'\n";
                $passed++;
            } else {
                echo "✗ Test failed for input '$input'\n";
                echo "  Expected: $expected\n";
                echo "  Got:      $result\n";
            }
        } catch (Exception $e) {
            echo "✗ Exception for input '$input': " . $e->getMessage() . "\n";
        }
    }
    
    echo "\nResults: $passed/$total tests passed\n";
    return $passed === $total;
}

function test_error_handling() {
    echo "\nTesting error handling...\n";
    
    $error_cases = [
        'invalid_hex' => 'gg',
        'odd_length' => 'abc',
        'mixed_case_invalid' => 'aG'
    ];
    
    $passed = 0;
    $total = count($error_cases);
    
    foreach ($error_cases as $test_name => $input) {
        try {
            $result = keccak256($input);
            echo "✗ Expected exception for $test_name, but got result: $result\n";
        } catch (Exception $e) {
            echo "✓ Correctly threw exception for $test_name: " . $e->getMessage() . "\n";
            $passed++;
        }
    }
    
    echo "\nError handling results: $passed/$total tests passed\n";
    return $passed === $total;
}

// Main test execution
echo "PHP Version: " . PHP_VERSION . "\n";
echo "Extension loaded: " . (extension_loaded('keccak256') ? 'Yes' : 'No') . "\n";

if (!extension_loaded('keccak256')) {
    echo "ERROR: keccak256 extension not loaded!\n";
    exit(1);
}

if (!function_exists('keccak256')) {
    echo "ERROR: keccak256 function not available!\n";
    exit(1);
}

$functionality_ok = test_keccak256_functionality();
$error_handling_ok = test_error_handling();

if ($functionality_ok && $error_handling_ok) {
    echo "\n✓ All tests passed for PHP " . PHP_VERSION . "\n";
    exit(0);
} else {
    echo "\n✗ Some tests failed for PHP " . PHP_VERSION . "\n";
    exit(1);
}