#!/usr/bin/env php
<?php

/**
 * Simple Memory Validation Test for Keccak256 Extension
 * 
 * Simple memory leak detection without external tools
 * 
 * Requirements tested:
 * - 5.4: Memory leak detection
 * - Proper memory management
 */

class MemoryValidationSimpleTest {
    private $total_tests = 0;
    private $passed_tests = 0;
    
    public function __construct() {
        echo "=== Keccak256 Simple Memory Validation Test ===\n";
        echo "PHP Version: " . PHP_VERSION . "\n";
        echo "Extension loaded: " . (extension_loaded('keccak256') ? 'Yes' : 'No') . "\n";
        
        if (!extension_loaded('keccak256')) {
            echo "ERROR: keccak256 extension not loaded!\n";
            exit(1);
        }
        
        echo "\n";
    }
    
    public function testBasicMemoryUsage() {
        echo "Testing basic memory usage...\n";
        
        $this->runTest("Memory usage for single call", function() {
            $initial_memory = memory_get_usage();
            
            $result = keccak256('deadbeef');
            
            $final_memory = memory_get_usage();
            $memory_increase = $final_memory - $initial_memory;
            
            // Should not have significant permanent memory increase
            if ($memory_increase > 1024) { // 1KB threshold
                throw new Exception("Excessive memory increase: " . number_format($memory_increase) . " bytes");
            }
            
            return "✓ Memory usage acceptable (increase: " . number_format($memory_increase) . " bytes)";
        });
        
        $this->runTest("Memory usage for multiple calls", function() {
            $initial_memory = memory_get_usage();
            
            for ($i = 0; $i < 1000; $i++) {
                $result = keccak256(sprintf('%08x', $i));
            }
            
            $final_memory = memory_get_usage();
            $memory_increase = $final_memory - $initial_memory;
            
            // Should not have significant permanent memory increase
            if ($memory_increase > 10240) { // 10KB threshold
                throw new Exception("Excessive memory increase: " . number_format($memory_increase) . " bytes");
            }
            
            return "✓ Memory stable across multiple calls (increase: " . number_format($memory_increase) . " bytes)";
        });
    }
    
    public function testMemoryWithErrors() {
        echo "\nTesting memory usage with error conditions...\n";
        
        $this->runTest("Memory cleanup after errors", function() {
            $initial_memory = memory_get_usage();
            
            $invalid_inputs = ['abc', 'gg', 'hello', 'xyz', '12@34'];
            
            for ($i = 0; $i < 100; $i++) {
                foreach ($invalid_inputs as $input) {
                    try {
                        /** @noinspection PhpExpressionResultUnusedInspection */
                        keccak256($input);
                    } catch (InvalidArgumentException $e) {
                        // Expected
                    }
                }
                
                // Interleave with valid calls
                if ($i % 10 === 0) {
                    /** @noinspection PhpExpressionResultUnusedInspection */
                    keccak256('deadbeef');
                }
            }
            
            $final_memory = memory_get_usage();
            $memory_increase = $final_memory - $initial_memory;
            
            // Should not have significant permanent memory increase from errors
            // Allow for PHP exception handling overhead (~55 bytes per operation)
            if ($memory_increase > 100 * 1024) { // 100KB threshold for 500+ operations
                throw new Exception("Memory leak in error paths: " . number_format($memory_increase) . " bytes");
            }
            
            return "✓ Memory properly cleaned up after errors (increase: " . number_format($memory_increase) . " bytes)";
        });
    }
    
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
    
    public function runAllTests() {
        $this->testBasicMemoryUsage();
        $this->testMemoryWithErrors();
        
        echo "\n=== Simple Memory Validation Test Summary ===\n";
        echo "Total tests: {$this->total_tests}\n";
        echo "Passed: {$this->passed_tests}\n";
        echo "Failed: " . ($this->total_tests - $this->passed_tests) . "\n";
        
        if ($this->passed_tests === $this->total_tests) {
            echo "\n🎉 ALL SIMPLE MEMORY VALIDATION TESTS PASSED!\n";
            return 0;
        } else {
            echo "\n❌ SOME SIMPLE MEMORY VALIDATION TESTS FAILED!\n";
            return 1;
        }
    }
}

$test_runner = new MemoryValidationSimpleTest();
$exit_code = $test_runner->runAllTests();
exit($exit_code);