# Keccak256 Extension Test Suite

This directory contains a comprehensive test suite for the modernized Keccak256 PHP extension. The tests validate all aspects of the extension including functionality, performance, memory management, and error handling.

## Test Files Overview

### Core Functionality Tests

- **`known_vectors_test.php`** - Tests against known Keccak256 test vectors to ensure correctness
- **`parameter_validation_test.php`** - Tests parameter parsing and validation
- **`comprehensive_validation_test.php`** - Comprehensive parameter validation test suite
- **`error_handling_test.php`** - Tests error handling and exception throwing
- **`return_value_test.php`** - Tests PHP 8 return value handling

### Edge Cases and Boundary Tests

- **`edge_cases_test.php`** - Tests edge cases and boundary conditions

### Memory Management Tests

- **`memory_validation_simple.php`** - Simple memory leak detection without external tools
- **`memory_leak_test.php`** - Comprehensive memory leak detection test

### Performance Tests

- **`performance_benchmark_test.php`** - Performance benchmarks and regression testing

### Integration Tests

- **`integration_validation_test.php`** - Core integration validation test
- **`thread_safety_test.php`** - Thread safety testing for ZTS environments

### Test Runners and Utilities

- **`run_comprehensive_tests.php`** - Main test runner that executes all test suites
- **`run_integration_tests.php`** - Integration test runner for core validation tests
- **`valgrind_memory_test.sh`** - Valgrind-based memory leak detection script
- **`test_all_php_versions.sh`** - Cross-version compatibility testing script
- **`README.md`** - This documentation file

### PHP Version Compatibility Tests

- **`test_all_php_versions.sh`** - Automated testing across multiple PHP versions to ensure compatibility

## Running Tests

### Quick Test Run

To run all critical tests quickly:

```bash
php tests/run_comprehensive_tests.php --quick
```

### Full Test Suite

To run all tests including performance benchmarks:

```bash
php tests/run_comprehensive_tests.php
```

### Verbose Output

For detailed output and debugging information:

```bash
php tests/run_comprehensive_tests.php --verbose
```

### Memory Checking

To include memory usage monitoring:

```bash
php tests/run_comprehensive_tests.php --memory-check
```

### Integration Test Suite

To run the core integration tests:

```bash
php tests/run_integration_tests.php
```

### Individual Test Files

You can also run individual test files:

```bash
php tests/known_vectors_test.php
php tests/parameter_validation_test.php
php tests/performance_benchmark_test.php
php tests/integration_validation_test.php
php tests/thread_safety_test.php
```

### Memory Leak Detection with Valgrind

For comprehensive memory leak detection using Valgrind:

```bash
./tests/valgrind_memory_test.sh
```

Or test a specific file:

```bash
./tests/valgrind_memory_test.sh tests/memory_leak_test.php
```

### PHP Version Compatibility Testing

To test the extension across all available PHP versions:

```bash
./tests/test_all_php_versions.sh
```

With verbose output for detailed information:

```bash
./tests/test_all_php_versions.sh --verbose
```

To see build command output for debugging:

```bash
./tests/test_all_php_versions.sh --build-output
```

For help with available options:

```bash
./tests/test_all_php_versions.sh --help
```

## Test Categories

### 1. Known Vector Tests

These tests validate that the extension produces correct Keccak256 hashes for known inputs:

- Empty string: `c5d2460186f7233c927e7db2dcc703c0e500b653ca82273b7bfad8045d85a470`
- "cc": `eead6dbfc7340a56caedc044696a168870549a6a7f6f56961e84a54bd9970b8a`
- Various other test vectors

### 2. Parameter Validation Tests

These tests ensure proper input validation:

- **Valid inputs**: Even-length hex strings (empty, short, long)
- **Invalid inputs**: Odd-length strings, non-hex characters, special characters
- **Error handling**: Proper exception throwing with descriptive messages

### 3. Memory Management Tests

These tests verify that memory is properly managed:

- **Allocation**: Proper use of `emalloc()` instead of `malloc()`
- **Deallocation**: Proper use of `efree()` for cleanup
- **Error paths**: Memory cleanup on all error conditions
- **Leak detection**: No memory leaks under normal or error conditions

### 4. Performance Tests

These tests ensure no performance regression:

- **Benchmarking**: Operations per second for various input sizes
- **Regression testing**: Comparison against baseline expectations
- **Memory efficiency**: Stable memory usage during operations

### 5. Error Handling Tests

These tests validate modern PHP 8 error handling:

- **Exception types**: Proper `InvalidArgumentException` throwing
- **Error messages**: Clear, descriptive error messages
- **Error conditions**: All invalid input scenarios handled gracefully

### 6. Integration Tests

These tests validate overall system integration:

- **Hash output validation**: Ensures identical output to original implementation
- **Cross-component testing**: Tests interaction between different parts
- **End-to-end validation**: Complete workflow testing

### 7. Thread Safety Tests

These tests validate thread safety in multi-threaded environments:

- **ZTS compatibility**: Tests for Zend Thread Safety environments
- **State isolation**: Ensures no global state interference
- **Concurrent operations**: Simulates concurrent function calls
- **Memory context isolation**: Tests memory management in threaded contexts

### 8. PHP Version Compatibility Tests

These tests ensure the extension works across multiple PHP versions:

- **Dynamic version detection**: Automatically detects available PHP versions (8.0, 8.1, 8.2, 8.3, 8.4+)
- **Build validation**: Tests compilation with each PHP version's development tools
- **Runtime compatibility**: Validates extension loading and function execution
- **Cross-version consistency**: Ensures identical behavior across PHP versions
- **Tool validation**: Checks for required `phpize` and `php-config` tools
- **Error reporting**: Detailed feedback for build or runtime failures

The script tests:
- Extension compilation with version-specific `phpize` and `php-config`
- Extension loading without errors or warnings
- Function correctness with known test vectors
- Error handling consistency across versions
- Memory management compatibility

## Test Requirements

### Prerequisites

1. **PHP 8.0+** - The extension is designed for modern PHP versions
2. **Keccak256 extension loaded** - The extension must be compiled and loaded
3. **Valgrind** (optional) - For comprehensive memory leak detection

### Extension Loading

Ensure the extension is loaded before running tests:

```bash
php -m | grep keccak256
```

If not loaded, you may need to:

1. Build the extension: `phpize && ./configure --enable-keccak256 && make`
2. Add to php.ini: `extension=keccak256.so`
3. Or load dynamically: `php -d extension=./modules/keccak256.so test_file.php`

Note: When running tests, use the dynamic loading approach with the correct path to the compiled extension in the `modules/` directory.

## Expected Results

### Successful Test Run

A successful test run should show:

- ✓ All known vector tests pass (9/9 tests)
- ✓ All parameter validation tests pass (13/13 tests)
- ✓ All error handling tests pass (7/7 tests)
- ✓ All return value tests pass (8/8 tests)
- ✓ All comprehensive validation tests pass (40+ tests)
- ✓ All edge case tests pass (8/8 tests)
- ✓ No memory leaks detected (3/3 memory tests pass)
- ✓ Performance within acceptable ranges (4/4 performance tests pass)
- ✓ Integration tests successful (14/14 tests pass)
- ✓ Thread safety verified (3/3 tests pass)

**Total: 11 test suites, 100+ individual tests**

### Common Issues

1. **Extension not loaded**: Ensure the extension is properly compiled and loaded
2. **Memory leaks**: Check that `emalloc()`/`efree()` are used correctly
3. **Performance regression**: May indicate issues with the modernization
4. **Test failures**: Could indicate bugs in the implementation

## Interpreting Results

### Test Status Indicators

- ✓ **PASS** - Test completed successfully
- ✗ **FAIL** - Test failed, issues detected
- ⚠ **ERROR** - Test encountered an error during execution
- - **SKIP** - Test was skipped (file not found, etc.)

### Memory Leak Detection

- **Definitely lost**: Memory that was allocated but never freed (serious leak)
- **Indirectly lost**: Memory lost due to pointer chains (serious leak)
- **Possibly lost**: Memory that might be leaked (investigate)
- **Still reachable**: Memory still accessible at exit (usually OK)

### Performance Metrics

- **Operations per second**: Higher is better
- **Average time per operation**: Lower is better
- **Memory usage**: Should remain stable across operations

## Continuous Integration

These tests are designed to be run in CI/CD pipelines:

```bash
# Quick validation
php tests/run_comprehensive_tests.php --quick

# Full validation with memory checking
php tests/run_comprehensive_tests.php --memory-check

# PHP version compatibility testing
./tests/test_all_php_versions.sh

# Valgrind testing (if available)
if command -v valgrind &> /dev/null; then
    ./tests/valgrind_memory_test.sh
fi
```

## Contributing

When adding new tests:

1. Follow the existing naming convention
2. Include proper error handling
3. Add documentation comments
4. Update this README if adding new test categories
5. Ensure tests are deterministic and repeatable

## Troubleshooting

### Common Test Failures

1. **"Extension not loaded"**: Compile and load the keccak256 extension
2. **"Function not found"**: Ensure the extension exports the keccak256() function
3. **Memory leaks**: Check for proper `emalloc()`/`efree()` usage
4. **Performance issues**: May indicate problems with the modernization

### Debug Mode

For debugging test issues:

```bash
php -d display_errors=1 -d error_reporting=E_ALL tests/test_file.php
```

### Valgrind Issues

If Valgrind tests fail:

1. Check that PHP was compiled with debug symbols
2. Ensure Valgrind is properly installed
3. Review the generated log files for detailed information

## Test Coverage

The test suite covers:

- ✅ PHP 8.0+ compatibility validation
- ✅ No warnings or deprecation notices
- ✅ Function works identically to original implementation
- ✅ Known test vector validation
- ✅ Error condition testing
- ✅ Various input size testing
- ✅ Memory leak detection
- ✅ Thread safety (where applicable)

### Test Statistics

The complete test suite includes:
- **16 test files** covering all aspects of the extension
- **100+ individual test cases** across all categories
- **Core functionality tests**: 5 files
- **Integration tests**: 2 files
- **Memory management tests**: 2 files
- **Performance tests**: 1 file
- **Edge case tests**: 1 file
- **PHP version compatibility tests**: 1 file
- **Test runners**: 2 files
- **Utilities**: 3 files

### PHP Version Coverage

The `test_all_php_versions.sh` script automatically tests:
- **PHP 8.0** - If available on the system
- **PHP 8.1** - If available on the system  
- **PHP 8.2** - If available on the system
- **PHP 8.3** - If available on the system
- **PHP 8.4+** - Future versions detected automatically
- **Default PHP** - System default PHP binary (if different from versioned ones)

Each PHP version is tested for:
- ✅ **Build compatibility**: Extension compiles successfully
- ✅ **Runtime loading**: Extension loads without errors
- ✅ **Function correctness**: Produces expected hash outputs
- ✅ **Error handling**: Proper exception throwing for invalid inputs
- ✅ **Tool availability**: Required `phpize` and `php-config` tools present

This comprehensive test suite ensures the modernized Keccak256 extension meets all requirements and maintains compatibility with PHP 8.x while providing reliable performance and memory management across multiple PHP versions.