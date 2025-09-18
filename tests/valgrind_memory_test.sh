#!/bin/bash

# Valgrind Memory Leak Detection Script for Keccak256 Extension
# 
# This script runs comprehensive memory leak detection using Valgrind
# for the modernized keccak256 PHP extension.
#
# Usage: ./tests/valgrind_memory_test.sh [test_file]
#
# Requirements:
# - Valgrind must be installed
# - PHP must be compiled with debug symbols for best results

set -e

# Configuration
VALGRIND_OPTS="--tool=memcheck --leak-check=full --show-leak-kinds=all --track-origins=yes --verbose --log-file=valgrind_output.log"
PHP_OPTS="-d extension=keccak256.so"
TEST_DIR="tests"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}=== Keccak256 Extension Valgrind Memory Test ===${NC}"
echo

# Check if Valgrind is available
if ! command -v valgrind &> /dev/null; then
    echo -e "${RED}ERROR: Valgrind is not installed or not in PATH${NC}"
    echo "Please install Valgrind to run memory leak detection tests."
    echo "On Ubuntu/Debian: sudo apt-get install valgrind"
    echo "On CentOS/RHEL: sudo yum install valgrind"
    echo "On macOS: brew install valgrind"
    exit 1
fi

# Check if PHP is available
if ! command -v php &> /dev/null; then
    echo -e "${RED}ERROR: PHP is not installed or not in PATH${NC}"
    exit 1
fi

echo "Valgrind version: $(valgrind --version)"
echo "PHP version: $(php -v | head -n1)"
echo

# Test files to run with Valgrind
if [ $# -eq 1 ]; then
    # Single test file specified
    TEST_FILES=("$1")
else
    # Default test files for memory leak detection
    TEST_FILES=(
        "$TEST_DIR/memory_leak_test.php"
        "$TEST_DIR/memory_validation_simple.php"
        "$TEST_DIR/known_vectors_test.php"
        "$TEST_DIR/parameter_validation_test.php"
        "$TEST_DIR/error_handling_test.php"
    )
fi

# Function to run a single test with Valgrind
run_valgrind_test() {
    local test_file="$1"
    local test_name=$(basename "$test_file" .php)
    local log_file="valgrind_${test_name}.log"
    
    echo -e "${BLUE}Running Valgrind test: $test_name${NC}"
    echo "Test file: $test_file"
    echo "Log file: $log_file"
    
    if [ ! -f "$test_file" ]; then
        echo -e "${YELLOW}WARNING: Test file $test_file not found, skipping${NC}"
        return 1
    fi
    
    # Run the test with Valgrind
    echo "Executing: valgrind $VALGRIND_OPTS --log-file=$log_file php $PHP_OPTS $test_file"
    
    if valgrind $VALGRIND_OPTS --log-file="$log_file" php $PHP_OPTS "$test_file" > /dev/null 2>&1; then
        echo -e "${GREEN}✓ Test execution completed${NC}"
    else
        echo -e "${RED}✗ Test execution failed${NC}"
        return 1
    fi
    
    # Analyze the Valgrind output
    if [ -f "$log_file" ]; then
        echo "Analyzing Valgrind output..."
        
        # Check for memory leaks
        local definitely_lost=$(grep "definitely lost:" "$log_file" | tail -n1 | awk '{print $4}')
        local indirectly_lost=$(grep "indirectly lost:" "$log_file" | tail -n1 | awk '{print $4}')
        local possibly_lost=$(grep "possibly lost:" "$log_file" | tail -n1 | awk '{print $4}')
        local still_reachable=$(grep "still reachable:" "$log_file" | tail -n1 | awk '{print $4}')
        
        echo "Memory leak summary:"
        echo "  Definitely lost: ${definitely_lost:-0} bytes"
        echo "  Indirectly lost: ${indirectly_lost:-0} bytes"
        echo "  Possibly lost: ${possibly_lost:-0} bytes"
        echo "  Still reachable: ${still_reachable:-0} bytes"
        
        # Check for errors
        local error_count=$(grep -c "ERROR SUMMARY:" "$log_file" || echo "0")
        local errors=$(grep "ERROR SUMMARY:" "$log_file" | tail -n1 | awk '{print $4}' || echo "0")
        
        echo "Error summary: ${errors:-0} errors"
        
        # Determine result
        if [ "${definitely_lost:-0}" = "0" ] && [ "${indirectly_lost:-0}" = "0" ] && [ "${errors:-0}" = "0" ]; then
            echo -e "${GREEN}✓ PASS: No memory leaks or errors detected${NC}"
            return 0
        else
            echo -e "${RED}✗ FAIL: Memory leaks or errors detected${NC}"
            
            # Show relevant parts of the log
            echo "Relevant Valgrind output:"
            grep -A5 -B5 "definitely lost\|indirectly lost\|ERROR SUMMARY" "$log_file" | head -n20
            
            return 1
        fi
    else
        echo -e "${RED}✗ ERROR: Valgrind log file not created${NC}"
        return 1
    fi
}

# Function to clean up log files
cleanup_logs() {
    echo "Cleaning up old Valgrind log files..."
    rm -f valgrind_*.log
}

# Main execution
echo "Starting Valgrind memory leak detection tests..."
echo "This may take several minutes depending on the number of tests."
echo

# Clean up old logs
cleanup_logs

# Track results
total_tests=0
passed_tests=0
failed_tests=0

# Run each test
for test_file in "${TEST_FILES[@]}"; do
    echo
    echo "----------------------------------------"
    
    total_tests=$((total_tests + 1))
    
    if run_valgrind_test "$test_file"; then
        passed_tests=$((passed_tests + 1))
    else
        failed_tests=$((failed_tests + 1))
    fi
    
    echo "----------------------------------------"
done

# Final summary
echo
echo -e "${BLUE}=== Valgrind Test Summary ===${NC}"
echo "Total tests: $total_tests"
echo -e "Passed: ${GREEN}$passed_tests${NC}"
echo -e "Failed: ${RED}$failed_tests${NC}"

if [ $failed_tests -eq 0 ]; then
    echo
    echo -e "${GREEN}🎉 ALL VALGRIND TESTS PASSED!${NC}"
    echo "No memory leaks detected in the keccak256 extension."
    exit_code=0
else
    echo
    echo -e "${RED}❌ SOME VALGRIND TESTS FAILED!${NC}"
    echo "Memory leaks or errors were detected. Please review the log files:"
    ls -la valgrind_*.log 2>/dev/null || echo "No log files found"
    exit_code=1
fi

echo
echo "Valgrind log files have been preserved for detailed analysis."
echo "To view a specific log: less valgrind_[test_name].log"
echo "To clean up logs: rm valgrind_*.log"

exit $exit_code