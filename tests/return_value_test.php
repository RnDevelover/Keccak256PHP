#!/usr/bin/env php
<?php

/**
 * Return Value Test for Keccak256 Extension
 * 
 * Tests PHP 8 return value handling
 * 
 * Requirements tested:
 * - Modern PHP 8 return value handling
 * - Proper string type returns
 */

class ReturnValueTest {
    private $test_results = [];
    private $total_tests = 0;
    private $passed_tests = 0;
    
    public function __construct() {
        echo "=== Keccak256 Return Value Test ===\n";
        echo "PHP Version: " . PHP_VERSION . "\n";
        echo "Extension loaded: " . (extension_loaded('keccak256') ? 'Yes' : 'No') . "\n";
        
        if (!extension_loaded('keccak256')) {
            echo "ERROR: keccak256 extension not loaded!\n";
            exit(1);
        }
        
        echo "\n";
    }
    
    public function testReturnType() {
        echo "Testing return type...\n";
        
        $test_inputs = ['', '00', 'deadbeef', str_repeat('ab', 100)];
        
        foreach ($test_inputs as $input) {
            $this->runTest("Return type for '$input'", function() use ($input) {
                $result = keccak256($input);
                
                if (!is_string($result)) {
                    throw new Exception("Return value is not a string: " . gettype($result));
                }
                
                return "✓ Returns string";
            });
        }
    }
    
    public function testReturnFormat() {
        echo "\nTesting return format...\n";
        
        $this->runTest("Return length", function() {
            $inputs = ['', '00', 'deadbeef', str_repeat('ff', 1000)];
            
            foreach ($inputs as $input) {
                $result = keccak256($input);
                if (strlen($result) !== 64) {
                    throw new Exception("Invalid length for '$input': " . strlen($result));
                }
            }
            
            return "✓ All results are 64 characters";
        });
        
        $this->runTest("Hexadecimal format", function() {
            $inputs = ['', '00', 'deadbeef', 'ABCDEF'];
            
            foreach ($inputs as $input) {
                $result = keccak256($input);
                if (!ctype_xdigit($result)) {
                    throw new Exception("Non-hex characters in result for '$input'");
                }
            }
            
            return "✓ All results are hexadecimal";
        });
        
        $this->runTest("Lowercase format", function() {
            $inputs = ['00', 'DEADBEEF', 'AbCdEf'];
            
            foreach ($inputs as $input) {
                $result = keccak256($input);
                if ($result !== strtolower($result)) {
                    throw new Exception("Result not lowercase for '$input': $result");
                }
            }
            
            return "✓ All results are lowercase";
        });
    }
    
    public function testReturnConsistency() {
        echo "\nTesting return consistency...\n";
        
        $this->runTest("Deterministic results", function() {
            $input = 'deadbeef';
            $first_result = keccak256($input);
            
            for ($i = 0; $i < 100; $i++) {
                $result = keccak256($input);
                if ($result !== $first_result) {
                    throw new Exception("Non-deterministic result at iteration $i");
                }
            }
            
            return "✓ Results are deterministic";
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
        $this->testReturnType();
        $this->testReturnFormat();
        $this->testReturnConsistency();
        
        echo "\n=== Return Value Test Summary ===\n";
        echo "Total tests: {$this->total_tests}\n";
        echo "Passed: {$this->passed_tests}\n";
        echo "Failed: " . ($this->total_tests - $this->passed_tests) . "\n";
        
        if ($this->passed_tests === $this->total_tests) {
            echo "\n🎉 ALL RETURN VALUE TESTS PASSED!\n";
            return 0;
        } else {
            echo "\n❌ SOME RETURN VALUE TESTS FAILED!\n";
            return 1;
        }
    }
}

$test_runner = new ReturnValueTest();
$exit_code = $test_runner->runAllTests();
exit($exit_code);