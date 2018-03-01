# PHP extension for Ethereum's Keccak256
This extension enables keccak256() function which returns keccak256 of a given string (byte array).
Keccak256 is used for Ethereum message signing and obtaining wallet addresses given Public keys.
The idea of making an extension for PHP first comes from my experiences and secondly from this [stackoverflow post](https://stackoverflow.com/questions/44742153/keccak-256-in-php)

To install this into your php developer files installed box,
   * git clone https://github.com/RnDevelover/Keccak256PHP.git
   * cd Keccak256PHP
   * phpize
   * ./configure --enable-keccak256
   * make
   * copy modules/keccak256.so to your php extension directory.
   * enable extension by adding extension=keccak256.so to your php.ini

Usage:

$a="some text";
$hash=substr(keccak256($a),32);

Please note that, only first 32 bytes from what it returns should be considered as bytes of the hash.

## Credits

Thanks to SHA3 implementation at [brainhub/SHA3IUF](https://github.com/brainhub/SHA3IUF). I used relevant parts of their implementation. 
I adopted to PHP using the documentation in [here](https://devzone.zend.com/303/extension-writing-part-i-introduction-to-php-and-zend/).


## License

This code belongs to public. You can utilize it any way you like. But I would suggest attribution to [brainhub](https://github.com/brainhub/SHA3IUF) since most of the stuff belongs to this guy. I only integrated parts.
