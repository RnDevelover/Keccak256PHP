PHP_ARG_ENABLE([keccak256],
  [whether to enable keccak256 support],
  [AS_HELP_STRING([--enable-keccak256],
    [Enable keccak256 support])],
  [no])

if test "$PHP_KECCAK256" != "no"; then
  dnl Check PHP version compatibility (require PHP 8.0+)
  AC_MSG_CHECKING([for PHP version >= 8.0])
  PHP_VERSION_ID=`$PHP_CONFIG --vernum 2>/dev/null`
  if test -z "$PHP_VERSION_ID" || test "$PHP_VERSION_ID" -lt "80000"; then
    AC_MSG_ERROR([PHP 8.0 or later is required])
  fi
  AC_MSG_RESULT([yes])
  
  dnl Check for required headers
  AC_CHECK_HEADERS([stdint.h inttypes.h string.h])
  
  dnl Define the extension
  AC_DEFINE(HAVE_KECCAK256, 1, [Whether you have keccak256])
  
  dnl Set compiler flags for modern C standards and PHP 8 compatibility
  PHP_ADD_MAKEFILE_FRAGMENT
  
  dnl Enable modern C standard (C11) for better compatibility
  if test "$GCC" = "yes"; then
    CFLAGS="$CFLAGS -std=c11 -Wall -Wextra -Wno-unused-parameter"
  fi
  
  dnl Create the extension with PHP 8 optimizations
  PHP_NEW_EXTENSION(keccak256, keccak256.c, $ext_shared,, -DZEND_ENABLE_STATIC_TSRMLS_CACHE=1)
  
  dnl Add build directory
  PHP_ADD_BUILD_DIR($ext_builddir)
  
  dnl Add installation target for headers if needed
  PHP_INSTALL_HEADERS([ext/keccak256], [php_keccak256.h])
fi