#!/usr/bin/env php
<?php

/**
 * Edge Cases Test for Keccak256 Extension
 * 
 * Tests edge cases and boundary conditions
 * 
 * Requirements tested:
 * - 5.3: Various input size testing
 * - Edge cases and boundary conditions
 */

class EdgeCasesTest {
    private $test_results = [];
    private $total_tests = 0;
    private $passed_tests = 0;
    
    public function __construct() {
        echo "=== Keccak256 Edge Cases Test ===\n";
        echo "PHP Version: " . PHP_VERSION . "\n";
        echo "Extension loaded: " . (extension_loaded('keccak256') ? 'Yes' : 'No') . "\n";
        
        if (!extension_loaded('keccak256')) {
            echo "ERROR: keccak256 extension not loaded!\n";
            exit(1);
        }
        
        echo "\n";
    }
    
    public function testInputSizes() {
        echo "Testing various input sizes...\n";
        
        $this->runTest("Empty string", function() {
            $result = keccak256('');
            $expected = 'c5d2460186f7233c927e7db2dcc703c0e500b653ca82273b7bfad8045d85a470';
            
            if ($result !== $expected) {
                throw new Exception("Empty string hash incorrect");
            }
            
            return "✓ Empty string handled correctly";
        });
        
        $this->runTest("Single byte", function() {
            $result = keccak256('00');
            if (strlen($result) !== 64 || !ctype_xdigit($result)) {
                throw new Exception("Single byte result invalid");
            }
            
            return "✓ Single byte handled correctly";
        });
        
        $this->runTest("Very long input (10KB)", function() {
            $input = str_repeat('ab', 5120); // 10KB
            $result = keccak256($input);
            
            if (strlen($result) !== 64 || !ctype_xdigit($result)) {
                throw new Exception("Long input result invalid");
            }
            
            return "✓ Very long input handled correctly";
        });
    }
    
    public function testBoundaryConditions() {
        echo "\nTesting boundary conditions...\n";
        
        $this->runTest("All zeros", function() {
            $input = str_repeat('00', 100);
            $result = keccak256($input);
            
            if (strlen($result) !== 64 || !ctype_xdigit($result)) {
                throw new Exception("All zeros result invalid");
            }
            
            return "✓ All zeros handled correctly";
        });
        
        $this->runTest("All ones", function() {
            $input = str_repeat('ff', 100);
            $result = keccak256($input);
            
            if (strlen($result) !== 64 || !ctype_xdigit($result)) {
                throw new Exception("All ones result invalid");
            }
            
            return "✓ All ones handled correctly";
        });
        
        $this->runTest("Alternating pattern", function() {
            $input = str_repeat('a5', 100);
            $result = keccak256($input);
            
            if (strlen($result) !== 64 || !ctype_xdigit($result)) {
                throw new Exception("Alternating pattern result invalid");
            }
            
            return "✓ Alternating pattern handled correctly";
        });
    }
    
    public function testCaseSensitivity() {
        echo "\nTesting case sensitivity...\n";
        
        $this->runTest("Case insensitive input", function() {
            $lower = 'deadbeef';
            $upper = 'DEADBEEF';
            $mixed = 'DeAdBeEf';
            
            $result_lower = keccak256($lower);
            $result_upper = keccak256($upper);
            $result_mixed = keccak256($mixed);
            
            if ($result_lower !== $result_upper || $result_lower !== $result_mixed) {
                throw new Exception("Case sensitivity inconsistency");
            }
            
            return "✓ Input is case insensitive";
        });
        
        $this->runTest("Output is lowercase", function() {
            $inputs = ['DEADBEEF', 'AbCdEf', 'FF'];
            
            foreach ($inputs as $input) {
                $result = keccak256($input);
                if ($result !== strtolower($result)) {
                    throw new Exception("Output not lowercase for input: $input");
                }
            }
            
            return "✓ Output is always lowercase";
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
        $this->testInputSizes();
        $this->testBoundaryConditions();
        $this->testCaseSensitivity();
        
        echo "\n=== Edge Cases Test Summary ===\n";
        echo "Total tests: {$this->total_tests}\n";
        echo "Passed: {$this->passed_tests}\n";
        echo "Failed: " . ($this->total_tests - $this->passed_tests) . "\n";
        
        if ($this->passed_tests === $this->total_tests) {
            echo "\n🎉 ALL EDGE CASES TESTS PASSED!\n";
            return 0;
        } else {
            echo "\n❌ SOME EDGE CASES TESTS FAILED!\n";
            return 1;
        }
    }
}

$test_runner = new EdgeCasesTest();
$exit_code = $test_runner->runAllTests();
exit($exit_code);