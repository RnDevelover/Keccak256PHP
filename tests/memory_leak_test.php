#!/usr/bin/env php
<?php

/**
 * Memory Leak Test for Keccak256 Extension
 * 
 * Comprehensive memory leak detection test
 * 
 * Requirements tested:
 * - 5.4: Memory leak detection
 * - Comprehensive memory management validation
 */

class MemoryLeakTest {
    private $test_results = [];
    private $total_tests = 0;
    private $passed_tests = 0;
    
    public function __construct() {
        echo "=== Keccak256 Memory Leak Test ===\n";
        echo "PHP Version: " . PHP_VERSION . "\n";
        echo "Extension loaded: " . (extension_loaded('keccak256') ? 'Yes' : 'No') . "\n";
        
        if (!extension_loaded('keccak256')) {
            echo "ERROR: keccak256 extension not loaded!\n";
            exit(1);
        }
        
        echo "\n";
    }
    
    public function testRepeatedOperations() {
        echo "Testing repeated operations for memory leaks...\n";
        
        $this->runTest("Repeated identical operations", function() {
            $initial_memory = memory_get_usage();
            $input = 'deadbeef';
            
            for ($i = 0; $i < 10000; $i++) {
                $result = keccak256($input);
                
                // Periodically check memory growth
                if ($i % 1000 === 0 && $i > 0) {
                    $current_memory = memory_get_usage();
                    $growth = $current_memory - $initial_memory;
                    
                    if ($growth > 51200) { // 50KB threshold
                        throw new Exception("Memory leak detected at iteration $i: " . number_format($growth) . " bytes");
                    }
                }
            }
            
            $final_memory = memory_get_usage();
            $total_growth = $final_memory - $initial_memory;
            
            return "✓ No memory leak detected (growth: " . number_format($total_growth) . " bytes)";
        });
    }
    
    public function testErrorPathMemoryLeaks() {
        echo "\nTesting error path memory leaks...\n";
        
        $this->runTest("Repeated error conditions", function() {
            $initial_memory = memory_get_usage();
            
            $invalid_inputs = ['abc', 'gg', 'hello', 'xyz', '12@34', 'invalid_hex'];
            
            for ($i = 0; $i < 1000; $i++) {
                $input = $invalid_inputs[$i % count($invalid_inputs)];
                
                try {
                    /** @noinspection PhpExpressionResultUnusedInspection */
                    keccak256($input);
                    throw new Exception("Expected exception for: $input");
                } catch (InvalidArgumentException $e) {
                    // Expected
                }
                
                // Periodically check memory growth
                if ($i % 100 === 0 && $i > 0) {
                    $current_memory = memory_get_usage();
                    $growth = $current_memory - $initial_memory;
                    
                    if ($growth > 100 * 1024) { // 100KB threshold - allow for PHP exception overhead
                        throw new Exception("Memory leak in error paths at iteration $i: " . number_format($growth) . " bytes");
                    }
                }
            }
            
            $final_memory = memory_get_usage();
            $total_growth = $final_memory - $initial_memory;
            
            return "✓ No memory leak in error paths (growth: " . number_format($total_growth) . " bytes)";
        });
    }
    
    public function testMemoryRecovery() {
        echo "\nTesting memory recovery...\n";
        
        $this->runTest("Memory recovery after intensive operations", function() {
            $baseline_memory = memory_get_usage();
            
            // Intensive operations phase
            for ($i = 0; $i < 1000; $i++) {
                $large_input = str_repeat('ab', 1000 + ($i % 100)); // Variable size
                $result = keccak256($large_input);
            }
            
            $peak_memory = memory_get_usage();
            
            // Recovery phase - simple operations
            for ($i = 0; $i < 100; $i++) {
                $result = keccak256('00');
            }
            
            // Force garbage collection
            if (function_exists('gc_collect_cycles')) {
                gc_collect_cycles();
            }
            
            $recovery_memory = memory_get_usage();
            
            $peak_increase = $peak_memory - $baseline_memory;
            $final_increase = $recovery_memory - $baseline_memory;
            
            // Memory should not continue growing after intensive operations
            // PHP's memory allocator may retain memory for efficiency, which is normal
            // The key is that memory doesn't keep growing indefinitely
            if ($final_increase > ($peak_increase * 1.1)) { // Allow 10% additional growth
                throw new Exception("Memory continues growing after intensive operations: peak increase " . number_format($peak_increase) . " bytes, final increase " . number_format($final_increase) . " bytes");
            }
            
            // Also check that we're not using excessive memory overall
            if ($final_increase > 50 * 1024) { // 50KB threshold
                throw new Exception("Excessive memory usage after operations: " . number_format($final_increase) . " bytes");
            }
            
            return "✓ Memory stable after intensive operations (peak: " . number_format($peak_increase) . " bytes, final: " . number_format($final_increase) . " bytes)";
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
        $this->testRepeatedOperations();
        $this->testErrorPathMemoryLeaks();
        $this->testMemoryRecovery();
        
        echo "\n=== Memory Leak Test Summary ===\n";
        echo "Total tests: {$this->total_tests}\n";
        echo "Passed: {$this->passed_tests}\n";
        echo "Failed: " . ($this->total_tests - $this->passed_tests) . "\n";
        
        if ($this->passed_tests === $this->total_tests) {
            echo "\n🎉 ALL MEMORY LEAK TESTS PASSED!\n";
            return 0;
        } else {
            echo "\n❌ SOME MEMORY LEAK TESTS FAILED!\n";
            return 1;
        }
    }
}

$test_runner = new MemoryLeakTest();
$exit_code = $test_runner->runAllTests();
exit($exit_code);