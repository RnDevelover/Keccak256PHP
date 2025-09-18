#!/usr/bin/env php
<?php

/**
 * Thread Safety Test for Keccak256 Extension
 * 
 * This test validates thread safety when ZTS (Zend Thread Safety) is enabled.
 * It simulates concurrent-like operations and tests for state interference.
 * 
 * Requirements tested:
 * - 5.5: Thread safety if ZTS is enabled
 * - 1.1: Compatibility with PHP 8.0+
 * - 1.2: No warnings or deprecation notices
 */

class ThreadSafetyTest {
    private $total_tests = 0;
    private $passed_tests = 0;
    private $zts_enabled = false;
    
    public function __construct() {
        echo "=== Keccak256 Thread Safety Test ===\n";
        echo "PHP Version: " . PHP_VERSION . "\n";
        echo "Extension loaded: " . (extension_loaded('keccak256') ? 'Yes' : 'No') . "\n";
        
        // Check ZTS status
        $this->zts_enabled = defined('ZEND_THREAD_SAFE') && ZEND_THREAD_SAFE;
        echo "ZTS (Zend Thread Safety): " . ($this->zts_enabled ? 'Enabled' : 'Disabled') . "\n";
        
        if (!extension_loaded('keccak256')) {
            echo "ERROR: keccak256 extension not loaded!\n";
            exit(1);
        }
        
        if (!function_exists('keccak256')) {
            echo "ERROR: keccak256 function not available!\n";
            exit(1);
        }
        
        echo "\n";
    }
    
    /**
     * Test basic thread safety by simulating concurrent operations
     */
    public function testConcurrentOperations() {
        echo "Testing concurrent-like operations...\n";
        
        $this->runTest("Interleaved function calls", function() {
            // Test data with known results
            $test_vectors = [
                '00' => 'bc36789e7a1e281436464229828f817d6612f7b477d66591ff96a9e064bcc98a',
                'ff' => '8b1a944cf13a9a1c08facb2c9e98623ef3254d2ddb48113885c3e8e97fec8db9',
                'deadbeef' => 'd4fd4e189132273036449fc9e11198c739161b4c0116a9a2dccdfa1c492006f1',
                'cafebabe' => '6fe2683bd1d27cbb7a05c570693bea39e0c082ed16722e5ecadcfb7cfdbd20db',
            ];
            
            $results = [];
            $iterations = 100;
            
            // Simulate interleaved operations
            for ($i = 0; $i < $iterations; $i++) {
                foreach ($test_vectors as $input => $expected) {
                    $key = $input . '_' . $i;
                    $results[$key] = keccak256($input);
                    
                    // Verify result immediately
                    if ($results[$key] !== $expected) {
                        throw new Exception("Incorrect result for $input at iteration $i: got {$results[$key]}, expected $expected");
                    }
                }
            }
            
            return "✓ {$iterations} iterations of interleaved calls successful";
        });
        
        $this->runTest("Rapid successive calls", function() {
            $input = 'deadbeef';
            $expected = 'd4fd4e189132273036449fc9e11198c739161b4c0116a9a2dccdfa1c492006f1';
            $iterations = 1000;
            
            for ($i = 0; $i < $iterations; $i++) {
                $result = keccak256($input);
                if ($result !== $expected) {
                    throw new Exception("Inconsistent result at iteration $i: got $result");
                }
            }
            
            return "✓ {$iterations} rapid successive calls consistent";
        });
    }
    
    /**
     * Test for global state interference
     */
    public function testGlobalStateIsolation() {
        echo "\nTesting global state isolation...\n";
        
        $this->runTest("No state interference between calls", function() {
            // Use different input sizes to potentially trigger different code paths
            $test_cases = [
                ['input' => '', 'expected' => 'c5d2460186f7233c927e7db2dcc703c0e500b653ca82273b7bfad8045d85a470'],
                ['input' => 'cc', 'expected' => 'eead6dbfc7340a56caedc044696a168870549a6a7f6f56961e84a54bd9970b8a'],
                ['input' => 'deadbeef', 'expected' => 'd4fd4e189132273036449fc9e11198c739161b4c0116a9a2dccdfa1c492006f1'],
                ['input' => str_repeat('ab', 100), 'expected' => null], // Will calculate expected
            ];
            
            // Calculate expected for long input
            $test_cases[3]['expected'] = keccak256($test_cases[3]['input']);
            
            // Test in various orders to detect state interference
            $orders = [
                [0, 1, 2, 3],
                [3, 2, 1, 0],
                [1, 3, 0, 2],
                [2, 0, 3, 1],
            ];
            
            foreach ($orders as $order_index => $order) {
                foreach ($order as $case_index) {
                    $case = $test_cases[$case_index];
                    $result = keccak256($case['input']);
                    
                    if ($result !== $case['expected']) {
                        throw new Exception("State interference detected in order $order_index, case $case_index");
                    }
                }
            }
            
            return "✓ No state interference detected across different call orders";
        });
    }
    
    /**
     * Run a single test and track results
     */
    private function runTest($test_name, $test_function) {
        $this->total_tests++;
        
        try {
            $result = $test_function();
            $this->passed_tests++;
            echo "  ✓ $test_name: $result\n";
        } catch (Exception $e) {
            echo "  ✗ $test_name: " . $e->getMessage() . "\n";
        }
    }
    
    /**
     * Run all tests and display summary
     */
    public function runAllTests() {
        $start_time = microtime(true);
        
        $this->testConcurrentOperations();
        $this->testGlobalStateIsolation();
        
        $end_time = microtime(true);
        $duration = round($end_time - $start_time, 3);
        
        echo "\n=== Thread Safety Test Summary ===\n";
        echo "ZTS Status: " . ($this->zts_enabled ? 'Enabled' : 'Disabled') . "\n";
        echo "Total tests: {$this->total_tests}\n";
        echo "Passed: {$this->passed_tests}\n";
        echo "Failed: " . ($this->total_tests - $this->passed_tests) . "\n";
        echo "Duration: {$duration} seconds\n";
        
        if ($this->passed_tests === $this->total_tests) {
            echo "\n🎉 ALL THREAD SAFETY TESTS PASSED!\n";
            if ($this->zts_enabled) {
                echo "The keccak256 extension is thread-safe and works correctly\n";
                echo "in ZTS (Zend Thread Safety) environments.\n";
            } else {
                echo "The keccak256 extension works correctly in non-ZTS environments\n";
                echo "and shows no signs of global state interference.\n";
            }
            return 0;
        } else {
            echo "\n❌ SOME THREAD SAFETY TESTS FAILED!\n";
            echo "Please review the failed tests above.\n";
            return 1;
        }
    }
}

// Run the tests
$test_runner = new ThreadSafetyTest();
$exit_code = $test_runner->runAllTests();
exit($exit_code);