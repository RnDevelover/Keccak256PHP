#!/usr/bin/env php
<?php

/**
 * Parameter Validation Test for Keccak256 Extension
 * 
 * Tests parameter parsing and validation
 * 
 * Requirements tested:
 * - 5.2: Error condition testing
 */

class ParameterValidationTest {
    private $test_results = [];
    private $total_tests = 0;
    private $passed_tests = 0;
    
    public function __construct() {
        echo "=== Keccak256 Parameter Validation Test ===\n";
        echo "PHP Version: " . PHP_VERSION . "\n";
        echo "Extension loaded: " . (extension_loaded('keccak256') ? 'Yes' : 'No') . "\n";
        
        if (!extension_loaded('keccak256')) {
            echo "ERROR: keccak256 extension not loaded!\n";
            exit(1);
        }
        
        echo "\n";
    }
    
    public function testValidInputs() {
        echo "Testing valid inputs...\n";
        
        $valid_inputs = [
            '' => 'Empty string',
            '00' => 'Single byte',
            'deadbeef' => 'Multi-byte',
            'DEADBEEF' => 'Uppercase hex',
            'DeAdBeEf' => 'Mixed case hex',
            str_repeat('ab', 1000) => 'Very long input',
        ];
        
        foreach ($valid_inputs as $input => $description) {
            $this->runTest("Valid: $description", function() use ($input) {
                $result = keccak256($input);
                
                if (strlen($result) !== 64) {
                    throw new Exception("Invalid result length: " . strlen($result));
                }
                
                if (!ctype_xdigit($result)) {
                    throw new Exception("Result contains non-hex characters");
                }
                
                if ($result !== strtolower($result)) {
                    throw new Exception("Result not lowercase");
                }
                
                return "✓ Valid result format";
            });
        }
    }
    
    public function testInvalidInputs() {
        echo "\nTesting invalid inputs...\n";
        
        $invalid_inputs = [
            'abc' => 'Odd length string',
            'gg' => 'Invalid hex character (g)',
            'hello' => 'Non-hex string',
            'ab@cd' => 'Special characters',
            'abcü' => 'Unicode characters',
            '12 34' => 'Space in string',
            'ab\ncd' => 'Newline in string',
        ];
        
        foreach ($invalid_inputs as $input => $description) {
            $this->runTest("Invalid: $description", function() use ($input) {
                try {
                    $result = keccak256($input);
                    throw new Exception("Expected exception but got result: $result");
                } catch (InvalidArgumentException $e) {
                    return "✓ Correctly threw InvalidArgumentException: " . $e->getMessage();
                } catch (Exception $e) {
                    throw new Exception("Wrong exception type: " . get_class($e));
                }
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
        $this->testValidInputs();
        $this->testInvalidInputs();
        
        echo "\n=== Parameter Validation Test Summary ===\n";
        echo "Total tests: {$this->total_tests}\n";
        echo "Passed: {$this->passed_tests}\n";
        echo "Failed: " . ($this->total_tests - $this->passed_tests) . "\n";
        
        if ($this->passed_tests === $this->total_tests) {
            echo "\n🎉 ALL PARAMETER VALIDATION TESTS PASSED!\n";
            return 0;
        } else {
            echo "\n❌ SOME PARAMETER VALIDATION TESTS FAILED!\n";
            return 1;
        }
    }
}

$test_runner = new ParameterValidationTest();
$exit_code = $test_runner->runAllTests();
exit($exit_code);