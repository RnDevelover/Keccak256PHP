# PHP extension for Ethereum's Keccak256
This extension enables keccak256() function which returns keccak256 of a given string (byte array).
Keccak256 is used for Ethereum message signing, obtaining the wallet address given a public key, and determining the function selector of smart contracts to be provided as data in transactions given a function prototype (and in ABI encoding).
The idea of making an extension for PHP first comes from my experiences and secondly from this [stackoverflow post](https://stackoverflow.com/questions/44742153/keccak-256-in-php).

To install this into your php developer files installed box,
   * git clone https://github.com/RnDevelover/Keccak256PHP.git
   * cd Keccak256PHP
   * phpize
   * ./configure --enable-keccak256
   * make
   * copy modules/keccak256.so to your php extension directory.
   * enable extension by adding extension=keccak256.so to your php.ini

Usage:

$a="some text";<br/>
$hash=keccak256($a);
print\_r($hash);

This function returns an integer array where each element must be treated as a byte value (the values are lower than 256).

## Credits

Thanks to SHA3 implementation at [brainhub/SHA3IUF](https://github.com/brainhub/SHA3IUF). I used relevant parts of their implementation. 
I adopted to PHP using the documentation in [here](https://devzone.zend.com/303/extension-writing-part-i-introduction-to-php-and-zend/).


## License

This code belongs to public. You can utilize it any way you like. But I would suggest attribution to [brainhub](https://github.com/brainhub/SHA3IUF) since most of the stuff belongs to this guy. I only integrated parts.
