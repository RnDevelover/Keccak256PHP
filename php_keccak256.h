#ifdef PHP_WIN32
#include "win32/php_stdint.h"
#elif HAVE_INTTYPES_H
#include <inttypes.h>
#elif HAVE_STDINT_H
#include <stdint.h>
#endif


#ifndef PHP_KECCAK256_H
#define PHP_KECCAK256_H 1
#define PHP_KECCAK256_VERSION "1.0"
#define PHP_KECCAK256_EXTNAME "keccak256"

PHP_FUNCTION(keccak256);

extern zend_module_entry keccak256_module_entry;
#define phpext_keccak256_ptr &keccak256_module_entry

#endif
