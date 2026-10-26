# Sources

The sources consulted while writing this package.

- ISO/IEC 18004:2015, *QR Code bar code symbology specification*: structure, modes, error correction, masking,
  format and version information, the reference decoding procedure, and the tables in `src/Encoder/Tables.php`.
- Thonky, *QR Code Tutorial* (thonky.com/qr-code-tutorial): prose walkthrough and its tables, and the
  "HELLO WORLD" worked example in the tests.
- Wikipedia, *QR code*.
- General textbook material on Reed-Solomon codes over GF(256): encoding, syndromes, the Berlekamp-Massey
  algorithm, Chien search and Forney's formula.
- N. Otsu, *A Threshold Selection Method from Gray-Level Histograms* (1979): the global threshold of the native decoder.
- P. Heckbert, *Fundamentals of Texture Mapping and Image Warping* (1989): projective mappings between
  quadrilaterals, used for perspective in the native decoder.
- CSS Color Module Level 4: color spaces, conversion matrices and sample conversion code, and gamut mapping.
- B. Ottosson, *A perceptual color space for image processing* (2020): OKLab and OKLCH.
- RFC 6350 (vCard), RFC 5545 (iCalendar), RFC 6068 (mailto) and RFC 6266 (Content-Disposition).
- zxing-cpp (npm `zxing-wasm`): decodes codes in the test suite, and backs the optional `zxing` reader; used as a black box.
- resvg (npm `@resvg/resvg-wasm`): rasterizes SVG for the zxing reader and the test suite; used as a black box.
