#!/usr/bin/env php
<?php

/**
 * Comprehensive Validation Test for Keccak256 Extension
 * 
 * Comprehensive parameter validation test suite
 * 
 * Requirements tested:
 * - All parameter validation scenarios
 * - Edge cases and boundary conditions
 * - Error handling validation
 */

class ComprehensiveValidationTest {
    private $test_results = [];
    private $total_tests = 0;
    private $passed_tests = 0;
    
    public function __construct() {
        echo "=== Keccak256 Comprehensive Validation Test ===\n";
        echo "PHP Version: " . PHP_VERSION . "\n";
        echo "Extension loaded: " . (extension_loaded('keccak256') ? 'Yes' : 'No') . "\n";
        
        if (!extension_loaded('keccak256')) {
            echo "ERROR: keccak256 extension not loaded!\n";
            exit(1);
        }
        
        echo "\n";
    }
    
    public function testAllValidInputFormats() {
        echo "Testing all valid input formats...\n";
        
        $valid_cases = [
            // Basic cases
            '' => 'Empty string',
            '00' => 'Single zero byte',
            'ff' => 'Single max byte',
            
            // Case variations
            'deadbeef' => 'Lowercase hex',
            'DEADBEEF' => 'Uppercase hex',
            'DeAdBeEf' => 'Mixed case hex',
            'aBcDeF12' => 'Mixed case with numbers',
            
            // Length variations
            str_repeat('ab', 2) => 'Four characters',
            str_repeat('cd', 10) => 'Twenty characters',
            str_repeat('ef', 100) => 'Two hundred characters',
            
            // Number patterns
            '0123456789abcdef' => 'Sequential hex digits',
            '1111111111111111' => 'Repeated digit',
            '0000000000000000' => 'All zeros',
            'ffffffffffffffff' => 'All max values',
        ];
        
        foreach ($valid_cases as $input => $description) {
            $this->runTest("Valid: $description", function() use ($input) {
                $result = keccak256($input);
                
                // Validate result format
                if (strlen($result) !== 64) {
                    throw new Exception("Invalid result length: " . strlen($result));
                }
                
                if (!ctype_xdigit($result)) {
                    throw new Exception("Result contains non-hex characters");
                }
                
                if ($result !== strtolower($result)) {
                    throw new Exception("Result not lowercase");
                }
                
                // Verify deterministic
                $result2 = keccak256($input);
                if ($result !== $result2) {
                    throw new Exception("Non-deterministic result");
                }
                
                return "✓ Valid result format and deterministic";
            });
        }
    }
    
    public function testAllInvalidInputFormats() {
        echo "\nTesting all invalid input formats...\n";
        
        $invalid_cases = [
            // Length issues
            'a' => 'Odd length (1 char)',
            'abc' => 'Odd length (3 chars)',
            'abcde' => 'Odd length (5 chars)',
            
            // Invalid characters
            'gg' => 'Invalid hex character g',
            'hh' => 'Invalid hex character h',
            'zz' => 'Invalid hex character z',
            
            // Special characters
            'ab cd' => 'Space character',
            'ab\tcd' => 'Tab character',
            'ab\ncd' => 'Newline character',
            'ab@cd' => 'At symbol',
            'ab#cd' => 'Hash symbol',
            'ab$cd' => 'Dollar symbol',
            'ab%cd' => 'Percent symbol',
            'ab&cd' => 'Ampersand',
            'ab*cd' => 'Asterisk',
            'ab+cd' => 'Plus symbol',
            'ab-cd' => 'Minus symbol',
            'ab=cd' => 'Equals symbol',
            
            // Unicode characters
            'abücd' => 'Unicode character ü',
            'abñcd' => 'Unicode character ñ',
            'ab€cd' => 'Unicode character €',
            
            // Mixed invalid
            'hello world' => 'English text',
            '12.34' => 'Decimal number',
            '-abcd' => 'Negative sign',
            '+abcd' => 'Plus sign',
        ];
        
        foreach ($invalid_cases as $input => $description) {
            $this->runTest("Invalid: $description", function() use ($input) {
                try {
                    $result = keccak256($input);
                    throw new Exception("Expected exception but got result: $result");
                } catch (InvalidArgumentException $e) {
                    // Verify error message is descriptive
                    $message = $e->getMessage();
                    if (strlen($message) < 10) {
                        throw new Exception("Error message too short: '$message'");
                    }
                    
                    return "✓ Correctly threw InvalidArgumentException";
                } catch (Exception $e) {
                    throw new Exception("Wrong exception type: " . get_class($e) . " - " . $e->getMessage());
                }
            });
        }
    }
    
    public function testBoundaryConditions() {
        echo "\nTesting boundary conditions...\n";
        
        $this->runTest("Empty string", function() {
            $result = keccak256('');
            $expected = 'c5d2460186f7233c927e7db2dcc703c0e500b653ca82273b7bfad8045d85a470';
            
            if ($result !== $expected) {
                throw new Exception("Empty string hash incorrect");
            }
            
            return "✓ Empty string handled correctly";
        });
        
        $this->runTest("Very large input", function() {
            $large_input = str_repeat('ab', 10000); // 20KB
            $result = keccak256($large_input);
            
            if (strlen($result) !== 64 || !ctype_xdigit($result)) {
                throw new Exception("Large input result invalid");
            }
            
            return "✓ Very large input handled correctly";
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
        $this->testAllValidInputFormats();
        $this->testAllInvalidInputFormats();
        $this->testBoundaryConditions();
        
        echo "\n=== Comprehensive Validation Test Summary ===\n";
        echo "Total tests: {$this->total_tests}\n";
        echo "Passed: {$this->passed_tests}\n";
        echo "Failed: " . ($this->total_tests - $this->passed_tests) . "\n";
        
        if ($this->passed_tests === $this->total_tests) {
            echo "\n🎉 ALL COMPREHENSIVE VALIDATION TESTS PASSED!\n";
            return 0;
        } else {
            echo "\n❌ SOME COMPREHENSIVE VALIDATION TESTS FAILED!\n";
            return 1;
        }
    }
}

$test_runner = new ComprehensiveValidationTest();
$exit_code = $test_runner->runAllTests();
exit($exit_code);