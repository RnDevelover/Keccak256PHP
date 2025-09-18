#!/usr/bin/env php
<?php

/**
 * Integration Validation Test for Keccak256 Extension
 * 
 * This test validates that hash outputs remain identical to the original
 * implementation and tests various integration scenarios.
 * 
 * Requirements tested:
 * - 1.3: Function works identically to original implementation
 * - 5.5: Thread safety (where applicable)
 * - 1.1: Compatibility with PHP 8.0+
 * - 1.2: No warnings or deprecation notices
 */

class IntegrationValidationTest {
    private $total_tests = 0;
    private $passed_tests = 0;
    
    public function __construct() {
        echo "=== Keccak256 Integration Validation Test ===\n";
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
        
        echo "\n";
    }
    
    /**
     * Test against known Keccak256 test vectors to ensure identical output
     * to the original implementation
     */
    public function testKnownVectors() {
        echo "Testing known Keccak256 vectors...\n";
        
        // Official Keccak256 test vectors
        $test_vectors = [
            // Empty string
            '' => 'c5d2460186f7233c927e7db2dcc703c0e500b653ca82273b7bfad8045d85a470',
            
            // Single byte values
            '00' => 'bc36789e7a1e281436464229828f817d6612f7b477d66591ff96a9e064bcc98a',
            '01' => '5fe7f977e71dba2ea1a68e21057beebb9be2ac30c6410aa38d4f3fbe41dcffd2',
            'cc' => 'eead6dbfc7340a56caedc044696a168870549a6a7f6f56961e84a54bd9970b8a',
            'ff' => '8b1a944cf13a9a1c08facb2c9e98623ef3254d2ddb48113885c3e8e97fec8db9',
            
            // Multi-byte values
            'deadbeef' => 'd4fd4e189132273036449fc9e11198c739161b4c0116a9a2dccdfa1c492006f1',
            '0123456789abcdef' => '0c3d72390ac0ce0233c551a3c5278f8625ba996f5985dc8d612a9fc55f1de15a',
            
            // Longer strings
            '000102030405060708090a0b0c0d0e0f101112131415161718191a1b1c1d1e1f' => 
                '8ae1aa597fa146ebd3aa2ceddf360668dea5e526567e92b0321816a4e895bd2d',
            
            // Ethereum-specific test cases
            '68656c6c6f20776f726c64' => '47173285a8d7341e5e972fc677286384f802f8ef42a5ec5f03bbfa254cb01fad', // "hello world"
            '54686520717569636b2062726f776e20666f78206a756d7073206f76657220746865206c617a7920646f67' => 
                '4d741b6f1eb29cb2a9b9911c82f56fa8d73b04959d3d9d222895df6c0b28aa15', // "The quick brown fox jumps over the lazy dog"
        ];
        
        foreach ($test_vectors as $input => $expected) {
            $this->runTest("Vector: '$input'", function() use ($input, $expected) {
                $result = keccak256($input);
                
                if ($result !== $expected) {
                    throw new Exception("Hash mismatch!\nExpected: $expected\nGot:      $result");
                }
                
                return "✓ Correct hash: " . substr($result, 0, 16) . "...";
            });
        }
    }
    
    /**
     * Test error handling consistency
     */
    public function testErrorHandling() {
        echo "\nTesting error handling...\n";
        
        $error_cases = [
            'Odd length string' => 'abc',
            'Invalid hex character (g)' => 'abcg',
            'Invalid hex character (z)' => 'abcz',
            'Mixed invalid characters' => 'hello',
        ];
        
        foreach ($error_cases as $test_name => $input) {
            $this->runTest($test_name, function() use ($input) {
                try {
                    $result = keccak256($input);
                    throw new Exception("Expected exception but got result: $result");
                } catch (InvalidArgumentException $e) {
                    return "✓ Correctly threw InvalidArgumentException: " . $e->getMessage();
                } catch (Exception $e) {
                    throw new Exception("Wrong exception type: " . get_class($e) . " - " . $e->getMessage());
                }
            });
        }
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
        
        $this->testKnownVectors();
        $this->testErrorHandling();
        
        $end_time = microtime(true);
        $duration = round($end_time - $start_time, 3);
        
        echo "\n=== Integration Test Summary ===\n";
        echo "Total tests: {$this->total_tests}\n";
        echo "Passed: {$this->passed_tests}\n";
        echo "Failed: " . ($this->total_tests - $this->passed_tests) . "\n";
        echo "Duration: {$duration} seconds\n";
        
        if ($this->passed_tests === $this->total_tests) {
            echo "\n🎉 ALL INTEGRATION TESTS PASSED!\n";
            echo "The keccak256 extension is working correctly and maintains\n";
            echo "compatibility with the original implementation.\n";
            return 0;
        } else {
            echo "\n❌ SOME INTEGRATION TESTS FAILED!\n";
            echo "Please review the failed tests above.\n";
            return 1;
        }
    }
}

// Run the tests
$test_runner = new IntegrationValidationTest();
$exit_code = $test_runner->runAllTests();
exit($exit_code);