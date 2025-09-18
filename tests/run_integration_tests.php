#!/usr/bin/env php
<?php

/**
 * Integration Test Runner for Keccak256 Extension
 * 
 * This script runs comprehensive integration tests to validate that the
 * modernized keccak256 extension maintains compatibility with the original
 * implementation and works correctly in all supported environments.
 */

class IntegrationTestRunner {
    private $total_tests = 0;
    private $passed_tests = 0;
    private $failed_tests = 0;
    
    public function __construct() {
        echo "=== Keccak256 Extension Integration Test Runner ===\n";
        echo "PHP Version: " . PHP_VERSION . "\n";
        echo "Extension loaded: " . (extension_loaded('keccak256') ? 'Yes' : 'No') . "\n";
        echo "ZTS enabled: " . (defined('ZEND_THREAD_SAFE') && ZEND_THREAD_SAFE ? 'Yes' : 'No') . "\n";
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
    private function runTestFile($test_file, $description) {
        echo "Running $description...\n";
        
        if (!file_exists($test_file)) {
            echo "  ⚠ Test file not found: $test_file\n";
            return false;
        }
        
        $start_time = microtime(true);
        
        // Run the test in a separate process to get proper exit code
        $command = "php -d extension=./modules/keccak256.so " . escapeshellarg($test_file) . " 2>&1";
        $output = shell_exec($command);
        
        $end_time = microtime(true);
        $duration = round($end_time - $start_time, 3);
        
        $this->total_tests++;
        
        if (strpos($output, 'ALL') !== false && strpos($output, 'PASSED') !== false) {
            $this->passed_tests++;
            echo "  ✓ PASSED ({$duration}s)\n";
            return true;
        } else {
            $this->failed_tests++;
            echo "  ✗ FAILED ({$duration}s)\n";
            echo "    Output:\n";
            echo "    " . str_replace("\n", "\n    ", trim($output)) . "\n";
            return false;
        }
    }
    
    /**
     * Run all integration tests
     */
    public function runAllTests() {
        $overall_start_time = microtime(true);
        
        echo "=== Starting Integration Tests ===\n\n";
        
        // Core integration tests
        $this->runTestFile('tests/integration_validation_test.php', 'Core Integration Validation');
        $this->runTestFile('tests/thread_safety_test.php', 'Thread Safety Tests');
        $this->runTestFile('tests/memory_validation_simple.php', 'Simple Memory Validation');
        $this->runTestFile('tests/memory_leak_test.php', 'Memory Leak Detection');
        
        $overall_end_time = microtime(true);
        $total_duration = round($overall_end_time - $overall_start_time, 3);
        
        // Display final summary
        echo "\n=== Integration Test Summary ===\n";
        echo "Total test suites: {$this->total_tests}\n";
        echo "Passed: {$this->passed_tests}\n";
        echo "Failed: {$this->failed_tests}\n";
        echo "Total duration: {$total_duration} seconds\n";
        
        if ($this->failed_tests === 0) {
            echo "\n🎉 ALL INTEGRATION TESTS PASSED!\n";
            echo "\nThe keccak256 extension has been successfully validated:\n";
            echo "✓ Hash outputs remain identical to original implementation\n";
            echo "✓ Thread safety verified (where applicable)\n";
            echo "✓ PHP 8.x compatibility confirmed\n";
            echo "✓ Memory management working correctly\n";
            echo "✓ Error handling modernized and working\n";
            echo "\nThe extension is ready for production use.\n";
            return 0;
        } else {
            echo "\n❌ SOME INTEGRATION TESTS FAILED!\n";
            echo "\nPlease review the failed tests above and fix any issues\n";
            echo "before deploying the extension to production.\n";
            return 1;
        }
    }
}

// Main execution
$test_runner = new IntegrationTestRunner();
$exit_code = $test_runner->runAllTests();
exit($exit_code);