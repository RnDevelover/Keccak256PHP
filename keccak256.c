#ifdef HAVE_CONFIG_H
#include "config.h"
#endif
#include "php.h"
#include "php_keccak256.h"
#include "zend_exceptions.h"
#include "ext/spl/spl_exceptions.h"

#define SHA3_ASSERT( x )
#if defined(_MSC_VER)
#define SHA3_TRACE( format, ...)
#define SHA3_TRACE_BUF( format, buf, l, ...)
#else
#define SHA3_TRACE(format, args...)
#define SHA3_TRACE_BUF(format, buf, l, args...)
#endif

#if defined(_MSC_VER)
#define SHA3_CONST(x) x
#else
#define SHA3_CONST(x) x##L
#endif

/* 'Words' here refers to uint64_t */
#define SHA3_KECCAK_SPONGE_WORDS \
	(((1600)/8/*bits to byte*/)/sizeof(uint64_t))
typedef struct sha3_context_ {
    uint64_t saved;             /* the portion of the input message that we
                                 * didn't consume yet */
    union {                     /* Keccak's state */
        uint64_t s[SHA3_KECCAK_SPONGE_WORDS];
        uint8_t sb[SHA3_KECCAK_SPONGE_WORDS * 8];
    };
    unsigned byteIndex;         /* 0..7--the next byte after the set one
                                 * (starts from 0; 0--none are buffered) */
    unsigned wordIndex;         /* 0..24--the next word to integrate input
                                 * (starts from 0) */
    unsigned capacityWords;     /* the double size of the hash output in
                                 * words (e.g. 16 for Keccak 512) */
} sha3_context;

#ifndef SHA3_ROTL64
#define SHA3_ROTL64(x, y) \
	(((x) << (y)) | ((x) >> ((sizeof(uint64_t)*8) - (y))))
#endif

static const uint64_t keccakf_rndc[24] = {
    SHA3_CONST(0x0000000000000001UL), SHA3_CONST(0x0000000000008082UL),
    SHA3_CONST(0x800000000000808aUL), SHA3_CONST(0x8000000080008000UL),
    SHA3_CONST(0x000000000000808bUL), SHA3_CONST(0x0000000080000001UL),
    SHA3_CONST(0x8000000080008081UL), SHA3_CONST(0x8000000000008009UL),
    SHA3_CONST(0x000000000000008aUL), SHA3_CONST(0x0000000000000088UL),
    SHA3_CONST(0x0000000080008009UL), SHA3_CONST(0x000000008000000aUL),
    SHA3_CONST(0x000000008000808bUL), SHA3_CONST(0x800000000000008bUL),
    SHA3_CONST(0x8000000000008089UL), SHA3_CONST(0x8000000000008003UL),
    SHA3_CONST(0x8000000000008002UL), SHA3_CONST(0x8000000000000080UL),
    SHA3_CONST(0x000000000000800aUL), SHA3_CONST(0x800000008000000aUL),
    SHA3_CONST(0x8000000080008081UL), SHA3_CONST(0x8000000000008080UL),
    SHA3_CONST(0x0000000080000001UL), SHA3_CONST(0x8000000080008008UL)
};

static const unsigned keccakf_rotc[24] = {
    1, 3, 6, 10, 15, 21, 28, 36, 45, 55, 2, 14, 27, 41, 56, 8, 25, 43, 62,
    18, 39, 61, 20, 44
};

static const unsigned keccakf_piln[24] = {
    10, 7, 11, 17, 18, 3, 5, 16, 8, 21, 24, 4, 15, 23, 19, 13, 12, 2, 20,
    14, 22, 9, 6, 1
};

/* generally called after SHA3_KECCAK_SPONGE_WORDS-ctx->capacityWords words 
 * are XORed into the state s 
 */
static void
keccakf(uint64_t s[25])
{
    int i, j, round;
    uint64_t t, bc[5];
#define KECCAK_ROUNDS 24

    for(round = 0; round < KECCAK_ROUNDS; round++) {

        /* Theta */
        for(i = 0; i < 5; i++)
            bc[i] = s[i] ^ s[i + 5] ^ s[i + 10] ^ s[i + 15] ^ s[i + 20];

        for(i = 0; i < 5; i++) {
            t = bc[(i + 4) % 5] ^ SHA3_ROTL64(bc[(i + 1) % 5], 1);
            for(j = 0; j < 25; j += 5)
                s[j + i] ^= t;
        }

        /* Rho Pi */
        t = s[1];
        for(i = 0; i < 24; i++) {
            j = keccakf_piln[i];
            bc[0] = s[j];
            s[j] = SHA3_ROTL64(t, keccakf_rotc[i]);
            t = bc[0];
        }

        /* Chi */
        for(j = 0; j < 25; j += 5) {
            for(i = 0; i < 5; i++)
                bc[i] = s[j + i];
            for(i = 0; i < 5; i++)
                s[j + i] ^= (~bc[(i + 1) % 5]) & bc[(i + 2) % 5];
        }

        /* Iota */
        s[0] ^= keccakf_rndc[round];
    }
}

/* *************************** Public Inteface ************************ */

/* For Init or Reset call these: */
static void
sha3_Init256(void *priv)
{
    sha3_context *ctx = (sha3_context *) priv;
    memset(ctx, 0, sizeof(*ctx));
    ctx->capacityWords = 2 * 256 / (8 * sizeof(uint64_t));
}



static void
sha3_Update(void *priv, void const *bufIn, size_t len)
{
    sha3_context *ctx = (sha3_context *) priv;

    /* 0...7 -- how much is needed to have a word */
    unsigned old_tail = (8 - ctx->byteIndex) & 7;

    size_t words;
    unsigned tail;
    size_t i;

    const uint8_t *buf = bufIn;

    SHA3_TRACE_BUF("called to update with:", buf, len);

    SHA3_ASSERT(ctx->byteIndex < 8);
    SHA3_ASSERT(ctx->wordIndex < sizeof(ctx->s) / sizeof(ctx->s[0]));

    if(len < old_tail) {        /* have no complete word or haven't started 
                                 * the word yet */
        SHA3_TRACE("because %d<%d, store it and return", (unsigned)len,
                (unsigned)old_tail);
        /* endian-independent code follows: */
        while (len--)
            ctx->saved |= (uint64_t) (*(buf++)) << ((ctx->byteIndex++) * 8);
        SHA3_ASSERT(ctx->byteIndex < 8);
        return;
    }

    if(old_tail) {              /* will have one word to process */
        SHA3_TRACE("completing one word with %d bytes", (unsigned)old_tail);
        /* endian-independent code follows: */
        len -= old_tail;
        while (old_tail--)
            ctx->saved |= (uint64_t) (*(buf++)) << ((ctx->byteIndex++) * 8);

        /* now ready to add saved to the sponge */
        ctx->s[ctx->wordIndex] ^= ctx->saved;
        SHA3_ASSERT(ctx->byteIndex == 8);
        ctx->byteIndex = 0;
        ctx->saved = 0;
        if(++ctx->wordIndex ==
                (SHA3_KECCAK_SPONGE_WORDS - ctx->capacityWords)) {
            keccakf(ctx->s);
            ctx->wordIndex = 0;
        }
    }

    /* now work in full words directly from input */

    SHA3_ASSERT(ctx->byteIndex == 0);

    words = len / sizeof(uint64_t);
    tail = len - words * sizeof(uint64_t);

    SHA3_TRACE("have %d full words to process", (unsigned)words);

    for(i = 0; i < words; i++, buf += sizeof(uint64_t)) {
        const uint64_t t = (uint64_t) (buf[0]) |
                ((uint64_t) (buf[1]) << 8 * 1) |
                ((uint64_t) (buf[2]) << 8 * 2) |
                ((uint64_t) (buf[3]) << 8 * 3) |
                ((uint64_t) (buf[4]) << 8 * 4) |
                ((uint64_t) (buf[5]) << 8 * 5) |
                ((uint64_t) (buf[6]) << 8 * 6) |
                ((uint64_t) (buf[7]) << 8 * 7);
#if defined(__x86_64__ ) || defined(__i386__)
        SHA3_ASSERT(memcmp(&t, buf, 8) == 0);
#endif
        ctx->s[ctx->wordIndex] ^= t;
        if(++ctx->wordIndex ==
                (SHA3_KECCAK_SPONGE_WORDS - ctx->capacityWords)) {
            keccakf(ctx->s);
            ctx->wordIndex = 0;
        }
    }

    SHA3_TRACE("have %d bytes left to process, save them", (unsigned)tail);

    /* finally, save the partial word */
    SHA3_ASSERT(ctx->byteIndex == 0 && tail < 8);
    while (tail--) {
        SHA3_TRACE("Store byte %02x '%c'", *buf, *buf);
        ctx->saved |= (uint64_t) (*(buf++)) << ((ctx->byteIndex++) * 8);
    }
    SHA3_ASSERT(ctx->byteIndex < 8);
    SHA3_TRACE("Have saved=0x%016" PRIx64 " at the end", ctx->saved);
}

/* This is simply the 'update' with the padding block.
 * The padding block is 0x01 || 0x00* || 0x80. First 0x01 and last 0x80 
 * bytes are always present, but they can be the same byte.
 */
static void const *
sha3_Finalize(void *priv)
{
    sha3_context *ctx = (sha3_context *) priv;

    SHA3_TRACE("called with %d bytes in the buffer", ctx->byteIndex);

    /* Append 2-bit suffix 01, per SHA-3 spec. Instead of 1 for padding we
     * use 1<<2 below. The 0x02 below corresponds to the suffix 01.
     * Overall, we feed 0, then 1, and finally 1 to start padding. Without
     * M || 01, we would simply use 1 to start padding. */

    /* For testing the "pure" Keccak version */
    ctx->s[ctx->wordIndex] ^=
            (ctx->saved ^ ((uint64_t) ((uint64_t) 1 << (ctx->byteIndex *
                                    8))));

    ctx->s[SHA3_KECCAK_SPONGE_WORDS - ctx->capacityWords - 1] ^=
            SHA3_CONST(0x8000000000000000UL);
    keccakf(ctx->s);

    /* Return first bytes of the ctx->s. This conversion is not needed for
     * little-endian platforms e.g. wrap with #if !defined(__BYTE_ORDER__)
     * || !defined(__ORDER_LITTLE_ENDIAN__) || \
     * __BYTE_ORDER__!=__ORDER_LITTLE_ENDIAN__ ... the conversion below ...
     * #endif */
    {
        unsigned i;
        for(i = 0; i < SHA3_KECCAK_SPONGE_WORDS; i++) {
            const unsigned t1 = (uint32_t) ctx->s[i];
            const unsigned t2 = (uint32_t) ((ctx->s[i] >> 16) >> 16);
            ctx->sb[i * 8 + 0] = (uint8_t) (t1);
            ctx->sb[i * 8 + 1] = (uint8_t) (t1 >> 8);
            ctx->sb[i * 8 + 2] = (uint8_t) (t1 >> 16);
            ctx->sb[i * 8 + 3] = (uint8_t) (t1 >> 24);
            ctx->sb[i * 8 + 4] = (uint8_t) (t2);
            ctx->sb[i * 8 + 5] = (uint8_t) (t2 >> 8);
            ctx->sb[i * 8 + 6] = (uint8_t) (t2 >> 16);
            ctx->sb[i * 8 + 7] = (uint8_t) (t2 >> 24);
        }
    }

    SHA3_TRACE_BUF("Hash: (first 32 bytes)", ctx->sb, 256 / 8);

    return (ctx->sb);
}

/* Argument information for keccak256 function */
ZEND_BEGIN_ARG_INFO_EX(arginfo_keccak256, 0, 0, 1)
    ZEND_ARG_TYPE_INFO(0, data, IS_STRING, 0)
ZEND_END_ARG_INFO()

/* Function entry table with proper argument information */
static const zend_function_entry keccak256_functions[] = {
    PHP_FE(keccak256, arginfo_keccak256)
    PHP_FE_END
};

zend_module_entry keccak256_module_entry = {
#if ZEND_MODULE_API_NO >= 20010901
    STANDARD_MODULE_HEADER,
#endif
    PHP_KECCAK256_EXTNAME,
    keccak256_functions,
    NULL,
    NULL,
    NULL,
    NULL,
    NULL,
#if ZEND_MODULE_API_NO >= 20010901
    PHP_KECCAK256_VERSION,
#endif
    STANDARD_MODULE_PROPERTIES
};

#ifdef COMPILE_DL_KECCAK256
ZEND_GET_MODULE(keccak256)
#endif
/* Helper function to validate hex character */
static int is_hex_char(char c) {
    return (c >= '0' && c <= '9') || 
           (c >= 'A' && c <= 'F') || 
           (c >= 'a' && c <= 'f');
}

/* Helper function to convert hex character to value */
static unsigned char hex_char_to_value(char c) {
    if (c >= '0' && c <= '9') {
        return c - '0';
    } else if (c >= 'A' && c <= 'F') {
        return c - 'A' + 10;
    } else if (c >= 'a' && c <= 'f') {
        return c - 'a' + 10;
    }
    return 0; /* Should never reach here if validation is done */
}

PHP_FUNCTION(keccak256)
{
    char *input_str;
    size_t input_len;
    unsigned char *binary_data = NULL;
    size_t binary_len;
    size_t i;
    sha3_context c;
    const uint8_t *hash;
    char *result_str;
    zend_string *result;

    /* Parse parameters with modern PHP 8 syntax */
    ZEND_PARSE_PARAMETERS_START(1, 1)
        Z_PARAM_STRING(input_str, input_len)
    ZEND_PARSE_PARAMETERS_END();

    /* Validate input is not empty */
    if (input_len == 0) {
        /* Empty string is valid - hash of empty input */
        binary_data = NULL;
        binary_len = 0;
    } else {
        /* Validate input length is even */
        if (input_len % 2 != 0) {
            zend_throw_exception(spl_ce_InvalidArgumentException, 
                "Input must be even-length hex string", 0);
            return;
        }

        /* Validate all characters are valid hex */
        for (i = 0; i < input_len; i++) {
            if (!is_hex_char(input_str[i])) {
                zend_throw_exception(spl_ce_InvalidArgumentException, 
                    "Input contains non-hexadecimal characters", 0);
                return;
            }
        }

        /* Allocate memory for binary data using PHP memory management */
        binary_len = input_len / 2;
        binary_data = (unsigned char*)emalloc(binary_len);
        if (!binary_data) {
            /* emalloc failure is handled by PHP automatically with fatal error,
             * but we add this check for completeness */
            zend_throw_exception(zend_ce_error_exception, 
                "Memory allocation failed", 0);
            return;
        }

        /* Convert hex string to binary */
        for (i = 0; i < binary_len; i++) {
            unsigned char high_nibble = hex_char_to_value(input_str[i * 2]);
            unsigned char low_nibble = hex_char_to_value(input_str[i * 2 + 1]);
            binary_data[i] = (high_nibble << 4) | low_nibble;
        }
    }

    /* Compute Keccak256 hash */
    sha3_Init256(&c);
    if (binary_data && binary_len > 0) {
        sha3_Update(&c, binary_data, binary_len);
    }
    hash = sha3_Finalize(&c);

    /* Convert hash to hex string */
    result = zend_string_alloc(64, 0); /* 32 bytes * 2 chars per byte */
    if (!result) {
        /* Clean up binary data before throwing exception */
        if (binary_data) {
            efree(binary_data);
        }
        zend_throw_exception(zend_ce_error_exception, 
            "Failed to allocate result string", 0);
        return;
    }
    
    result_str = ZSTR_VAL(result);
    
    for (i = 0; i < 32; i++) {
        unsigned char high_nibble = (hash[i] & 0xF0) >> 4;
        unsigned char low_nibble = hash[i] & 0x0F;
        
        result_str[i * 2] = (high_nibble < 10) ? 
            (high_nibble + '0') : (high_nibble - 10 + 'a');
        result_str[i * 2 + 1] = (low_nibble < 10) ? 
            (low_nibble + '0') : (low_nibble - 10 + 'a');
    }
    
    result_str[64] = '\0';
    ZSTR_LEN(result) = 64;

    /* Clean up binary data before returning */
    if (binary_data) {
        efree(binary_data);
    }

    RETURN_STR(result);
}
