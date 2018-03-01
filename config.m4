PHP_ARG_ENABLE(keccak256, whether to enable keccack256 support,
[ --enable-keccak256   Enable keccak256 support])
if test "$PHP_KECCAK256" = "yes"; then
  AC_DEFINE(HAVE_KECCAK256, 1, [Whether you have keccak256])
  PHP_NEW_EXTENSION(keccak256, keccak256.c, $ext_shared)
fi
