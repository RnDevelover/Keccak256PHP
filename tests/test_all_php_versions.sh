#!/bin/bash

# Test script to verify keccak256 extension compatibility across PHP versions

set -e  # Exit on any error unless explicitly handled

# Parse command line arguments
VERBOSE=false
SHOW_BUILD_OUTPUT=false

while [[ $# -gt 0 ]]; do
    case $1 in
        -v|--verbose)
            VERBOSE=true
            shift
            ;;
        -b|--build-output)
            SHOW_BUILD_OUTPUT=true
            shift
            ;;
        -h|--help)
            echo "Usage: $0 [OPTIONS]"
            echo "Options:"
            echo "  -v, --verbose       Show detailed output"
            echo "  -b, --build-output  Show build command output"
            echo "  -h, --help          Show this help message"
            exit 0
            ;;
        *)
            echo "Unknown option: $1"
            exit 1
            ;;
    esac
done

echo "Testing keccak256 extension across PHP versions..."
echo "=================================================="

# Function to test a specific PHP version
test_php_version() {
    local php_version=$1
    local php_binary=$2
    
    echo ""
    echo "=== Testing PHP $php_version ==="
    
    # Verify required tools exist
    local phpize_cmd="phpize${php_version}"
    local phpconfig_cmd="php-config${php_version}"
    
    if ! command -v "$phpize_cmd" >/dev/null 2>&1; then
        echo "✗ phpize${php_version} not found"
        return 1
    fi
    
    if ! command -v "$phpconfig_cmd" >/dev/null 2>&1; then
        echo "✗ php-config${php_version} not found"
        return 1
    fi
    
    # Clean and rebuild for this PHP version
    echo -n "Cleaning build artifacts: "
    
    # Backup tests directory to prevent phpize --clean from deleting our test files
    # Note: phpize --clean has a bug where it deletes .php files in tests/ directory
    # because it assumes they are PHP's internal test files (.phpt format)
    if [ -d "tests" ] && [ "$(ls -A tests/*.php 2>/dev/null)" ]; then
        cp -r tests tests_backup_$$
        [ "$VERBOSE" = true ] && echo "  Backed up tests directory"
    fi
    
    # Clean build artifacts (avoid phpize --clean as it deletes .php files in tests/)
    rm -rf .libs *.lo *.la modules/* configure config.status config.log Makefile libtool autom4te.cache build run-tests.php 2>/dev/null || true
    
    # Restore tests directory if it was backed up
    if [ -d "tests_backup_$$" ]; then
        rm -rf tests 2>/dev/null || true
        mv tests_backup_$$ tests
        [ "$VERBOSE" = true ] && echo "  Restored tests directory"
    fi
    
    echo "✓"
    
    # Build for this PHP version
    echo -n "Running phpize: "
    if [ "$SHOW_BUILD_OUTPUT" = true ]; then
        if ! $phpize_cmd; then
            echo "✗"
            return 1
        fi
    else
        if ! $phpize_cmd > /dev/null 2>&1; then
            echo "✗"
            [ "$VERBOSE" = true ] && echo "  Failed to run phpize. Try with --build-output for details."
            return 1
        fi
    fi
    echo "✓"
    
    echo -n "Configuring build: "
    if [ "$SHOW_BUILD_OUTPUT" = true ]; then
        if ! ./configure --enable-keccak256 --with-php-config=$phpconfig_cmd; then
            echo "✗"
            return 1
        fi
    else
        if ! ./configure --enable-keccak256 --with-php-config=$phpconfig_cmd > /dev/null 2>&1; then
            echo "✗"
            [ "$VERBOSE" = true ] && echo "  Configure failed. Try with --build-output for details."
            return 1
        fi
    fi
    echo "✓"
    
    echo -n "Compiling extension: "
    if [ "$SHOW_BUILD_OUTPUT" = true ]; then
        if ! make; then
            echo "✗"
            return 1
        fi
    else
        if ! make > /dev/null 2>&1; then
            echo "✗"
            [ "$VERBOSE" = true ] && echo "  Compilation failed. Try with --build-output for details."
            return 1
        fi
    fi
    echo "✓"
    
    if [ ! -f "./modules/keccak256.so" ]; then
        echo "✗ Extension binary not found"
        return 1
    fi
    
    # Test basic functionality
    echo -n "Extension loads: "
    if ! $php_binary -d extension=./modules/keccak256.so -m 2>/dev/null | grep -q keccak256; then
        echo "✗"
        return 1
    fi
    echo "✓"
    
    echo -n "Function works: "
    result=$($php_binary -d extension=./modules/keccak256.so -r "echo keccak256('');" 2>/dev/null || echo "ERROR")
    expected="c5d2460186f7233c927e7db2dcc703c0e500b653ca82273b7bfad8045d85a470"
    if [ "$result" != "$expected" ]; then
        echo "✗ (expected: $expected, got: $result)"
        return 1
    fi
    echo "✓"
    
    echo -n "Error handling: "
    error_test=$($php_binary -d extension=./modules/keccak256.so -r "
        try { 
            keccak256('gg'); 
            echo 'FAIL'; 
        } catch (Exception \$e) { 
            echo 'OK'; 
        } catch (Error \$e) { 
            echo 'OK'; 
        }
    " 2>/dev/null || echo "ERROR")
    
    if [ "$error_test" != "OK" ]; then
        echo "✗ (got: $error_test)"
        return 1
    fi
    echo "✓"
    
    echo "✓ All tests passed for PHP $php_version"
    return 0
}

# Detect available PHP versions dynamically
detect_php_versions() {
    local versions=()
    
    # Check for specific versioned PHP binaries
    for version in 8.0 8.1 8.2 8.3 8.4; do
        local php_bin="php${version}"
        if command -v "$php_bin" >/dev/null 2>&1; then
            versions+=("$version:$php_bin")
        fi
    done
    
    # Check default php binary and determine its version
    if command -v php >/dev/null 2>&1; then
        local php_version=$(php -r "echo PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;")
        local found=false
        
        # Check if we already have this version
        for v in "${versions[@]}"; do
            if [[ "$v" == "$php_version:"* ]]; then
                found=true
                break
            fi
        done
        
        # Add default php if it's a different version
        if [ "$found" = false ]; then
            versions+=("$php_version:php")
        fi
    fi
    
    echo "${versions[@]}"
}

# Test all available PHP versions
versions_tested=0
versions_passed=0

echo "Detecting available PHP versions..."
php_versions=($(detect_php_versions))

if [ ${#php_versions[@]} -eq 0 ]; then
    echo "✗ No PHP installations found"
    exit 1
fi

echo "Found PHP versions: ${php_versions[*]}"

for version_info in "${php_versions[@]}"; do
    IFS=':' read -r version binary <<< "$version_info"
    versions_tested=$((versions_tested + 1))
    
    # Use set +e to prevent script exit on test failure
    set +e
    if test_php_version "$version" "$binary"; then
        versions_passed=$((versions_passed + 1))
    fi
    set -e
done

echo ""
echo "=================================================="
echo "Summary: $versions_passed/$versions_tested PHP versions passed all tests"

if [ $versions_passed -eq $versions_tested ] && [ $versions_tested -gt 0 ]; then
    echo "✓ Build configuration successfully updated for modern PHP!"
    exit 0
else
    echo "✗ Some tests failed"
    exit 1
fi