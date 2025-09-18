#!/usr/bin/env php
<?php

/**
 * Error Handling Test for Keccak256 Extension
 * 
 * Tests error handling and exception throwing
 * 
 * Requirements tested:
 * - 5.2: Error condition testing
 * - Modern PHP 8 error handling
 */

class ErrorHandlingTest {
    private $test_results = [];
    private $total_tests = 0;
    private $passed_tests = 0;
    
    public function __construct() {
        echo "=== Keccak256 Error Handling Test ===\n";
        echo "PHP Version: " . PHP_VERSION . "\n";
        echo "Extension loaded: " . (extension_loaded('keccak256') ? 'Yes' : 'No') . "\n";
        
        if (!extension_loaded('keccak256')) {
            echo "ERROR: keccak256 extension not loaded!\n";
            exit(1);
        }
        
        echo "\n";
    }
    
    public function testExceptionTypes() {
        echo "Testing exception types...\n";
        
        $error_cases = [
            'abc' => 'Odd length string',
            'gg' => 'Invalid hex character',
            'hello world' => 'Non-hex with space',
            '12@34' => 'Special character',
        ];
        
        foreach ($error_cases as $input => $description) {
            $this->runTest("Exception type: $description", function() use ($input) {
                try {
                    /** @noinspection PhpExpressionResultUnusedInspection */
                    keccak256($input);
                    throw new Exception("Expected exception but function succeeded");
                } catch (InvalidArgumentException $e) {
                    return "✓ Correctly threw InvalidArgumentException";
                } catch (TypeError $e) {
                    throw new Exception("Unexpected TypeError: " . $e->getMessage());
                } catch (Exception $e) {
                    throw new Exception("Wrong exception type: " . get_class($e));
                }
            });
        }
    }
    
    public function testErrorMessages() {
        echo "\nTesting error messages...\n";
        
        $this->runTest("Odd length error message", function() {
            try {
                /** @noinspection PhpExpressionResultUnusedInspection */
                keccak256('abc');
                throw new Exception("Expected exception");
            } catch (InvalidArgumentException $e) {
                $message = $e->getMessage();
                if (strpos($message, 'even-length') !== false || strpos($message, 'odd') !== false) {
                    return "✓ Descriptive error message: $message";
                } else {
                    throw new Exception("Error message not descriptive enough: $message");
                }
            }
        });
        
        $this->runTest("Invalid hex error message", function() {
            try {
                /** @noinspection PhpExpressionResultUnusedInspection */
                keccak256('gg');
                throw new Exception("Expected exception");
            } catch (InvalidArgumentException $e) {
                $message = $e->getMessage();
                if (strpos($message, 'hex') !== false || strpos($message, 'character') !== false) {
                    return "✓ Descriptive error message: $message";
                } else {
                    throw new Exception("Error message not descriptive enough: $message");
                }
            }
        });
    }
    
    public function testErrorRecovery() {
        echo "\nTesting error recovery...\n";
        
        $this->runTest("Function works after error", function() {
            // Cause an error
            try {
                /** @noinspection PhpExpressionResultUnusedInspection */
                keccak256('invalid');
            } catch (InvalidArgumentException $e) {
                // Expected
            }
            
            // Function should still work
            $result = keccak256('deadbeef');
            $expected = 'd4fd4e189132273036449fc9e11198c739161b4c0116a9a2dccdfa1c492006f1';
            
            if ($result !== $expected) {
                throw new Exception("Function corrupted after error");
            }
            
            return "✓ Function works correctly after error";
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
        $this->testExceptionTypes();
        $this->testErrorMessages();
        $this->testErrorRecovery();
        
        echo "\n=== Error Handling Test Summary ===\n";
        echo "Total tests: {$this->total_tests}\n";
        echo "Passed: {$this->passed_tests}\n";
        echo "Failed: " . ($this->total_tests - $this->passed_tests) . "\n";
        
        if ($this->passed_tests === $this->total_tests) {
            echo "\n🎉 ALL ERROR HANDLING TESTS PASSED!\n";
            return 0;
        } else {
            echo "\n❌ SOME ERROR HANDLING TESTS FAILED!\n";
            return 1;
        }
    }
}

$test_runner = new ErrorHandlingTest();
$exit_code = $test_runner->runAllTests();
exit($exit_code);