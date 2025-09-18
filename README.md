# PHP Keccak256 Extension

A modern PHP extension providing the `keccak256()` function for Ethereum development. This extension computes Keccak256 hashes from hex-encoded strings, essential for Ethereum message signing, wallet address derivation, and smart contract function selectors.

## Features

- **Modern PHP Support**: Compatible with PHP 8.0, 8.1, 8.2, and 8.3
- **High Performance**: Native C implementation for optimal speed
- **Proper Error Handling**: Comprehensive input validation with descriptive error messages
- **Memory Safe**: Uses PHP's memory management system to prevent leaks
- **Thread Safe**: Compatible with both ZTS and non-ZTS PHP builds

## Requirements

- PHP 8.0 or later
- PHP development headers (`php-dev` or `php-devel` package)
- Build tools: `gcc`, `make`, `autotools`
- Git (for cloning the repository)

## Installation

### Method 1: Build from Source (Recommended)

1. **Clone the repository:**
   ```bash
   git clone https://github.com/Accredifysg/Keccak256PHP.git
   cd Keccak256PHP
   ```

2. **Prepare the build environment:**
   ```bash
   phpize
   ```

3. **Configure the build:**
   ```bash
   ./configure --enable-keccak256
   ```

4. **Compile the extension:**
   ```bash
   make
   ```

5. **Install the extension:**
   ```bash
   sudo make install
   ```
   
   Or manually copy the compiled extension:
   ```bash
   sudo cp modules/keccak256.so $(php-config --extension-dir)/
   ```

6. **Enable the extension:**
   
   Add the following line to your `php.ini` file:
   ```ini
   extension=keccak256
   ```
   
   Or create a separate configuration file (recommended):
   ```bash
   echo "extension=keccak256" | sudo tee /etc/php/8.x/mods-available/keccak256.ini
   sudo phpenmod keccak256  # On Debian/Ubuntu systems
   ```

7. **Verify installation:**
   ```bash
   php -m | grep keccak256
   ```

### Method 2: Development Installation

For development or testing purposes:

```bash
git clone https://github.com/Accredifysg/Keccak256PHP.git
cd Keccak256PHP
phpize
./configure --enable-keccak256
make
# Load extension directly without system installation
php -d extension=./modules/keccak256.so -r "echo keccak256('cc');"
```

## Usage

### Basic Usage

```php
<?php
// Hash a hex-encoded string
$hexData = "cc";
$hash = keccak256($hexData);
echo $hash; // Output: eead6dbfc7340a56caedc044696a168870549a6a7f6f56961e84a54bd9970b8a
?>
```

### Ethereum Address Derivation

```php
<?php
// Derive Ethereum address from public key
$publicKey = "0450863ad64a87ae8a2fe83c1af1a8403cb53f53e486d8511dad8a04887e5b23522cd470243453a299fa9e77237716103abc11a1df38855ed6f2ee187e9c582ba6";
$hash = keccak256($publicKey);
$address = "0x" . substr($hash, -40); // Last 20 bytes (40 hex chars)
echo "Address: $address\n";
?>
```

### Smart Contract Function Selector

```php
<?php
// Generate function selector for "transfer(address,uint256)"
$functionSignature = "transfer(address,uint256)";
$hexSignature = bin2hex($functionSignature);
$hash = keccak256($hexSignature);
$selector = substr($hash, 0, 8); // First 4 bytes (8 hex chars)
echo "Function selector: 0x$selector\n";
?>
```

### Error Handling

```php
<?php
try {
    // This will throw InvalidArgumentException
    $hash = keccak256("abc"); // Odd length string
} catch (InvalidArgumentException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

try {
    // This will throw InvalidArgumentException
    $hash = keccak256("abcg"); // Invalid hex character 'g'
} catch (InvalidArgumentException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

// Valid usage
$hash = keccak256(""); // Empty string is valid
echo "Empty string hash: $hash\n";
?>
```

## Function Reference

### `keccak256(string $hexData): string`

Computes the Keccak256 hash of the given hex-encoded data.

**Parameters:**
- `$hexData` (string): Hex-encoded input data. Must contain only characters `0-9`, `a-f`, `A-F` and have even length.

**Returns:**
- (string): 64-character lowercase hex string representing the 32-byte Keccak256 hash.

**Throws:**
- `InvalidArgumentException`: When input contains non-hex characters or has odd length.

## Testing

Refer to tests/README.md for detailed instructions on running the test suites.

## Compatibility

### Supported PHP Versions

| PHP Version | Status            | Notes              |
|-------------|-------------------|--------------------|
| 8.3.x       | ✅ Fully Supported | Recommended        |
| 8.2.x       | ✅ Fully Supported | Recommended        |
| 8.1.x       | ✅ Fully Supported | Recommended        |
| 8.0.x       | ✅ Fully Supported | Minimum version    |
| 7.x         | ❌ Not Supported   | Use legacy version |
| 5.x         | ❌ Not Supported   | Use legacy version |

### Platform Support

- **Linux**: Fully supported (Ubuntu, Debian, CentOS, RHEL, etc.)
- **macOS**: Supported with Xcode command line tools
- **Windows**: Limited support (requires proper build environment)

## Troubleshooting

### Common Build Issues

#### "phpize: command not found"
```bash
# Ubuntu/Debian
sudo apt-get install php-dev

# CentOS/RHEL
sudo yum install php-devel

# macOS with Homebrew
brew install php
```

#### "PHP 8.0 or later is required"
```bash
# Check your PHP version
php --version

# Update PHP if needed (Ubuntu/Debian)
sudo apt-get update
sudo apt-get install php8.2-dev

# Update PHP (macOS with Homebrew)
brew upgrade php
```

#### "Cannot find autoconf"
```bash
# Ubuntu/Debian
sudo apt-get install autoconf

# CentOS/RHEL
sudo yum install autoconf

# macOS
brew install autoconf
```

#### Extension loads but function not found
1. Verify the extension is loaded:
   ```bash
   php -m | grep keccak256
   ```

2. Check for loading errors:
   ```bash
   php -r "var_dump(extension_loaded('keccak256'));"
   ```

3. Restart your web server after installation:
   ```bash
   sudo systemctl restart apache2  # or nginx, php-fpm, etc.
   ```

### Runtime Issues

#### "Call to undefined function keccak256()"
- Ensure the extension is properly installed and enabled in php.ini
- Check that you're using the correct PHP binary (CLI vs web server)
- Verify extension directory permissions

#### Memory-related errors
- The extension uses PHP's memory management system
- Memory leaks should not occur with proper usage
- Run memory tests if you suspect issues

### Performance Issues

#### Slower than expected
- Ensure you're using the compiled extension, not a PHP implementation
- Check that the extension is properly loaded
- Run performance benchmarks to compare

## Development

### Building for Development

```bash
# Clean build
make clean
phpize --clean
phpize
./configure --enable-keccak256 --enable-debug
make

# Run with debug information
php -d extension=./modules/keccak256.so your_test_script.php
```

### Running Tests During Development

```bash
# Quick validation
php -d extension=./modules/keccak256.so tests/known_vectors_test.php

# Full test suite
php -d extension=./modules/keccak256.so tests/run_comprehensive_tests.php
```

## Credits

- **Original Implementation**: Based on [RnDevelover/Keccak256PHP](https://github.com/RnDevelover/Keccak256PHP)
- **SHA3/Keccak Implementation**: Based on [brainhub/SHA3IUF](https://github.com/brainhub/SHA3IUF)
- **PHP Extension Structure**: Adapted from Zend extension documentation

## License

This code is released into the public domain. You may use it in any way you like. Attribution to the original SHA3 implementation by [brainhub](https://github.com/brainhub/SHA3IUF) is appreciated.

## Contributing

Contributions are welcome! Please ensure:

1. All tests pass
2. Code follows modern PHP extension best practices
3. Memory safety is maintained
4. Compatibility with supported PHP versions

## Support

For issues, questions, or contributions, please visit the [GitHub repository](https://github.com/Accredifysg/Keccak256PHP).
