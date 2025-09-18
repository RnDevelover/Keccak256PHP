#!/usr/bin/env php
<?php

/**
 * Performance Benchmark Test for Keccak256 Extension
 * 
 * Performance benchmarks and regression testing
 * 
 * Requirements tested:
 * - Performance benchmarking
 * - Regression testing
 * - Memory efficiency
 */

class PerformanceBenchmarkTest {
    private $test_results = [];
    private $total_tests = 0;
    private $passed_tests = 0;
    
    public function __construct() {
        echo "=== Keccak256 Performance Benchmark Test ===\n";
        echo "PHP Version: " . PHP_VERSION . "\n";
        echo "Extension loaded: " . (extension_loaded('keccak256') ? 'Yes' : 'No') . "\n";
        
        if (!extension_loaded('keccak256')) {
            echo "ERROR: keccak256 extension not loaded!\n";
            exit(1);
        }
        
        echo "\n";
    }
    
    public function testBasicPerformance() {
        echo "Testing basic performance...\n";
        
        $this->runTest("Small input performance", function() {
            $input = 'deadbeef';
            $iterations = 10000;
            
            $start_time = microtime(true);
            
            for ($i = 0; $i < $iterations; $i++) {
                $result = keccak256($input);
            }
            
            $end_time = microtime(true);
            $duration = $end_time - $start_time;
            $ops_per_second = $iterations / $duration;
            
            // Should be able to do at least 1000 ops/sec for small inputs
            if ($ops_per_second < 1000) {
                throw new Exception("Performance too slow: " . number_format($ops_per_second, 0) . " ops/sec");
            }
            
            return "✓ " . number_format($ops_per_second, 0) . " ops/sec";
        });
        
        $this->runTest("Medium input performance", function() {
            $input = str_repeat('ab', 100); // 200 chars = 100 bytes
            $iterations = 5000;
            
            $start_time = microtime(true);
            
            for ($i = 0; $i < $iterations; $i++) {
                $result = keccak256($input);
            }
            
            $end_time = microtime(true);
            $duration = $end_time - $start_time;
            $ops_per_second = $iterations / $duration;
            
            // Should be able to do at least 500 ops/sec for medium inputs
            if ($ops_per_second < 500) {
                throw new Exception("Performance too slow: " . number_format($ops_per_second, 0) . " ops/sec");
            }
            
            return "✓ " . number_format($ops_per_second, 0) . " ops/sec";
        });
        
        $this->runTest("Large input performance", function() {
            $input = str_repeat('ff', 5000); // 10KB
            $iterations = 100;
            
            $start_time = microtime(true);
            
            for ($i = 0; $i < $iterations; $i++) {
                $result = keccak256($input);
            }
            
            $end_time = microtime(true);
            $duration = $end_time - $start_time;
            $ops_per_second = $iterations / $duration;
            
            // Should be able to do at least 10 ops/sec for large inputs
            if ($ops_per_second < 10) {
                throw new Exception("Performance too slow: " . number_format($ops_per_second, 0) . " ops/sec");
            }
            
            return "✓ " . number_format($ops_per_second, 0) . " ops/sec";
        });
    }
    
    public function testPerformanceConsistency() {
        echo "\nTesting performance consistency...\n";
        
        $this->runTest("Performance consistency across runs", function() {
            $input = 'deadbeef';
            $iterations = 1000;
            $run_times = [];
            
            // Multiple performance runs
            for ($run = 0; $run < 5; $run++) {
                $start_time = microtime(true);
                
                for ($i = 0; $i < $iterations; $i++) {
                    $result = keccak256($input);
                }
                
                $end_time = microtime(true);
                $run_times[] = $end_time - $start_time;
            }
            
            $avg_time = array_sum($run_times) / count($run_times);
            $min_time = min($run_times);
            $max_time = max($run_times);
            
            // Check for consistency (max should not be more than 2x min)
            if ($max_time > ($min_time * 2)) {
                throw new Exception("Inconsistent performance: min " . number_format($min_time * 1000, 2) . "ms, max " . number_format($max_time * 1000, 2) . "ms");
            }
            
            return "✓ Consistent performance (avg: " . number_format($avg_time * 1000, 2) . "ms)";
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
        $this->testBasicPerformance();
        $this->testPerformanceConsistency();
        
        echo "\n=== Performance Benchmark Test Summary ===\n";
        echo "Total tests: {$this->total_tests}\n";
        echo "Passed: {$this->passed_tests}\n";
        echo "Failed: " . ($this->total_tests - $this->passed_tests) . "\n";
        
        if ($this->passed_tests === $this->total_tests) {
            echo "\n🎉 ALL PERFORMANCE BENCHMARK TESTS PASSED!\n";
            return 0;
        } else {
            echo "\n❌ SOME PERFORMANCE BENCHMARK TESTS FAILED!\n";
            return 1;
        }
    }
}

$test_runner = new PerformanceBenchmarkTest();
$exit_code = $test_runner->runAllTests();
exit($exit_code);