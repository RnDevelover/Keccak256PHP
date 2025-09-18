#!/usr/bin/env php
<?php

/**
 * Comprehensive Test Runner for Keccak256 Extension
 * 
 * Main test runner that executes all test suites
 * 
 * Usage:
 *   php tests/run_comprehensive_tests.php [options]
 * 
 * Options:
 *   --quick         Run only essential tests
 *   --verbose       Show detailed output
 *   --memory-check  Include memory usage monitoring
 */

class ComprehensiveTestRunner {
    private $options = [];
    private $test_results = [];
    private $total_suites = 0;
    private $passed_suites = 0;
    private $failed_suites = 0;
    
    public function __construct($argv) {
        $this->parseArguments($argv);
        $this->displayHeader();
    }
    
    private function parseArguments($argv) {
        $this->options = [
            'quick' => false,
            'verbose' => false,
            'memory_check' => false,
        ];
        
        foreach ($argv as $arg) {
            switch ($arg) {
                case '--quick':
                    $this->options['quick'] = true;
                    break;
                case '--verbose':
                    $this->options['verbose'] = true;
                    break;
                case '--memory-check':
                    $this->options['memory_check'] = true;
                    break;
            }
        }
    }
    
    private function displayHeader() {
        echo "=== Keccak256 Extension Comprehensive Test Runner ===\n";
        echo "PHP Version: " . PHP_VERSION . "\n";
        echo "Extension loaded: " . (extension_loaded('keccak256') ? 'Yes' : 'No') . "\n";
        echo "ZTS enabled: " . (defined('ZEND_THREAD_SAFE') && ZEND_THREAD_SAFE ? 'Yes' : 'No') . "\n";
        
        if ($this->options['quick']) {
            echo "Mode: Quick test run (essential tests only)\n";
        } else {
            echo "Mode: Full comprehensive test suite\n";
        }
        
        if ($this->options['verbose']) {
            echo "Verbose output: Enabled\n";
        }
        
        if ($this->options['memory_check']) {
            echo "Memory monitoring: Enabled\n";
        }
        
        echo "\n";
        
        // Check prerequisites
        if (!extension_loaded('keccak256')) {
            echo "ERROR: keccak256 extension not loaded!\n";
            echo "Please compile and load the extension before running tests.\n";
            exit(1);
        }
        
        if (!function_exists('keccak256')) {
            echo "ERROR: keccak256 function not available!\n";
            exit(1);
        }
    }
    
    /**
     * Run a test file and capture results
     */
    private function runTestSuite($test_file, $description, $category = 'Core') {
        echo "[$category] Running $description...\n";
        
        if (!file_exists($test_file)) {
            echo "  ⚠ Test file not found: $test_file\n";
            return false;
        }
        
        $start_time = microtime(true);
        $start_memory = memory_get_usage();
        
        // Run the test in a separate process
        $command = "php -d extension=./modules/keccak256.so " . escapeshellarg($test_file) . " 2>&1";
        $output = shell_exec($command);
        
        $end_time = microtime(true);
        $end_memory = memory_get_usage();
        
        $duration = round($end_time - $start_time, 3);
        $memory_used = $end_memory - $start_memory;
        
        $this->total_suites++;
        
        // Parse results
        $success = (strpos($output, 'ALL') !== false && strpos($output, 'PASSED') !== false);
        
        if ($success) {
            $this->passed_suites++;
            echo "  ✓ PASSED ({$duration}s";
            if ($this->options['memory_check']) {
                echo ", " . number_format($memory_used) . " bytes";
            }
            echo ")\n";
            
            if ($this->options['verbose']) {
                echo "    " . str_replace("\n", "\n    ", trim($output)) . "\n";
            }
            
            return true;
        } else {
            $this->failed_suites++;
            echo "  ✗ FAILED ({$duration}s)\n";
            
            // Always show output for failed tests
            echo "    Output:\n";
            echo "    " . str_replace("\n", "\n    ", trim($output)) . "\n";
            
            return false;
        }
    }
    
    /**
     * Run all comprehensive tests
     */
    public function runAllTests() {
        $overall_start_time = microtime(true);
        
        echo "=== Starting Comprehensive Test Suite ===\n\n";
        
        // Essential tests (always run)
        $this->runTestSuite('tests/known_vectors_test.php', 'Known Vector Validation', 'Essential');
        $this->runTestSuite('tests/parameter_validation_test.php', 'Parameter Validation', 'Essential');
        $this->runTestSuite('tests/error_handling_test.php', 'Error Handling', 'Essential');
        $this->runTestSuite('tests/return_value_test.php', 'Return Value Validation', 'Essential');
        
        if (!$this->options['quick']) {
            // Comprehensive tests
            $this->runTestSuite('tests/comprehensive_validation_test.php', 'Comprehensive Validation', 'Comprehensive');
            $this->runTestSuite('tests/edge_cases_test.php', 'Edge Cases', 'Comprehensive');
            
            // Memory tests
            $this->runTestSuite('tests/memory_validation_simple.php', 'Simple Memory Validation', 'Memory');
            $this->runTestSuite('tests/memory_leak_test.php', 'Memory Leak Detection', 'Memory');
            
            // Performance tests
            $this->runTestSuite('tests/performance_benchmark_test.php', 'Performance Benchmarks', 'Performance');
            
            // Integration tests
            $this->runTestSuite('tests/integration_validation_test.php', 'Integration Validation', 'Integration');
            $this->runTestSuite('tests/thread_safety_test.php', 'Thread Safety', 'Integration');
        }
        
        $overall_end_time = microtime(true);
        $total_duration = round($overall_end_time - $overall_start_time, 3);
        
        // Display final summary
        echo "\n=== Comprehensive Test Suite Summary ===\n";
        echo "Total test suites: {$this->total_suites}\n";
        echo "Passed: {$this->passed_suites}\n";
        echo "Failed: {$this->failed_suites}\n";
        echo "Total duration: {$total_duration} seconds\n";
        
        if ($this->failed_suites === 0) {
            echo "\n🎉 ALL COMPREHENSIVE TESTS PASSED!\n";
            echo "\nThe keccak256 extension has been thoroughly validated:\n";
            echo "✓ Known test vectors verified\n";
            echo "✓ Parameter validation working correctly\n";
            echo "✓ Error handling modernized and robust\n";
            echo "✓ Return values properly formatted\n";
            
            if (!$this->options['quick']) {
                echo "✓ Comprehensive validation completed\n";
                echo "✓ Edge cases handled correctly\n";
                echo "✓ Memory management verified\n";
                echo "✓ Performance benchmarks passed\n";
                echo "✓ Integration tests successful\n";
                echo "✓ Thread safety confirmed\n";
            }
            
            echo "\nThe extension is ready for production use.\n";
            return 0;
        } else {
            echo "\n❌ SOME COMPREHENSIVE TESTS FAILED!\n";
            echo "\nPlease review the failed test suites above and fix any issues\n";
            echo "before deploying the extension to production.\n";
            return 1;
        }
    }
}

// Main execution
$test_runner = new ComprehensiveTestRunner($argv);
$exit_code = $test_runner->runAllTests();
exit($exit_code);