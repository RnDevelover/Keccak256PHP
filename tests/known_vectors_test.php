#!/usr/bin/env php
<?php

/**
 * Known Vectors Test for Keccak256 Extension
 * 
 * Tests against known Keccak256 test vectors to ensure correctness
 * 
 * Requirements tested:
 * - 5.1: Known test vector validation
 */

class KnownVectorsTest {
    private $test_results = [];
    private $total_tests = 0;
    private $passed_tests = 0;
    
    // Known Keccak256 test vectors
    private $test_vectors = [
        // Empty string
        '' => 'c5d2460186f7233c927e7db2dcc703c0e500b653ca82273b7bfad8045d85a470',
        
        // Single bytes
        '00' => 'bc36789e7a1e281436464229828f817d6612f7b477d66591ff96a9e064bcc98a',
        '01' => '5fe7f977e71dba2ea1a68e21057beebb9be2ac30c6410aa38d4f3fbe41dcffd2',
        'cc' => 'eead6dbfc7340a56caedc044696a168870549a6a7f6f56961e84a54bd9970b8a',
        'ff' => '8b1a944cf13a9a1c08facb2c9e98623ef3254d2ddb48113885c3e8e97fec8db9',
        
        // Multi-byte values
        'deadbeef' => 'd4fd4e189132273036449fc9e11198c739161b4c0116a9a2dccdfa1c492006f1',
        'cafebabe' => '6fe2683bd1d27cbb7a05c570693bea39e0c082ed16722e5ecadcfb7cfdbd20db',
        
        // Ethereum test cases
        '68656c6c6f20776f726c64' => '47173285a8d7341e5e972fc677286384f802f8ef42a5ec5f03bbfa254cb01fad', // "hello world"
        '54686520717569636b2062726f776e20666f78206a756d7073206f76657220746865206c617a7920646f67' => 
            '4d741b6f1eb29cb2a9b9911c82f56fa8d73b04959d3d9d222895df6c0b28aa15', // "The quick brown fox jumps over the lazy dog"
    ];
    
    public function __construct() {
        echo "=== Keccak256 Known Vectors Test ===\n";
        echo "PHP Version: " . PHP_VERSION . "\n";
        echo "Extension loaded: " . (extension_loaded('keccak256') ? 'Yes' : 'No') . "\n";
        
        if (!extension_loaded('keccak256')) {
            echo "ERROR: keccak256 extension not loaded!\n";
            exit(1);
        }
        
        echo "\n";
    }
    
    public function testKnownVectors() {
        echo "Testing known Keccak256 vectors...\n";
        
        foreach ($this->test_vectors as $input => $expected) {
            $this->runTest("Vector: " . ($input === '' ? '(empty)' : $input), function() use ($input, $expected) {
                $result = keccak256($input);
                
                if ($result !== $expected) {
                    throw new Exception("Hash mismatch!\nInput: '$input'\nExpected: $expected\nGot: $result");
                }
                
                return "✓ Correct hash";
            });
        }
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
        $this->testKnownVectors();
        
        echo "\n=== Known Vectors Test Summary ===\n";
        echo "Total tests: {$this->total_tests}\n";
        echo "Passed: {$this->passed_tests}\n";
        echo "Failed: " . ($this->total_tests - $this->passed_tests) . "\n";
        
        if ($this->passed_tests === $this->total_tests) {
            echo "\n🎉 ALL KNOWN VECTOR TESTS PASSED!\n";
            return 0;
        } else {
            echo "\n❌ SOME KNOWN VECTOR TESTS FAILED!\n";
            return 1;
        }
    }
}

$test_runner = new KnownVectorsTest();
$exit_code = $test_runner->runAllTests();
exit($exit_code);