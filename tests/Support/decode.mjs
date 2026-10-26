// Decodes QR codes with zxing-cpp (npm zxing-wasm), used as a black box. One JSON request
// per line on stdin, one JSON answer per line on stdout, so a test run starts Node only once.
//
// Request: {"rows": ["#.#", ...], "scale": 4, "margin": 4} - a matrix, drawn as square modules -
//          or {"png": "<base64>"} - an image - or {"svg": "<svg ...>", "width": 400}, rasterized with resvg.
// Answer:  {"text": "..."} or {"text": null}.
import { readFileSync } from 'node:fs'
import { createRequire } from 'node:module'
import { createInterface } from 'node:readline'
import { initWasm, Resvg } from '@resvg/resvg-wasm'
import { prepareZXingModule, readBarcodes } from 'zxing-wasm/reader'

// Load the WebAssembly from node_modules; by default zxing-wasm would fetch it from a CDN.
const require = createRequire(import.meta.url)
const zxing = readFileSync(require.resolve('zxing-wasm/reader/zxing_reader.wasm'))
prepareZXingModule({ overrides: { wasmBinary: zxing.buffer.slice(zxing.byteOffset, zxing.byteOffset + zxing.byteLength) } })
await initWasm(readFileSync(require.resolve('@resvg/resvg-wasm/index_bg.wasm')))

function rasterize({ svg, width }) {
  return new Resvg(svg, { fitTo: { mode: 'width', value: width }, background: 'white' }).render().asPng()
}

function draw({ rows, scale, margin }) {
  const width = (rows.length + margin * 2) * scale
  const pixels = new Uint8ClampedArray(width * width * 4).fill(255)

  rows.forEach((row, y) => {
    ;[...row].forEach((cell, x) => {
      if (cell !== '#') return

      for (let py = (y + margin) * scale; py < (y + margin + 1) * scale; py++) {
        for (let px = (x + margin) * scale; px < (x + margin + 1) * scale; px++) {
          const offset = (py * width + px) * 4
          pixels[offset] = pixels[offset + 1] = pixels[offset + 2] = 0
        }
      }
    })
  })

  return { data: pixels, width, height: width, colorSpace: 'srgb' }
}

for await (const line of createInterface({ input: process.stdin })) {
  const request = JSON.parse(line)
  const image = request.svg ? rasterize(request) : request.png ? new Uint8Array(Buffer.from(request.png, 'base64')) : draw(request)
  const [code] = await readBarcodes(image, { formats: ['QRCode'] })

  process.stdout.write(JSON.stringify({ text: code?.text ?? null }) + '\n')
}
